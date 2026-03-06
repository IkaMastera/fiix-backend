<?php

namespace App\Domain\Jobs\Policies;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function transition(User $user, Job $job, string $toStatus): bool
    {
        $to = JobStatus::from($toStatus);
        $from = JobStatus::from($job->status);

        // Admin and operator can perform any allowed transition
        if (in_array($user->role, [UserRole::ADMIN->value, UserRole::OPERATOR->value], true)) {
            return true;
        }

        // Customer: cancel only before IN_PROGRESS, dispute only from DONE
        if ($user->role === UserRole::CUSTOMER->value) {
            if ($to === JobStatus::CANCELLED) {
                return in_array($from, [
                    JobStatus::SUBMITTED,
                    JobStatus::TRIAGED,
                    JobStatus::ASSIGNED,
                ], true) && $job->customer_user_id === $user->id;
            }
            if ($to === JobStatus::DISPUTED) {
                return $from === JobStatus::DONE
                    && $job->customer_user_id === $user->id;
            }
            return false;
        }

        // Technician: only for jobs assigned to them
        if ($user->role === UserRole::TECHNICIAN->value) {

            // Use eager loaded relation if available, avoid extra DB query
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