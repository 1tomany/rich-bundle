<?php

namespace OneToMany\RichBundle\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

use function is_array;
use function json_decode;
use function sprintf;

final class ResponseViolationMessage extends Constraint
{
    public function __construct(
        private readonly string $property,
        private readonly string $message,
    ) {
    }

    public function toString(): string
    {
        return sprintf('the property "%s" has a violation "%s"', $this->property, $this->message);
    }

    /**
     * @param Response $response
     */
    protected function matches(mixed $response): bool
    {
        if (!$response instanceof Response) {
            throw new UnexpectedTypeException($response, Response::class);
        }

        $content = $response->getContent();

        if (!$content) {
            return false;
        }

        $json = json_decode($content, true);

        if (!is_array($json)) {
            return false;
        }

        $violations = $json['violations'] ?? null;

        if (!is_array($violations)) {
            return false;
        }

        $hasPropertyAndMessage = false;

        foreach ($violations as $v) {
            if (!is_array($v)) {
                continue;
            }

            if ($this->property === $v['property']) {
                if ($this->message === $v['message']) {
                    $hasPropertyAndMessage = true;
                }
            }
        }

        return $hasPropertyAndMessage;
    }

    /**
     * @param Response $response
     */
    protected function failureDescription($response): string
    {
        return $this->toString();
    }
}
