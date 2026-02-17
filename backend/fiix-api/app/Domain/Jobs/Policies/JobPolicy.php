<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Job;
use App\Domain\Jobs\Enums\JobStatus;

class JobPolicy
{
    public function transition(User $user, Job $job, string $toStatus): bool
    {
        $to = JobStatus::from($toStatus);
        $from = JobStatus::from($job->status);

        //Refine This Later right now admin and operator can do ops transitions
        if(in_array($user->role, ['ADMIN', 'OPERATOR'], true)){
            return true;
        }

        //Customer Cancellation only before IN_PROGRESS
        if($user->role === 'USER'){
            if($to === JobStatus::CANCELLED){
                return in_array($from, [JobStatus::SUBMITTED, JobStatus::TRIAGED, JobStatus::ASSIGNED], true)
                && $job->customer_user_id === $user->id;
            }
            if($to === JobStatus::DISPUTED){
                return $from === JobStatus::DONE && $job->customer_user_id === $user->id;
            }
            return false;
        }

        // Technician only for assigned jobs
        if($user->role === 'TECHNICIAN'){
            $isAssignedToTech = $job->activeAssignment()
                ->where('technician_user_id', $user->id)
                ->exists();

                if(!$isAssignedToTech) return false;

                if($to === JobStatus::IN_PROGRESS) return $from === JobStatus::ASSIGNED;
                if ($to === JobStatus::DONE) return $from === JobStatus::IN_PROGRESS;
                if ($to === JobStatus::BLOCKED) return $from === JobStatus::IN_PROGRESS;

                return false;
        }

        return false;

    }
}