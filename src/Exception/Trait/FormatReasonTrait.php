<?php

namespace OneToMany\RichBundle\Exception\Trait;

use function lcfirst;
use function rtrim;
use function sprintf;
use function trim;

trait FormatReasonTrait
{
    private function formatReason(string|\Throwable|null $reason): string
    {
        if ($reason instanceof \Throwable) {
            $reason = $reason->getMessage();
        }

        if ($reason = trim(rtrim((string) $reason, '.'))) {
            $reason = sprintf(': %s', lcfirst($reason));
        }

        return $reason;
    }
}
