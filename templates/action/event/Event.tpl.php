<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

final readonly class <?= $class_name."\n"; ?>
{
    public function __construct(
        public int $<?= $id_property; ?>,
    ) {
    }
}
