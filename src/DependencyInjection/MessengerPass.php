<?php

namespace OneToMany\RichBundle\DependencyInjection;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\HandlerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\MessageBusInterface;

use function class_exists;
use function interface_exists;
use function is_subclass_of;
use function str_replace;

class MessengerPass implements CompilerPassInterface
{
    public const int PRIORITY = 2;

    public function process(ContainerBuilder $container): void
    {
        /** @disregard P1009 Undefined type */
        if (interface_exists(MessageBusInterface::class, false)) {
            foreach ($container->getDefinitions() as $definition) {
                $class = $definition->getClass();

                // Tag handlers to be used by Symfony Messenger
                if ($class && $this->isClassMessageHandler($class)) {
                    if (!$definition->hasTag('messenger.message_handler')) {
                        if ($command = $this->getHandlerCommandClass($class)) {
                            // Tag the handler for the Symfony Messenger
                            $definition->addTag('messenger.message_handler', [
                                'method' => 'handle', 'handles' => $command,
                            ]);

                            // Update the definition with the new tag
                            $container->setDefinition($class, $definition);
                        }
                    }
                }
            }
        }
    }

    private function isClassMessageHandler(string $class): bool
    {
        return class_exists($class, false) && is_subclass_of($class, HandlerInterface::class); // @see https://github.com/1tomany/rich-bundle/issues/11
    }

    /**
     * This attempts to generate the command class name based off
     * the handler class name. It takes the FQCN of the handler and
     * replaces the string Handler with Command. If the resulting
     * class exists and implements the CommandInterface interface,
     * it assumes that class is the command class for that handler.
     *
     * @return ?class-string<CommandInterface>
     */
    private function getHandlerCommandClass(string $class): ?string
    {
        // This is a quick-and-dirty way to generate the FQCN
        // for the command given the FQCN of the handler class
        $command = str_replace('Handler', 'Command', $class);

        // @see https://github.com/1tomany/rich-bundle/issues/11
        return class_exists($command, false) && is_subclass_of($command, CommandInterface::class) ? $command : null;
    }
}
