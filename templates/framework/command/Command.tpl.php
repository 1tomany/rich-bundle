<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: '<?= $command_name; ?>',
    description: 'Displays <?= $humanized_domain; ?> details',
)]
final readonly class <?= $class_name."\n"; ?>
{
    public function __construct()
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('ID of the <?= $humanized_domain; ?> to read')] int $<?= $id_property; ?>,
    ): int {
        $io->warning('Not implemented!');

        return Command::SUCCESS;
    }
}
