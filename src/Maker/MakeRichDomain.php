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
use Symfony\Component\Filesystem\Filesystem;

use function array_diff;
use function ctype_alnum;
use function ctype_alpha;
use function dirname;
use function is_string;
use function lcfirst;
use function sprintf;
use function substr;

final class MakeRichDomain extends AbstractMaker
{
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
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
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
            ->addOption('without-repository', null, InputOption::VALUE_NONE, 'Do not generate the repository interface')
            ->addOption('with-create-stub', null, InputOption::VALUE_NEGATABLE, 'Generate the input, command, and handler classes to create an entity', true)
            ->addOption('with-read-stub', null, InputOption::VALUE_NEGATABLE, 'Generate the input, command, handler, and console command classes to read an entity', true)
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command generates the directories and classes for a RICH domain:

                <info>php %command.full_name% Account</info>

                Existing files are never overwritten, so the command can also be run for an existing domain to generate any missing classes.

                Use the <info>--without-repository</info> option to skip generating the repository interface:

                <info>php %command.full_name% Account --without-repository</info>

                Use the <info>--no-with-create-stub</info> and <info>--no-with-read-stub</info> options to skip generating the Create and Read action stubs:

                <info>php %command.full_name% Account --no-with-create-stub --no-with-read-stub</info>
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
        $domain = $this->getDomain($input);

        $withRepository = true !== $input->getOption('without-repository');
        $withCreateStub = true === $input->getOption('with-create-stub');
        $withReadStub = true === $input->getOption('with-read-stub');

        $rootNamespace = $generator->getRootNamespace();
        $namespace = "{$rootNamespace}\\Domain\\{$domain}";
        $idProperty = lcfirst($domain).'Id';

        // Variables shared by all templates
        $variables = [
            'entity_class_name' => $domain,
            'entity_full_class_name' => "{$rootNamespace}\\Entity\\{$domain}",
            'exception_class_name' => 'RuntimeException',
            'exception_full_class_name' => "{$namespace}\\Exception\\RuntimeException",
            'exception_interface_full_class_name' => "{$namespace}\\Contract\\Exception\\ExceptionInterface",
        ];

        // Classes relative to the domain namespace and their templates
        $classes = [
            'Contract\\Exception\\ExceptionInterface' => [
                'contracts/exception/ExceptionInterface.tpl.php', [],
            ],
        ];

        if ($withRepository) {
            $classes["Contract\\Repository\\{$domain}RepositoryInterface"] = [
                'contracts/repository/RepositoryInterface.tpl.php', [],
            ];
        }

        $classes['Exception\\DomainException'] = [
            'exception/DomainException.tpl.php', [],
        ];

        $classes['Exception\\RuntimeException'] = [
            'exception/RuntimeException.tpl.php', [],
        ];

        if ($withCreateStub) {
            $actionClasses = $this->getActionClasses(
                $namespace, "Create{$domain}", null,
            );

            $classes = [...$classes, ...$actionClasses];

            $classes["Action\\Event\\{$domain}Created"] = [
                'action/event/Event.tpl.php', [
                    'id_property' => $idProperty,
                ],
            ];
        }

        if ($withReadStub) {
            $actionClasses = $this->getActionClasses(
                $namespace, "Read{$domain}", $idProperty,
            );

            $classes = [...$classes, ...$actionClasses];

            $classes["Framework\\Command\\Read{$domain}Command"] = [
                'framework/command/Command.tpl.php', [
                    'command_name' => 'app:read-'.Str::asCommand($domain),
                ],
            ];
        }

        $skippedPaths = [];
        $templateDirectory = dirname(__DIR__, 2).'/templates';

        foreach ($classes as $relativeClass => [$template, $classVariables]) {
            $class = "{$namespace}\\{$relativeClass}";
            $path = $this->getPathForClass($class);

            // Never overwrite an existing file
            if ($this->fileManager->fileExists($path)) {
                $skippedPaths[] = $path;

                continue;
            }

            $generator->generateClass($class, "{$templateDirectory}/{$template}", [...$variables, ...$classVariables]);
        }

        $generator->writeChanges();

        $directories = self::DIRECTORIES;

        if (!$withRepository) {
            $directories = array_diff($directories, ['Contract/Repository']);
        }

        // The file manager only resolves paths for classes, so the
        // domain directory is resolved from a class in the domain root
        $domainDirectory = dirname($this->getPathForClass("{$namespace}\\{$domain}"));

        foreach ($directories as $directory) {
            $directory = $domainDirectory.'/'.$directory;

            if (!$this->fileManager->fileExists($directory)) {
                $this->filesystem->mkdir($this->fileManager->absolutizePath($directory));

                $io->comment(sprintf('<fg=blue>created</>: %s/', $directory));
            }
        }

        foreach ($skippedPaths as $path) {
            $io->comment(sprintf('<fg=yellow>skipped</>: %s (already exists)', $path));
        }

        $this->writeSuccessMessage($io);

        $io->text('Find the documentation at <fg=yellow>https://github.com/1tomany/rich-bundle</>');
    }

    /**
     * @return non-empty-string
     */
    private function getDomain(InputInterface $input): string
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
            'command_full_class_name' => "{$namespace}\\Action\\Command\\{$action}Command",
            'command_class_name' => "{$action}Command",
            'id_property' => $idProperty,
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
