<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function count;

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
    private string $message = 'An unexpected error occurred.';

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

        $this->resolveMessage();
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

        if ($withHttpStatus = $this->getWithHttpStatus()) {
            $statusCode ??= $withHttpStatus->statusCode;
        }

        $statusCode ??= \intval($this->exception->getCode());

        if (!isset(Response::$statusTexts[$statusCode])) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return \max(100, \min($statusCode, 599));
    }

    /**
     * @return non-empty-string
     */
    private function resolveTitle(): string
    {
        // @phpstan-ignore-next-line
        return (Response::$statusTexts[$this->status] ?? null) ?: 'Error';
    }

    private function resolveMessage(): void
    {
        $message = null;

        if ($this->exception instanceof HttpException) {
            $message = $this->exception->getMessage();
        }

        $refClass = new \ReflectionClass($this->exception);

        if (count($refClass->getAttributes(HasUserMessage::class))) {
            $message = $this->exception->getMessage();
        }

        if ($this->exception instanceof ValidationFailedException) {
            $message = 'The data provided is not valid.';
        }

        $this->message = $message ?? $this->message;
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

    private function getWithHttpStatus(): ?WithHttpStatus
    {
        $attributes = new \ReflectionClass($this->exception)->getAttributes(
            WithHttpStatus::class, \ReflectionAttribute::IS_INSTANCEOF
        );

        if ($attribute = \array_shift($attributes)) {
            return $attribute->newInstance();
        }

        return null;
    }
}
