<?php

namespace OneToMany\RichBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Path;

use function ctype_alnum;
use function ctype_alpha;
use function dirname;
use function file_exists;
use function is_dir;
use function is_string;
use function lcfirst;
use function mkdir;
use function sprintf;
use function strtolower;
use function substr;
use function vsprintf;

final class MakeRichDomain extends AbstractMaker
{
    private string $rootDirectory;

    /**
     * Directories relative to the domain root that are
     * created even if no class is generated inside them.
     *
     * @var list<non-empty-string>
     */
    private const array DIRECTORIES = [
        'Action/Command',
        'Action/Event',
        'Action/Handler',
        'Action/Handler/Exception',
        'Action/Input',
        'Contract/Enum',
        'Contract/Exception',
        'Contract/Repository',
        'Exception',
        'Framework/Command',
        'Framework/Controller/API',
        'Framework/Controller/Web',
    ];

    public function __construct(
        private readonly FileManager $fileManager,
    ) {
        $this->rootDirectory = $this->fileManager->getRootDirectory();
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public static function getCommandName(): string
    {
        return 'make:rich-domain';
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public static function getCommandDescription(): string
    {
        return 'Create RICH domain directories and classes';
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    #[\Override]
    public function configureCommand(
        Command $command,
        InputConfiguration $inputConfig,
    ): void {
        $command
            ->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain (e.g. <fg=yellow>Account</>)')
            ->addOption('create-repository', null, InputOption::VALUE_NEGATABLE, "Generate a Doctrine repository interface for the domain's entity", true)
            ->addOption('create-actions', null, InputOption::VALUE_NEGATABLE, "Generate the RICH classes to create and read the domain's entity", true)
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command generates the directories and classes for a RICH domain:

                <info>php %command.full_name% Account</info>

                Existing files are never overwritten, so the command can also be run for an existing domain to generate any missing classes.

                Use the <info>--no-create-repository</info> option to skip generating the Doctrine repository interface:

                <info>php %command.full_name% Account --no-create-repository</info>

                Use the <info>--no-create-actions</info> option to skip generating the create and read action stubs:

                <info>php %command.full_name% Account --no-create-actions</info>
                HELP)
        ;
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    #[\Override]
    public function configureDependencies(
        DependencyBuilder $dependencies,
    ): void {
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public function generate(
        InputInterface $input,
        ConsoleStyle $io,
        Generator $generator,
    ): void {
        $domain = $this->normalizeDomain($input);

        $rootNamespace = $generator->getRootNamespace();
        $domainNamespace = "{$rootNamespace}\\Domain\\{$domain}";

        // The file manager only resolves paths for classes, so the domain directory
        // is resolved by simulating a class name in the root directory of the domain
        $domainDir = dirname($this->getPathForClass("{$domainNamespace}\\{$domain}"));

        // Generate an absolute path to the domain root directory
        $domainDir = Path::join($this->rootDirectory, $domainDir);

        if (!Path::isAbsolute($domainDir)) {
            throw new RuntimeCommandException(sprintf('The domain directory "%s" is not an absolute path.', $domainDir));
        }

        foreach (self::DIRECTORIES as $directory) {
            $path = Path::join($domainDir, $directory);

            if (!file_exists($path)) {
                @mkdir($path, 0755, true);

                if (!is_dir($path)) {
                    throw new RuntimeCommandException(sprintf('Failed to create "%s".', $path));
                }

                $io->comment(sprintf('<fg=blue>created</>: %s/', Path::makeRelative($path, $this->rootDirectory)));
            }
        }

        $globalTemplateVariables = [
            'entity_class_name' => $domain,
            'entity_full_class_name' => "{$rootNamespace}\\Entity\\{$domain}",
            'runtime_exception_class_name' => 'RuntimeException',
            'runtime_exception_full_class_name' => "{$domainNamespace}\\Exception\\RuntimeException",
            'exception_interface_full_class_name' => "{$domainNamespace}\\Contract\\Exception\\ExceptionInterface",
        ];

        $classesToCreate = [
            'Contract\\Exception\\ExceptionInterface' => [
                'contracts/exception/ExceptionInterface.tpl.php', [],
            ],
        ];

        // Doctrine repository interface
        if (true === $input->getOption('create-repository')) {
            $classesToCreate["Contract\\Repository\\{$domain}RepositoryInterface"] = [
                'contracts/repository/RepositoryInterface.tpl.php', [],
            ];
        }

        // Exception classes
        $classesToCreate['Exception\\DomainException'] = [
            'exception/DomainException.tpl.php', [],
        ];

        $classesToCreate['Exception\\RuntimeException'] = [
            'exception/RuntimeException.tpl.php', [],
        ];

        $idProperty = lcfirst($domain).'Id';

        // Create and Read action classes
        if (true === $input->getOption('create-actions')) {
            // Create{Domain} action classes and stubs
            $createActionClasses = $this->getActionClasses(
                $domainNamespace, "Create{$domain}", null,
            );

            $classesToCreate = [...$classesToCreate, ...$createActionClasses];

            // {Domain}Created event class
            $classesToCreate["Action\\Event\\{$domain}Created"] = [
                'action/event/Event.tpl.php', [
                    'id_property' => $idProperty,
                ],
            ];

            // Read{Domain} action classes and stubs
            $readActionClasses = $this->getActionClasses(
                $domainNamespace, "Read{$domain}", $idProperty,
            );

            $classesToCreate = [...$classesToCreate, ...$readActionClasses];

            // app:read-{domain} Symfony Console class
            $commandName = vsprintf('app:read-%s', [
                Str::asCommand($domain),
            ]);

            $humanizedDomain = Str::asHumanWords($domain);
            $humanizedDomain = strtolower($humanizedDomain);

            $classesToCreate["Framework\\Command\\Read{$domain}Command"] = [
                'framework/command/Command.tpl.php', [
                    'command_name' => $commandName,
                    'humanized_domain' => $humanizedDomain,
                    'id_property' => $idProperty,
                ],
            ];
        }

        $skippedPaths = [];
        $templateDir = dirname(__DIR__, 2).'/templates';

        foreach ($classesToCreate as $class => [$template, $variables]) {
            $className = "{$domainNamespace}\\{$class}";
            $classPath = $this->getPathForClass($className);

            if (file_exists($classPath)) {
                $skippedPaths[] = $classPath;

                continue;
            }

            $generator->generateClass($className, "{$templateDir}/{$template}", [
                ...$globalTemplateVariables, ...$variables,
            ]);
        }

        $generator->writeChanges();

        foreach ($skippedPaths as $path) {
            $io->comment(sprintf('<fg=yellow>skipped</>: %s', $path));
        }

        $this->writeSuccessMessage($io);

        $io->text('Next: open and customize your input, command, and handler classes!');
        $io->text('Find the documentation at <fg=yellow>https://github.com/1tomany/rich-bundle</>');
    }

    /**
     * @return non-empty-string
     */
    private function normalizeDomain(InputInterface $input): string
    {
        $argument = $input->getArgument('domain');

        // Normalize names like "account" or "invoice-line"
        $domain = Str::asClassName(is_string($argument) ? $argument : '');

        // The domain name is used in namespaces, class, and variable names
        if (!ctype_alpha(substr($domain, 0, 1)) || !ctype_alnum($domain)) {
            throw new RuntimeCommandException(sprintf('The domain name "%s" is not valid: it must start with a letter and contain only letters and numbers.', $domain));
        }

        return $domain;
    }

    /**
     * @return array<non-empty-string, array{non-empty-string, array<non-empty-string, ?string>}>
     */
    private function getActionClasses(
        string $namespace,
        string $action,
        ?string $idProperty,
    ): array {
        $actionClassVariables = [
            'id_property' => $idProperty,
            'command_class_name' => "{$action}Command",
            'command_full_class_name' => "{$namespace}\\Action\\Command\\{$action}Command",
        ];

        return [
            "Action\\Command\\{$action}Command" => [
                'action/command/Command.tpl.php',
                $actionClassVariables,
            ],
            "Action\\Input\\{$action}Input" => [
                'action/input/Input.tpl.php',
                $actionClassVariables,
            ],
            "Action\\Handler\\{$action}Handler" => [
                'action/handler/Handler.tpl.php',
                $actionClassVariables,
            ],
        ];
    }

    private function getPathForClass(string $class): string
    {
        $path = $this->fileManager->getRelativePathForFutureClass($class);

        if (null === $path) {
            throw new RuntimeCommandException(sprintf('Could not determine where to locate the class "%s". Make sure its namespace is configured in the "autoload" section of your composer.json file.', $class));
        }

        return $path;
    }
}
