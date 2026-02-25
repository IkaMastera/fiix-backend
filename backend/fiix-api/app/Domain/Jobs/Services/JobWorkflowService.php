<?php

namespace App\Domain\Jobs\Services;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Exceptions\InvalidJobTransition;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class JobWorkflowService
{
    public function transition(
        Job $job,
        JobStatus $to,
        User $actor,
        ?string $reasonCode = null,
        ?string $reasonNote = null,
        bool $useTransaction = true
    ): Job {

        $fn = function () use ($job, $to, $actor, $reasonCode, $reasonNote) {

            $from = JobStatus::from($job->status);

            // Terminal states are read-only
            if ($from->isTerminal()) {
                throw new InvalidJobTransition("Job is terminal: {$from->value}");
            }

            // State machine enforcement
            if (!JobTransitionMap::isAllowed($from, $to)) {
                throw new InvalidJobTransition("Not allowed: {$from->value} -> {$to->value}");
            }

            // Authorization enforcement (explicit actor)
            if (Gate::forUser($actor)->denies('transition', [$job, $to->value])) {
                throw new InvalidJobTransition("Forbidden transition for this user"); 
            }

            // BLOCKED requires reason (external cause only)
            if ($to === JobStatus::BLOCKED && !$reasonCode) {
                throw new InvalidJobTransition("BLOCKED requires reason_code");
            }

            // Accept invariant (ASSIGNED -> IN_PROGRESS)
            if ($to === JobStatus::IN_PROGRESS) {

                $assignment = $job->activeAssignment()
                    ->where('technician_user_id', $actor->id)
                    ->first();

                if (!$assignment) {
                    throw new InvalidJobTransition(
                        "Accept requires ACTIVE assignment for this technician"
                    );
                }

                if ($assignment->accepted_at !== null) {
                    throw new InvalidJobTransition(
                        "Assignment already accepted"
                    );
                }

                $assignment->accepted_at = now();
                $assignment->save();
            }

            // Update job status
            $job->status = $to->value;
            $job->save();

            // Immutable audit trail
            $job->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by_user_id' => $actor->id,
                'reason_code' => $reasonCode,
                'reason_note' => $reasonNote,
                'changed_at' => now(),
            ]);

            return $job->refresh();
        };

        return $useTransaction
            ? DB::transaction($fn)
            : $fn();
    }
}