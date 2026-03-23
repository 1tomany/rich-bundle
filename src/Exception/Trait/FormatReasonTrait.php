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
        if ($reason instanceof \Throwable) {
            $reason = $reason->getMessage();
        }

        if ($reason = trim((string) $reason)) {
            $reasonLength = strlen($reason);

            while (true) {
                $reason = trim(rtrim($reason, '.,!'));
                $trimmedLength = strlen($reason);

                if ($reasonLength === $trimmedLength) {
                    break;
                }

                if (!$reason) {
                    break;
                }

                $reasonLength = $trimmedLength;
            }
        }

        return $reason ? sprintf(': %s', lcfirst($reason)) : '';
    }
}
