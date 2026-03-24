<?php

namespace OneToMany\RichBundle\Exception\Trait;

use function lcfirst;
use function trim;

trait FormatReasonTrait
{
    private function formatReason(
        string|\Throwable|null $reason,
        string $prefix = ':',
    ): string {
        if (!$reason) {
            return '';
        }

        if ($reason instanceof \Throwable) {
            $reason = $reason->getMessage();
        }

        $reason = trim(trim((string) $reason), '.,!;');

        return $reason ? trim(implode(' ', [$prefix, lcfirst($reason)])) : '';
    }
}
