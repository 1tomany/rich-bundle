<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use <?php echo $command_class; ?>;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements InputInterface<<?php echo $command_class_name; ?>>
 */
final class <?php echo $class_name; ?> implements InputInterface
{

    public function __construct()
    {
    }

    public function toCommand(): CommandInterface
    {
        return new <?php echo $command_class_name; ?>();
    }

}
