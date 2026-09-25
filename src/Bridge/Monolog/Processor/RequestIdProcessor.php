<?php

namespace OneToMany\RichBundle\Bridge\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OneToMany\RichBundle\EventListener\RequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function is_string;

final readonly class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @see Monolog\Processor\ProcessorInterface
     */
    #[\Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this
            ->requestStack
            ->getMainRequest();

        if ($request instanceof Request) {
            $requestId = $request->attributes->get(...[
                'key' => RequestListener::REQUEST_ID_KEY,
            ]);

            if (is_string($requestId) && '' !== $requestId) {
                $record->extra['requestId'] = $requestId;
            }
        }

        return $record;
    }
}
