<?php

namespace OneToMany\RichBundle\Exception\Trait;

use function lcfirst;
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

        $reason = trim((string) $reason, " \t\n\r\0\x0B.,!;");

        return $reason ? trim(implode(' ', [$prefix, lcfirst($reason)])) : '';
    }
}
