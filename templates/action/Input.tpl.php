<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

// use App\Model\Action\Command\CreateModelCommand;
// use OneToMany\RichBundle\Attribute\SourceRequest;
// use OneToMany\RichBundle\Attribute\SourceSecurity;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use Symfony\Component\Validator\Constraints as Assert;

// use function OneToMany\RichBundle\Function\strlower;
// use function OneToMany\RichBundle\Function\strtrim;

/**
 * @implements InputInterface<<?php echo $command_class; ?>>
 */
final class <?php echo $class_name; ?> implements InputInterface
{

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[SourceSecurity]
        public string $username = '' {
            set(mixed $v) => strlower($v);
        },

        #[Assert\Length(min: 4, max: 48)]
        #[SourceRequest]
        public string $name = '' {
            set(mixed $v) => strtrim($v);
        },

        #[Assert\Length(min: 8, max: 256)]
        #[SourceRequest]
        public string $description = '' {
            set(mixed $v) => strtrim($v);
        },
    )
    {
    }

    public function toCommand(): CommandInterface
    {
        return new <?php echo $command_class; ?>(...[
            'username' => $this->username,
            'name' => $this->name,
            'description' => $this->description,
        ]);
    }

}
