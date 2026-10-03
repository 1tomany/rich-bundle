<?php

namespace OneToMany\RichBundle\Contract\Error\Record;

use Symfony\Component\Validator\ConstraintViolationInterface;

final readonly class Violation implements \JsonSerializable
{
    public function __construct(
        public string $property,
        public string $message,
    ) {
    }

    public static function create(ConstraintViolationInterface $violation): static
    {
        return new static($violation->getPropertyPath(), $violation->getMessage());
    }

    /**
     * @see \JsonSerializable
     *
     * @return array{
     *   property: string,
     *   message: string,
     * }
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * @return array{
     *   property: string,
     *   message: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'property' => $this->property,
            'message' => $this->message,
        ];
    }
}
