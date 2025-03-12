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

final class MakeAction extends AbstractMaker
{

    public function __construct()
    {
    }

    public static function getCommandName(): string
    {
        return 'make:rich:action';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates the RICH action (input, command, handler, and result) classes';
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

        $action = 'Create' . $module;

        $actionNamespace = sprintf('%s\\Action', $module);
        $templateDirectory = __DIR__ . '/../../templates/action';

        // Create the Command class
        $commandClass = $generator->createClassNameDetails(
            $action, $actionNamespace . '\\Command', 'Command'
        );

        if (!class_exists($commandClass->getFullName())) {
            $templateName = vsprintf('%s/Command.tpl.php', [
                $templateDirectory,
            ]);

            $generator->generateClass($commandClass->getFullName(), $templateName);
        } else {
            $io->note(sprintf('The command class "%s" already exists, skipping.', $commandClass->getFullName()));
        }

        // Create the Input class
        $inputClass = $generator->createClassNameDetails(
            $action, $actionNamespace . '\\Input', 'Input'
        );

        if (!class_exists($inputClass->getFullName())) {
            $templateName = vsprintf('%s/Input.tpl.php', [
                $templateDirectory,
            ]);

            $generator->generateClass($inputClass->getFullName(), $templateName, [
                'command_class' => $commandClass->getFullName(),
                'command_class_name' => $commandClass->getShortName(),
            ]);
        } else {
            $io->note(sprintf('The input class "%s" already exists, skipping.', $inputClass->getFullName()));
        }

        $generator->writeChanges();
    }

}
