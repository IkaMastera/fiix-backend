<?php

namespace App\Domain\Jobs\Policies;

use App\Models\User;
use App\Models\Job;
use App\Domain\Jobs\Enums\JobStatus;

class JobPolicy
{
    public function transition(User $user, Job $job, string $toStatus): bool
    {
        $to = JobStatus::from($toStatus);
        $from = JobStatus::from($job->status);

        // Admin and operator can perform any allowed transition
        if (in_array($user->role, ['admin', 'operator'], true)) {
            return true;
        }

        // Customer: cancellation only before IN_PROGRESS, dispute only from DONE
        if ($user->role === 'customer') {
            if ($to === JobStatus::CANCELLED) {
                return in_array($from, [JobStatus::SUBMITTED, JobStatus::TRIAGED, JobStatus::ASSIGNED], true)
                    && $job->customer_user_id === $user->id;
            }
            if ($to === JobStatus::DISPUTED) {
                return $from === JobStatus::DONE && $job->customer_user_id === $user->id;
            }
            return false;
        }

        // Technician only for assigned jobs
        $activeAssignment = $job->relationLoaded('activeAssignment')
            ? $job->activeAssignment
            : $job->activeAssignment()->first();

        $isAssignedToTech = $activeAssignment
            && (string) $activeAssignment->technician_user_id === (string) $user->id;

            if (!$isAssignedToTech) {
                return false;
            }

            if ($to === JobStatus::IN_PROGRESS) {
                return $from === JobStatus::ASSIGNED;
            }
            if ($to === JobStatus::DONE) {
                return $from === JobStatus::IN_PROGRESS;
            }
            if ($to === JobStatus::BLOCKED) {
                return $from === JobStatus::IN_PROGRESS;
            }

            return false;
        }

        return false;

    }
}