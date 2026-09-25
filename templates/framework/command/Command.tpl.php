<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $exception_full_class_name; ?>;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: '<?= $command_name; ?>',
)]
final readonly class <?= $class_name."\n"; ?>
{
    public function __construct()
    {
    }

    public function __invoke(
        SymfonyStyle $io,
    ): int {
        throw new <?= $exception_class_name; ?>('Not implemented!');

        return Command::SUCCESS;
    }
}
