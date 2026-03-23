<?php

namespace OneToMany\RichBundle\Exception\Trait;

use function lcfirst;
use function rtrim;
use function sprintf;
use function strlen;
use function trim;

trait FormatReasonTrait
{
    private function formatReason(
        string|\Throwable|null $reason,
        string $prefix = ':',
    ): string
    {
        if ($reason instanceof \Throwable) {
            $reason = $reason->getMessage();
        }

        if ($reason = trim((string) $reason)) {
            $reasonLength = strlen($reason);

            do {
                $reason = trim(rtrim($reason, '.,!;'));
                $trimmedReasonLength = strlen($reason);

                if ($reasonLength === $trimmedReasonLength) {
                    break;
                }

                $reasonLength = $trimmedReasonLength;
            } while (!empty($reason));
        }

        return $reason ? trim(implode(' ', [$prefix, lcfirst($reason)])) : '';
    }
}
