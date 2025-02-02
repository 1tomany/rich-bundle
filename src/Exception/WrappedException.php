<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class WrappedException
{

    private int $status;
    private string $title;
    private string $message;

    /**
     * @var array<string, scalar>
     */
    private array $headers;

    /**
     * @var list<array<string, int|string>>
     */
    private array $stack;

    /**
     * @var list<array<string, string>>
     */
    private array $violations;

    public function __construct(\Throwable $exception)
    {
        $this->resolveStatus($exception);
        $this->resolveMessage($exception);
        $this->resolveHeaders($exception);
        $this->normalizeStack($exception);
        $this->expandViolations($exception);
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

    /**
     * @return array<string, scalar>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return list<array<string, int|string>>
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * @return list<array<string, string>>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    private function resolveStatus(\Throwable $exception): void
    {
        $this->status = $exception->getCode();

        if ($exception instanceof HttpException) {
            $this->status = $exception->getStatusCode();
        }

        if ($exception instanceof ValidationFailedException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        }

        if (!isset(Response::$statusTexts[$this->status])) {
            $this->status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->resolveTitle();
    }

    private function resolveTitle(): void
    {
        $title = Response::$statusTexts[$this->status] ?? null;

        if (!is_string($title)) {
            $title = 'Unknown';
        }

        $this->title = $title;
    }

    private function resolveMessage(\Throwable $exception): void
    {
        $this->message = 'An unexpected error occurred.';

        if ($exception instanceof HttpException) {
            $this->message = $exception->getMessage();
        }

        $ref = new \ReflectionClass($exception);

        if (count($ref->getAttributes(HasUserMessage::class))) {
            $this->message = $exception->getMessage();
        }

        if ($exception instanceof ValidationFailedException) {
            $this->message = 'The data provided is not valid.';
        }
    }

    private function resolveHeaders(\Throwable $exception): void
    {
        $this->headers = [];

        if ($exception instanceof HttpException) {
            // @phpstan-ignore-next-line
            $this->headers = $exception->getHeaders();
        }
    }

    private function normalizeStack(\Throwable $exception): void
    {
        $this->stack = [];

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

    private function expandViolations(\Throwable $exception): void
    {
        $this->violations = [];

        if ($exception instanceof ValidationFailedException) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($exception->getViolations() as $violation) {
                $this->violations[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
        }
    }

}
