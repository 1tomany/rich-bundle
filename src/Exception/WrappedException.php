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
    private string $message;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var list<array<string, string>>
     */
    private array $errors;

    /**
     * @var list<array<string, int|string>>
     */
    private array $stack;

    public function __construct(\Throwable $exception)
    {
        $this->resolveStatus($exception);
        $this->resolveMessage($exception);
        $this->resolveHeaders($exception);

        $this->expandErrors($exception);
        $this->expandStack($exception);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTitle(): string
    {
        /** @var non-empty-string */
        return Response::$statusTexts[$this->status];
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return list<array<string, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<array<string, int|string>>
     */
    public function getStack(): array
    {
        return $this->stack;
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

    private function expandErrors(\Throwable $exception): void
    {
        $this->errors = [];

        if ($exception instanceof ValidationFailedException) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($exception->getViolations() as $violation) {
                $this->errors[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
        }
    }

    private function expandStack(\Throwable $exception): void
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

}
