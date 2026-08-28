<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
