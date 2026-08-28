<?php

namespace OneToMany\RichBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\String\UnicodeString;

use function Symfony\Component\String\u;

final class MakeRichModule extends AbstractMaker
{
    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public static function getCommandName(): string
    {
        return 'make:rich-module';
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public static function getCommandDescription(): string
    {
        return 'Creates the directory and file structure for a new RICH module';
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('module', InputArgument::OPTIONAL, 'Choose a name for your module (e.g. <fg=yellow>Account</>)')
            ->addOption('src-dir', null, InputOption::VALUE_REQUIRED, 'The directory the module structure is created in, relative to the current working directory', 'src')
            ->addOption('with-commands', null, InputOption::VALUE_NEGATABLE, 'Create the console command directory for the module', true)
            ->addOption('with-controllers', null, InputOption::VALUE_NEGATABLE, 'Create the controller directories for the module', true)
            ->addOption('with-repository', null, InputOption::VALUE_NEGATABLE, 'Create the repository contract for the module', true)
        ;
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(UnicodeString::class, 'string');
    }

    /**
     * @see Symfony\Bundle\MakerBundle\MakerInterface
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $module = $this->resolveModule($this->stringify($input->getArgument('module')));
        $srcDir = $this->resolveSourceDirectory($this->stringify($input->getOption('src-dir')));

        $moduleDir = $srcDir.'/Module/'.$module;

        // Action
        foreach (['Command', 'Event', 'Handler/Exception', 'Input'] as $directory) {
            $this->createDirectory($moduleDir.'/Action/'.$directory, $io);
        }

        // Controllers
        if ($input->getOption('with-controllers')) {
            foreach (['API', 'Web'] as $directory) {
                $this->createDirectory($srcDir.'/Controller/'.$module.'/'.$directory, $io);
            }
        }

        // Console Commands
        if ($input->getOption('with-commands')) {
            $this->createDirectory($srcDir.'/Command/'.$module, $io);
        }

        // Contract\Exception
        $this->createFile($generator, $moduleDir.'/Contract/Exception/ExceptionInterface.php', 'rich/contract/exception/ExceptionInterface.tpl.php', [
            'namespace' => sprintf('App\\Module\\%s\\Contract\\Exception', $module),
        ]);

        // Contract\Repository
        if ($input->getOption('with-repository')) {
            $repositoryDir = $srcDir.'/Repository';

            if (is_dir($repositoryDir) && is_writable($repositoryDir)) {
                $this->createFile($generator, $moduleDir.'/Contract/Repository/'.$module.'RepositoryInterface.php', 'rich/contract/repository/RepositoryInterface.tpl.php', [
                    'namespace' => sprintf('App\\Module\\%s\\Contract\\Repository', $module),
                    'class_name' => sprintf('%sRepositoryInterface', $module),
                ]);
            } else {
                $io->note(sprintf('Skipping the repository contract because the directory "%s" does not exist or is not writable.', $repositoryDir));
            }
        }

        // Exception
        $exceptionNamespace = sprintf('App\\Module\\%s\\Exception', $module);
        $exceptionInterfaceUseStatement = sprintf("use App\\Module\\%s\\Contract\\Exception\\ExceptionInterface;\n", $module);

        $this->createFile($generator, $moduleDir.'/Exception/InvalidArgumentException.php', 'rich/exception/InvalidArgumentException.tpl.php', [
            'namespace' => $exceptionNamespace,
            'use_statements' => $exceptionInterfaceUseStatement,
        ]);

        $this->createFile($generator, $moduleDir.'/Exception/RuntimeException.php', 'rich/exception/RuntimeException.tpl.php', [
            'namespace' => $exceptionNamespace,
            'use_statements' => $exceptionInterfaceUseStatement,
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text(sprintf('Next: Start building the "%s" module in "%s"!', $module, $moduleDir));
    }

    private function stringify(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function resolveModule(string $module): string
    {
        $resolved = (string) u($module)->pascal();
        $resolved = preg_replace('/[^a-zA-Z]/', '', $resolved);

        if (null === $resolved || '' === trim($resolved)) {
            throw new RuntimeCommandException(sprintf('The module name "%s" is not valid because it does not contain any letters.', $module));
        }

        return $resolved;
    }

    private function resolveSourceDirectory(string $srcDir): string
    {
        $srcDir = trim($srcDir);
        $srcDir = str_starts_with($srcDir, '/') ? $srcDir : getcwd().'/'.$srcDir;
        $srcDir = rtrim($srcDir, '/');

        if (!is_dir($srcDir) || !is_writable($srcDir)) {
            throw new RuntimeCommandException(sprintf('The source directory "%s" does not exist or is not writable.', $srcDir));
        }

        return $srcDir;
    }

    private function createDirectory(string $directory, ConsoleStyle $io): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeCommandException(sprintf('The directory "%s" could not be created.', $directory));
        }

        $io->comment(sprintf('<fg=blue>created</>: %s', $directory));
    }

    /**
     * @param array<string, string> $variables
     */
    private function createFile(Generator $generator, string $file, string $template, array $variables): void
    {
        if (file_exists($file)) {
            return;
        }

        $generator->generateFile($file, $this->templatePath($template), $variables);
    }

    private function templatePath(string $template): string
    {
        return sprintf('%s/templates/%s', dirname(__DIR__, 2), $template);
    }
}
