<?php

namespace OneToMany\RichBundle\Exception\Trait;

use function lcfirst;
use function rtrim;
use function sprintf;
use function strlen;
use function trim;

trait FormatReasonTrait
{
    private function formatReason(string|\Throwable|null $reason): string
    {
        if (!$reason) {
            return '';
        }

        if ($reason instanceof \Throwable) {
            $reason = $reason->getMessage();
        }

        $reason = trim($reason);

        if (!$reason) {
            return '';
        }

        $reasonLength = strlen($reason);

        while (true) {
            $reason = rtrim($reason, '.,!');
            $trimmedLength = strlen($reason);

            if ($reasonLength === $trimmedLength) {
                break;
            }

            $reasonLength = $trimmedLength;
        }

        // if ($reason = trim(rtrim((string) $reason, '.,!'))) {
        //     $reason = sprintf(': %s', lcfirst($reason));
        // }

        return sprintf(': %s', lcfirst($reason));
    }
}
