<?php

namespace OneToMany\RichBundle\Serializer\Normalizer;

use OneToMany\RichBundle\Security\User\UserId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class UserIdNormalizer implements DenormalizerInterface
{
    public const string USER_CLASS = 'user_class';
    public const string ID_PROPERTY = 'id_property';

    public function __construct(
        private readonly ?TokenStorageInterface $tokenStorage,
    )
    {
    }

    /**
     * @param string $data
     * @param class-string<UserId> $type
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): UserId
    {
        if (null === $this->tokenStorage) {
            throw new \RuntimeException('The property "%s" could not be extracted from the security token because the Symfony Security Bundle is not installed. Try running "composer require symfony/security-bundle".');
        }

        $user = $this->tokenStorage->getToken()?->getUser();

        if (null === $user) {
            throw new \RuntimeException('null user come on');
        }

        if (!isset($context[self::USER_CLASS]) || !\is_string($context[self::USER_CLASS])) {
            throw new \RuntimeException('no user_class context');
        }

        if (!isset($context[self::ID_PROPERTY]) || !\is_string($context[self::ID_PROPERTY])) {
            throw new \RuntimeException('no id_property context');
        }

        if (!\is_a($user, $context[self::USER_CLASS], true)) {
            throw new \RuntimeException('user isnt correct type');
        }

        $class = new \ReflectionClass($user);

        if (!$class->hasProperty($context[self::ID_PROPERTY])) {
            throw new \RuntimeException('id property doesnt exist');
        }

        $idProperty = $class->getProperty($context[self::ID_PROPERTY]);

        if ($idProperty->isPrivate()) {
            $idProperty->setAccessible(true);
        }

        $id = $idProperty->getValue($user);

        if (!\is_null($id) && !\is_int($id) && !\is_string($id)) {
            throw new \RuntimeException('id property is not null, int, or string');
        }

        return new UserId($id, $data);
    }

    /**
     * @param string $data
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return \is_string($data) && \is_a($type, UserId::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            UserId::class => true,
        ];
    }
}
