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
use function substr;

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
        return 'Create a new RICH domain';
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
            ->addOption('repository', null, InputOption::VALUE_NEGATABLE, "Generate a Doctrine repository interface for the domain's entity", true)
            ->addOption('create-stub', null, InputOption::VALUE_NEGATABLE, "Generate the RICH classes to create the domain's entity", true)
            ->addOption('read-stub', null, InputOption::VALUE_NEGATABLE, "Generate the RICH and console command classes to read the domain's entity", true)
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command generates the directories and classes for a RICH domain:

                <info>php %command.full_name% Account</info>

                Existing files are never overwritten, so the command can also be run for an existing domain to generate any missing classes.

                Use the <info>--no-repository</info> option to skip generating the repository interface:

                <info>php %command.full_name% Account --no-repository</info>

                Use the <info>--no-create-stub</info> and <info>--no-read-stub</info> options to skip generating the Create and Read action stubs:

                <info>php %command.full_name% Account --no-create-stub --no-read-stub</info>
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

        // Global template variables
        $variables = [
            'entity_class_name' => $domain,
            'entity_full_class_name' => "{$rootNamespace}\\Entity\\{$domain}",
            'exception_class_name' => 'RuntimeException',
            'exception_full_class_name' => "{$domainNamespace}\\Exception\\RuntimeException",
            'exception_interface_full_class_name' => "{$domainNamespace}\\Contract\\Exception\\ExceptionInterface",
        ];

        // Classes to create
        $classes = [
            'Contract\\Exception\\ExceptionInterface' => [
                'contracts/exception/ExceptionInterface.tpl.php', [],
            ],
        ];

        // Create the repository interface
        if (true === $input->getOption('repository')) {
            $classes["Contract\\Repository\\{$domain}RepositoryInterface"] = [
                'contracts/repository/RepositoryInterface.tpl.php', [],
            ];
        }

        // Exception classes
        $classes['Exception\\DomainException'] = [
            'exception/DomainException.tpl.php', [],
        ];

        $classes['Exception\\RuntimeException'] = [
            'exception/RuntimeException.tpl.php', [],
        ];

        $idProperty = lcfirst($domain).'Id';

        // Create the Create{Domain} action classes
        if (true === $input->getOption('create-stub')) {
            $actionClasses = $this->getActionClasses(
                $domainNamespace, "Create{$domain}", null,
            );

            $classes = [...$classes, ...$actionClasses];

            // Create the {Domain}Created event class
            $classes["Action\\Event\\{$domain}Created"] = [
                'action/event/Event.tpl.php', [
                    'id_property' => $idProperty,
                ],
            ];
        }

        // Create the Read{Domain} action classes
        if (true === $input->getOption('read-stub')) {
            $actionClasses = $this->getActionClasses(
                $domainNamespace, "Read{$domain}", $idProperty,
            );

            $classes = [...$classes, ...$actionClasses];

            // Create the app:read-{domain} Symfony Console class
            $classes["Framework\\Command\\Read{$domain}Command"] = [
                'framework/command/Command.tpl.php', [
                    'command_name' => 'app:read-'.Str::asCommand($domain),
                ],
            ];
        }

        $skippedPaths = [];
        $templateDirectory = dirname(__DIR__, 2).'/templates';

        foreach ($classes as $relativeClass => [$template, $classVariables]) {
            $class = "{$domainNamespace}\\{$relativeClass}";
            $path = $this->getPathForClass($class);

            // Never overwrite an existing file
            if ($this->fileManager->fileExists($path)) {
                $skippedPaths[] = $path;

                continue;
            }

            $generator->generateClass($class, "{$templateDirectory}/{$template}", [
                ...$variables, ...$classVariables,
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
        $variables = [
            'id_property' => $idProperty,
            'command_class_name' => "{$action}Command",
            'command_full_class_name' => "{$namespace}\\Action\\Command\\{$action}Command",
        ];

        return [
            "Action\\Command\\{$action}Command" => [
                'action/command/Command.tpl.php',
                $variables,
            ],
            "Action\\Input\\{$action}Input" => [
                'action/input/Input.tpl.php',
                $variables,
            ],
            "Action\\Handler\\{$action}Handler" => [
                'action/handler/Handler.tpl.php',
                $variables,
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
