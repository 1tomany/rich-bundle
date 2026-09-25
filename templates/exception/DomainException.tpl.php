<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $exception_interface_full_class_name; ?>;

class <?= $class_name; ?> extends \DomainException implements ExceptionInterface
{
}
