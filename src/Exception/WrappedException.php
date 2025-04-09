<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function count;

final class WrappedException implements WrappedExceptionInterface
{
    private int $status = 500;
    private string $title = '';
    private string $message = '';

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
        $this->resolveStatus();
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

    private function resolveStatus(): void
    {
        $this->status = $this->exception->getCode();

        if ($this->exception instanceof HttpException) {
            $this->status = $this->exception->getStatusCode();
        }

        if ($this->exception instanceof ValidationFailedException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        }

        if (!isset(Response::$statusTexts[$this->status])) {
            $this->status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->resolveTitle();
    }

    private function resolveTitle(): void
    {
        // @phpstan-ignore-next-line
        $this->title = Response::$statusTexts[$this->status] ?? 'Unknown';
    }

    private function resolveMessage(): void
    {
        $this->message = 'An unexpected error occurred.';

        if ($this->exception instanceof HttpException) {
            $this->message = $this->exception->getMessage();
        }

        $refClass = new \ReflectionClass($this->exception);

        if (count($refClass->getAttributes(HasUserMessage::class))) {
            $this->message = $this->exception->getMessage();
        }

        if ($this->exception instanceof ValidationFailedException) {
            $this->message = 'The data provided is not valid.';
        }
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
}
