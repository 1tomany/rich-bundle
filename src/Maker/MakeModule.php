<?php

namespace OneToMany\RichBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class MakeModule extends AbstractMaker
{

    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public static function getCommandName(): string
    {
        return 'make:rich:module';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a RICH module directory and initial contracts';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('module', InputArgument::REQUIRED, 'The name of the RICH module (e.g. <fg=yellow>Account</>)');

        $inputConfig->setArgumentAsNonInteractive('module');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $module = Validator::validateClassName(
            $input->getArgument('module')
        );

        $templateDirectory = __DIR__ . '/../../templates';
        $moduleNamespace = sprintf('%s\\Contract', $module);

        // Create the Doctrine repository interface contract
        $repositoryInterfaceClass = $generator->createClassNameDetails(
            $module, $moduleNamespace, 'RepositoryInterface'
        );

        try {
            // Ensure the DoctrineBundle is installed
            $doctrineBundleName = 'DoctrineBundle';

            if ($this->kernel->getBundle($doctrineBundleName)) {
                // App\<Module>\Contract\<Module>RepositoryInterface
                if (!interface_exists($repositoryInterfaceClass->getFullName())) {
                    $templateName = vsprintf('%s/module/Repository.tpl.php', [
                        $templateDirectory,
                    ]);

                    $generator->generateClass($repositoryInterfaceClass->getFullName(), $templateName);
                } else {
                    $io->note(sprintf('The repository interface "%s" already exists, skipping.', $repositoryInterfaceClass->getFullName()));
                }
            }
        } catch (\InvalidArgumentException $e) {
            $io->note(sprintf('The repository interface "%s" was not created because the bundle "%s" is not installed.', $repositoryInterfaceClass->getFullName(), $doctrineBundleName));
        }
        // Create the exception interface contract
        $exceptionClass = $generator->createClassNameDetails(
            'Exception', $moduleNamespace . '\\Exception', 'Interface'
        );

        // App\<Module>\Contract\Exception\ExceptionInterface
        if (!interface_exists($exceptionClass->getFullName())) {
            $templateName = vsprintf('%s/module/Exception.tpl.php', [
                $templateDirectory,
            ]);

            $generator->generateClass($exceptionClass->getFullName(), $templateName);
        } else {
            $io->note(sprintf('The exception interface "%s" already exists, skipping.', $exceptionClass->getFullName()));
        }

        $generator->writeChanges();

        // App\Repository\<Module>Repository
        $repositoryClass = $generator->createClassNameDetails(
            $module, 'Repository\\', 'Repository',
        );

        if (interface_exists($repositoryClass->getFullName())) {
            $io->writeln(sprintf('Update the repository "%s" to implement the interface "%s".', $repositoryClass->getFullName(), $repositoryInterfaceClass->getFullName()));
            $io->newLine();
        }
    }

}
