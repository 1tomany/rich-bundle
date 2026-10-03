<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
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
    public protected(set) \Throwable $throwable;
    public protected(set) ?self $previous = null;

    /**
     * @var int<100,599>
     */
    public protected(set) int $status = 500;

    /**
     * @var non-empty-string
     */
    public protected(set) string $title = 'Internal Server Error';

    /**
     * @var non-empty-string
     */
    public protected(set) string $message = self::MESSAGE_UNEXPECTED_ERROR;

    /**
     * @var array<string, string>
     */
    public protected(set) array $headers = [];

    /**
     * @var list<Violation>
     */
    public protected(set) array $violations = [];

    public const string MESSAGE_ACCESS_DENIED = 'Access to this resource is denied.';
    public const string MESSAGE_VALIDATION_FAILED = 'The data provided is not valid.';
    public const string MESSAGE_UNEXPECTED_ERROR = 'An unexpected error occurred.';

    public function __construct(
        \Throwable $throwable,
    ) {
        $this->throwable = $throwable;

        $this->resolveStatus();
        $this->resolveTitle();
        $this->resolveMessage();
        $this->resolveHeaders();
        $this->expandViolations();

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

            if ('' === $message = trim((string) $message)) {
                $message = self::MESSAGE_VALIDATION_FAILED;
            }
        } elseif ($this->throwable instanceof AccessDeniedException) {
            $message = self::MESSAGE_ACCESS_DENIED;
        } elseif (
            $this->throwable instanceof HttpExceptionInterface
            || $this->hasAttribute(WithHttpStatus::class)
            || $this->hasAttribute(HasUserMessage::class)
        ) {
            $message = $this->throwable->getMessage();
        }

        if ('' === $message = trim((string) $message)) {
            $message = self::MESSAGE_UNEXPECTED_ERROR;
        }

        $this->message = $message;
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

    protected function expandViolations(): void
    {
        $throwable = $this->throwable;

        while (null !== $throwable) {
            if ($throwable instanceof ValidationFailedException) {
                foreach ($throwable->getViolations() as $violation) {
                    $this->violations[] = Violation::create($violation);
                }
            }

            $throwable = $throwable->getPrevious();
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return ?T
     */
    protected function getAttribute(
        string $attributeClass,
    ): ?object {
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
    protected function hasAttribute(
        string $attributeClass,
    ): bool {
        return null !== $this->getAttribute($attributeClass);
    }
}
