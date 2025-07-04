<?php

namespace OneToMany\RichBundle\Test\Constraint;

use OneToMany\RichBundle\Exception\Contract\Schema\WrappedExceptionSchema;
use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;

use function sprintf;

final class ResponseErrorDetail extends ResponseMatchesJsonSchema
{
    public function __construct(private readonly string $message)
    {
        parent::__construct(new WrappedExceptionSchema());
    }

    public function toString(): string
    {
        return sprintf('the "detail" property matches the message "%s"', $this->message);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function matches(mixed $response): bool
    {
        /** @var object{detail: non-empty-string} */
        $json = $this->validateSchema($response, true);

        return $json->detail === $this->message;
    }
}
