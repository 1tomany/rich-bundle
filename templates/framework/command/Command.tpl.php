<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

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
        $io->error('Not implemented!');

        return Command::SUCCESS;
    }
}
