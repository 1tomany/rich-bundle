<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use OneToMany\RichBundle\Contract\ResultInterface;

final readonly class <?php echo $class_name; ?> implements ResultInterface
{

    public function __construct(public mixed $value)
    {
    }

}
