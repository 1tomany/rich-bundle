<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $command_full_class_name; ?>;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;

/**
 * @implements InputInterface<<?= $command_class_name; ?>>
 */
final readonly class <?= $class_name; ?> implements InputInterface
{
<?php if ($id_property): ?>
    public function __construct(
        public ?int $<?= $id_property; ?>,
    ) {
    }
<?php else: ?>
    public function __construct()
    {
    }
<?php endif; ?>

    /**
     * @see OneToMany\RichBundle\Contract\Action\InputInterface
     */
    #[\Override]
    public function toCommand(): CommandInterface
    {
<?php if ($id_property): ?>
        return new <?= $command_class_name; ?>((int) $this-><?= $id_property; ?>);
<?php else: ?>
        return new <?= $command_class_name; ?>();
<?php endif; ?>
    }
}
