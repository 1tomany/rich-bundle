<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use OneToMany\RichBundle\Contract\Action\CommandInterface;

final readonly class <?= $class_name; ?> implements CommandInterface
{
<?php if ($id_property): ?>
    public function __construct(
        public int $<?= $id_property; ?>,
    ) {
    }
<?php else: ?>
    public function __construct()
    {
    }
<?php endif; ?>
}
