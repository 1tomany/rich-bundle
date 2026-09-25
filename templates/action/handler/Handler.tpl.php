<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $command_full_class_name; ?>;
use <?= $exception_full_class_name; ?>;
use <?= $entity_full_class_name; ?>;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\HandlerInterface;
use OneToMany\RichBundle\Contract\Action\ResultInterface;

/**
 * @implements HandlerInterface<<?= $command_class_name; ?>, ResultInterface<<?= $entity_class_name; ?>>>
 */
final readonly class <?= $class_name; ?> implements HandlerInterface
{
    public function __construct()
    {
    }

    /**
     * @see OneToMany\RichBundle\Contract\Action\HandlerInterface
     */
    #[\Override]
    public function handle(CommandInterface $command): ResultInterface
    {
        throw new <?= $exception_class_name; ?>('Not implemented!');
    }
}
