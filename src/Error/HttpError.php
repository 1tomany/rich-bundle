<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Attribute\HasErrorType;
use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Enum\ErrorType;
use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use Psr\Log\LogLevel;
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
    protected ErrorType $type;

    /** @var int<100, 599> */
    protected int $status = 500;

    /** @var non-empty-string */
    protected string $title = 'Internal Server Error';

    /** @var array<string, string> */
    protected array $headers = [];

    /** @var non-empty-string */
    protected string $message = 'An unexpected error occurred.';

    /** @var list<Violation> */
    protected array $violations = [];

    /** @var list<Stack> */
    protected array $stack = [];

    /** @var list<Trace> */
    protected array $trace = [];

    public function __construct(protected readonly \Throwable $throwable)
    {
        $this->resolveStatus();
        $this->resolveTitle();
        $this->resolveHeaders();
        $this->resolveMessage();
        $this->expandViolations();
        $this->flattenStack();
        $this->flattenTrace();
        $this->resolveType();
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return sprintf('(%s) %s', $this->getDescription(), $this->getMessage());
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }

    public function getType(): ErrorType
    {
        return $this->type;
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

    public function getLogLevel(): string
    {
        if ($this->getStatus() < 300) {
            return LogLevel::INFO;
        }

        if ($this->getStatus() < 400) {
            return LogLevel::NOTICE;
        }

        if ($this->getStatus() < 500) {
            return LogLevel::ERROR;
        }

        return LogLevel::CRITICAL;
    }

    public function hasUserMessage(): bool
    {
        return $this->hasAttribute(HasUserMessage::class);
    }

    protected function resolveStatus(): void
    {
        $status = (int) $this->throwable->getCode();

        if ($this->throwable instanceof ValidationFailedException) {
            $status = Response::HTTP_BAD_REQUEST;
        } elseif ($this->throwable instanceof HttpExceptionInterface) {
            $status = $this->throwable->getStatusCode();
        } elseif ($withHttpStatus = $this->getAttribute(WithHttpStatus::class)) {
            $status = $withHttpStatus->statusCode;
        }

        if (!array_key_exists($status, Response::$statusTexts)) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->status = max(100, min($status, 599));
    }

    protected function resolveTitle(): void
    {
        $this->title = (Response::$statusTexts[$this->status] ?? null) ?: $this->title;
    }

    protected function resolveType(): void
    {
        $hasErrorType = $this->getAttribute(...[
            'attributeClass' => HasErrorType::class,
        ]);

        if ($hasErrorType instanceof HasErrorType) {
            $this->type = $hasErrorType->type;
        } else {
            $this->type = ErrorType::create(...[
                'throwable' => $this->throwable,
                'httpStatus' => $this->status,
            ]);
        }
    }

    protected function resolveHeaders(): void
    {
        $headers = null;

        if ($this->throwable instanceof HttpExceptionInterface) {
            $headers = $this->throwable->getHeaders();
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

    protected function resolveMessage(): void
    {
        $message = null;

        if (
            $this->throwable instanceof ValidationFailedException
            || $this->throwable instanceof BadRequestHttpException
        ) {
            $message = 'The data provided is not valid.';
        } elseif (
            $this->throwable instanceof HttpExceptionInterface
            || $this->hasAttribute(WithHttpStatus::class)
            || $this->hasAttribute(HasUserMessage::class)
        ) {
            $message = $this->throwable->getMessage();
        }

        $this->message = trim($message ?? '') ?: $this->message;
    }

    protected function expandViolations(): void
    {
        $exception = $this->throwable;

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

    protected function flattenStack(): void
    {
        $exception = $this->throwable;

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

    protected function flattenTrace(): void
    {
        foreach ($this->throwable->getTrace() as $trace) {
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
    protected function getAttribute(string $attributeClass): ?object
    {
        $class = new \ReflectionClass($this->throwable);

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
    protected function hasAttribute(string $attributeClass): bool
    {
        return null !== $this->getAttribute($attributeClass);
    }
}
