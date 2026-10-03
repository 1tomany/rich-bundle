<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use OneToMany\RichBundle\Contract\Error\Record\StackItem;
use OneToMany\RichBundle\Contract\Error\Record\TraceItem;
use OneToMany\RichBundle\Contract\Error\Record\Violation;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function array_key_exists;
use function is_string;
use function max;
use function min;
use function sprintf;
use function trim;

class HttpError implements HttpErrorInterface
{
    protected \Throwable $throwable;
    protected ?self $previous = null;

    /**
     * @var int<100,599>
     */
    protected int $status = 500;

    /**
     * @var non-empty-string
     */
    protected string $title = 'Internal Server Error';

    /**
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * @var non-empty-string
     */
    protected string $message = self::MESSAGE_UNEXPECTED_ERROR;

    /**
     * @var list<Violation>
     */
    protected array $violations = [];

    /**
     * @var list<StackItem>
     */
    protected array $stack = [];

    /**
     * @var list<TraceItem>
     */
    protected array $trace = [];

    public const string MESSAGE_ACCESS_DENIED = 'Access to this resource is denied.';
    public const string MESSAGE_VALIDATION_FAILED = 'The data provided is not valid.';
    public const string MESSAGE_UNEXPECTED_ERROR = 'An unexpected error occurred.';

    public function __construct(
        \Throwable $throwable,
    ) {
        $this->throwable = $throwable;

        $this->resolveStatus();
        $this->resolveTitle();
        $this->resolveHeaders();
        $this->resolveMessage();
        $this->expandViolations();
        $this->flattenStack();
        $this->flattenTrace();

        if ($previous = $throwable->getPrevious()) {
            $this->previous = new self($previous);
        }
    }

    /**
     * @see \Stringable
     *
     * @return non-empty-string
     */
    #[\Override]
    public function __toString(): string
    {
        return sprintf('[%s] %s', $this->getDescription(), $this->getMessage());
    }

    /**
     * @see \JsonSerializable
     *
     * @return array{
     *   status: int<100,599>,
     *   title: non-empty-string,
     *   message: non-empty-string,
     *   violations: list<Violation>,
     *   previous: ?HttpErrorInterface,
     * }
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->getStatus(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'violations' => $this->getViolations(),
            'previous' => $this->getPrevious(),
        ];
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getPrevious(): ?HttpErrorInterface
    {
        return $this->previous;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getDescription(): string
    {
        return sprintf('%d %s', $this->status, $this->title);
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
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

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getContext(): array
    {
        return [];
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
            $this->throwable instanceof BadRequestHttpException
            || $this->throwable instanceof ValidationFailedException
        ) {
            if ($this->throwable instanceof ValidationFailedException) {
                if (1 === $this->throwable->getViolations()->count()) {
                    $message = $this->throwable->getViolations()->get(0)->getMessage();
                }
            } else {
                $message = $this->throwable->getMessage();
            }

            $message = trim((string) $message) ?: self::MESSAGE_VALIDATION_FAILED;
        } elseif ($this->throwable instanceof AccessDeniedException) {
            $message = self::MESSAGE_ACCESS_DENIED;
        } elseif (
            $this->throwable instanceof HttpExceptionInterface
            || $this->hasAttribute(WithHttpStatus::class)
            || $this->hasAttribute(HasUserMessage::class)
        ) {
            $message = $this->throwable->getMessage();
        }

        $this->message = trim((string) $message) ?: self::MESSAGE_UNEXPECTED_ERROR;
    }

    protected function expandViolations(): void
    {
        $exception = $this->throwable;

        while (null !== $exception) {
            if ($exception instanceof ValidationFailedException) {
                foreach ($exception->getViolations() as $violation) {
                    $this->violations[] = Violation::create($violation);
                }
            }

            $exception = $exception->getPrevious();
        }
    }

    protected function flattenStack(): void
    {
        $exception = $this->throwable;

        while (null !== $exception) {
            $this->stack[] = StackItem::create(...[
                'throwable' => $exception,
            ]);

            $exception = $exception->getPrevious();
        }
    }

    protected function flattenTrace(): void
    {
        foreach ($this->throwable->getTrace() as $trace) {
            $this->trace[] = TraceItem::create($trace);
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
