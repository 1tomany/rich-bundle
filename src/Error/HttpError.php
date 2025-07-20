<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function array_key_exists;
use function is_string;
use function max;
use function min;
use function sprintf;
use function trim;

/**
 * @phpstan-import-type Stack from HttpErrorInterface
 * @phpstan-import-type Trace from HttpErrorInterface
 * @phpstan-import-type Violation from HttpErrorInterface
 */
class HttpError implements HttpErrorInterface
{
    /**
     * @var int<100, 599>
     */
    private int $status = 500;

    /**
     * @var non-empty-string
     */
    private string $title = 'Internal Server Error';

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @var non-empty-string
     */
    private string $message = 'An unexpected error occurred.';

    /**
     * @var list<Violation>
     */
    private array $violations = [];

    /**
     * @var list<Stack>
     */
    private array $stack = [];

    /**
     * @var list<Trace>
     */
    private array $trace = [];

    public function __construct(private \Throwable $exception)
    {
        $this->resolveStatus();
        $this->resolveTitle();
        $this->resolveHeaders();
        $this->resolveMessage();
        $this->expandViolations();
        $this->flattenStack();
        $this->flattenTrace();
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
        return sprintf('%d %s', $this->status, $this->title);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getStack(): array
    {
        return $this->stack;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    private function resolveStatus(): void
    {
        $status = (int) $this->exception->getCode();

        if ($this->exception instanceof ValidationFailedException) {
            $status = Response::HTTP_BAD_REQUEST;
        } elseif ($this->exception instanceof HttpExceptionInterface) {
            $status = $this->exception->getStatusCode();
        } elseif ($withHttpStatus = $this->getAttribute(WithHttpStatus::class)) {
            $status = $withHttpStatus->statusCode;
        }

        if (!array_key_exists($status, Response::$statusTexts)) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->status = max(100, min($status, 599));
    }

    private function resolveTitle(): void
    {
        $this->title = (Response::$statusTexts[$this->status] ?? null) ?: $this->title;
    }

    private function resolveHeaders(): void
    {
        $headers = null;

        if ($this->exception instanceof HttpExceptionInterface) {
            $headers = $this->exception->getHeaders();
        } elseif ($withHttpStatus = $this->getAttribute(WithHttpStatus::class)) {
            $headers = $withHttpStatus->headers;
        }

        if (!$headers) {
            return;
        }

        foreach ($headers as $header => $value) {
            if (is_string($header) && is_string($value)) {
                $this->headers[$header] = trim($value);
            }
        }
    }

    private function resolveMessage(): void
    {
        $message = null;

        if (
            $this->exception instanceof ValidationFailedException
            || $this->exception instanceof BadRequestHttpException
        ) {
            $message = 'The data provided is not valid.';
        } elseif (
            $this->exception instanceof HttpExceptionInterface
            || $this->hasAttribute(WithHttpStatus::class)
            || $this->hasAttribute(HasUserMessage::class)
        ) {
            $message = $this->exception->getMessage();
        }

        $this->message = trim($message ?? '') ?: $this->message;
    }

    private function expandViolations(): void
    {
        $exception = $this->exception;

        while (null !== $exception) {
            if ($exception instanceof ValidationFailedException) {
                foreach ($exception->getViolations() as $violation) {
                    $this->violations[] = [
                        'property' => $violation->getPropertyPath(),
                        'message' => $violation->getMessage(),
                    ];
                }
            }

            $exception = $exception->getPrevious();
        }
    }

    private function flattenStack(): void
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

    private function flattenTrace(): void
    {
        foreach ($this->exception->getTrace() as $trace) {
            $this->trace[] = [
                'class' => $trace['class'] ?? null,
                'function' => $trace['function'] ?? null,
                'file' => $trace['file'] ?? null,
                'line' => $trace['line'] ?? null,
            ];
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
