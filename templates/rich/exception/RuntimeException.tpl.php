<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
