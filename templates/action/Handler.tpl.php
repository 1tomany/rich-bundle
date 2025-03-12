<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use App\Account\Contract\AccountRepositoryInterface;
use App\Model\Action\Command\CreateModelCommand;
use App\Model\Action\Result\ModelCreatedResult;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\HandlerInterface;
use OneToMany\RichBundle\Contract\ResultInterface;

/**
 * @implements HandlerInterface<<?php echo $command_class; ?>, <?php echo $result_class; ?>>
 */
final readonly class <?php echo $class_name; ?> implements HandlerInterface
{

    public function __construct()
    {
    }

    public function handle(CommandInterface $command): ResultInterface
    {
        return new <?php echo $result_class; ?>(null);
    }

}
