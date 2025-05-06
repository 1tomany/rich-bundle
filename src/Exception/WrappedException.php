<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function array_shift;
use function intval;
use function max;
use function min;
use function trim;

final class WrappedException implements WrappedExceptionInterface
{
    /**
     * @var int<100, 599>
     */
    private readonly int $status;

    /**
     * @var non-empty-string
     */
    private readonly string $title;

    /**
     * @var non-empty-string
     */
    private readonly string $message;

    /**
     * @var array<string, int|float|string>
     */
    private array $headers = [];

    /**
     * @var list<array<string, int|string>>
     */
    private array $stack = [];

    /**
     * @var list<array<string, string>>
     */
    private array $violations = [];

    public function __construct(private readonly \Throwable $exception)
    {
        $this->status = $this->resolveStatus();
        $this->title = $this->resolveTitle();
        $this->message = $this->resolveMessage();

        $this->resolveHeaders();
        $this->normalizeStack();
        $this->expandViolations();
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStack(): array
    {
        return $this->stack;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @return int<100, 599>
     */
    private function resolveStatus(): int
    {
        $statusCode = null;

        if ($this->exception instanceof ValidationFailedException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
        }

        if ($this->exception instanceof HttpExceptionInterface) {
            $statusCode ??= $this->exception->getStatusCode();
        }

        if ($withHttpStatus = $this->getAttribute(WithHttpStatus::class)) {
            $statusCode ??= $withHttpStatus->statusCode;
        }

        $statusCode ??= intval($this->exception->getCode());

        if (!isset(Response::$statusTexts[$statusCode])) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return max(100, min($statusCode, 599));
    }

    /**
     * @return non-empty-string
     */
    private function resolveTitle(): string
    {
        // @phpstan-ignore-next-line
        return (Response::$statusTexts[$this->status] ?? null) ?: 'Error';
    }

    /**
     * @return non-empty-string
     */
    private function resolveMessage(): string
    {
        $message = null;

        // Default Validation Failed Message
        if ($this->exception instanceof ValidationFailedException) {
            $message = 'The data provided is not valid.';
        }

        // HTTP Exceptions, or Exceptions With HasUserMessage or WithHttpStatus Attributes
        if ($this->exception instanceof HttpExceptionInterface || $this->hasAttribute(HasUserMessage::class) || $this->hasAttribute(WithHttpStatus::class)) {
            $message = $this->exception->getMessage();
        }

        return trim($message ?? '') ?: 'An unexpected error occurred.';
    }

    private function resolveHeaders(): void
    {
        if ($this->exception instanceof HttpException) {
            // @phpstan-ignore-next-line
            $this->headers = $this->exception->getHeaders();
        }
    }

    private function normalizeStack(): void
    {
        $exception = $this->exception;

        while (null !== $exception) {
            $this->stack[] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            $exception = $exception->getPrevious();
        }
    }

    private function expandViolations(): void
    {
        if ($this->exception instanceof ValidationFailedException) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($this->exception->getViolations() as $violation) {
                $this->violations[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return ?T
     */
    private function getAttribute(string $attributeClass): ?object
    {
        $attributes = new \ReflectionClass($this->exception)->getAttributes(
            $attributeClass, \ReflectionAttribute::IS_INSTANCEOF
        );

        if ($attribute = array_shift($attributes)) {
            return $attribute->newInstance();
        }

        return null;
    }

    /**
     * @param class-string $attributeClass
     */
    private function hasAttribute(string $attributeClass): bool
    {
        return null !== $this->getAttribute($attributeClass);
    }
}
