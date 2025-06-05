<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function array_push;
use function implode;
use function intval;
use function is_string;
use function max;
use function min;
use function trim;

final readonly class WrappedException implements WrappedExceptionInterface
{
    public \Throwable $exception;

    /**
     * @var int<100, 599>
     */
    private int $status;

    /**
     * @var non-empty-string
     */
    private string $title;

    /**
     * @var non-empty-string
     */
    private string $message;

    /**
     * @var array<string, string>
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
        $this->exception = $exception;

        // Expand and Normalize Exception Values
        $this->status = $this->resolveStatus();
        $this->title = $this->resolveTitle();
        $this->message = $this->resolveMessage();
        $this->headers = $this->resolveHeaders();

        // Expand Stack Trace
        $exceptionStackTrace = [];

        while (null !== $exception) {
            array_push($exceptionStackTrace, [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $exception = $exception->getPrevious();
        }

        $this->stack = $exceptionStackTrace;

        // Expand Constraint Violations
        $constraintViolations = [];

        if ($this->exception instanceof ValidationFailedException) {
            foreach ($this->exception->getViolations() as $violation) {
                array_push($constraintViolations, [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ]);
            }
        }

        $this->violations = $constraintViolations;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return implode(' ', [$this->status, $this->title]);
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
        if ($this->exception instanceof HttpExceptionInterface || $this->hasAttribute(WithHttpStatus::class) || $this->hasAttribute(HasUserMessage::class)) {
            $message = $this->exception->getMessage();
        }

        return trim($message ?? '') ?: 'An unexpected error occurred.';
    }

    /**
     * @return array<string, string>
     */
    private function resolveHeaders(): array
    {
        if ($this->exception instanceof HttpExceptionInterface) {
            return $this->cleanHeaders($this->exception->getHeaders());
        }

        if ($withHttpStatus = $this->getAttribute(WithHttpStatus::class)) {
            return $this->cleanHeaders($withHttpStatus->headers);
        }

        return [];
    }

    /**
     * @param array<mixed> $headers
     *
     * @return array<string, string>
     */
    private function cleanHeaders(array $headers): array
    {
        $headersClean = [];

        foreach ($headers as $header => $value) {
            if (is_string($header) && is_string($value)) {
                $headersClean[$header] = trim($value);
            }
        }

        return $headersClean;
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
        $class = new \ReflectionClass($this->exception);

        do {
            if ($attributes = $class->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF)) {
                return $attributes[0]->newInstance();
            }
        } while ($class = $class->getParentClass());

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
