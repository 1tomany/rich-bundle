<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use OneToMany\RichBundle\Contract\CommandInterface;

final readonly class <?php echo $class_name; ?> implements CommandInterface
{

    public function __construct()
    {
    }

}
