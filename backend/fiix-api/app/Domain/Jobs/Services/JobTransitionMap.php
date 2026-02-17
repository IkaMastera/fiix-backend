<?php

namespace App\Domain\Jobs\Services;

use App\Domain\Jobs\Enums\JobStatus;

final class JobTransitionMap
{
    /** @return array<string, array<string>> */
    public static function allowed(): array
    {
        return [
            JobStatus::SUBMITTED->value => [
                JobStatus::TRIAGED->value,
                JobStatus::CANCELLED->value,
            ],
            JobStatus::TRIAGED->value => [
                JobStatus::ASSIGNED->value,
                JobStatus::CANCELLED->value,
            ],
            JobStatus::ASSIGNED->value => [
                JobStatus::IN_PROGRESS->value,
                JobStatus::TRIAGED->value,
                JobStatus::CANCELLED->value,
            ],
            JobStatus::IN_PROGRESS->value => [
                JobStatus::BLOCKED->value,
                JobStatus::DONE->value,
            ],
            JobStatus::BLOCKED->value => [
                JobStatus::TRIAGED->value,
                JobStatus::CANCELLED->value,
            ],
            JobStatus::DONE->value => [
                JobStatus::DISPUTED->value,
            ],
            JobStatus::CANCELLED->value => [],
            JobStatus::DISPUTED->value => [],
        ];
    }

    public static function isAllowed(JobStatus $from, JobStatus $to): bool
    {
        $allowed = self::allowed()[$from->value] ?? [];
        return in_array($to->value, $allowed, true);
    }
}