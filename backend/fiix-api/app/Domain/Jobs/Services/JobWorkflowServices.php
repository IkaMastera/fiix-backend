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
        ?string $reasonNote = null
    ): Job {
        return DB::transaction(function () use ($job, $to, $actor, $reasonCode, $reasonNote) {

            $from = JobStatus::from($job->status);

            // Terminal states are read-only
            if ($from->isTerminal()) {
                throw new InvalidJobTransition("Job is terminal: {$from->value}");
            }

            // State machine
            if (!JobTransitionMap::isAllowed($from, $to)) {
                throw new InvalidJobTransition("Not allowed: {$from->value} -> {$to->value}");
            }

            // Authorization (Policy via Gate)
            if (Gate::denies('transition', [$job, $to->value])) {
                throw new InvalidJobTransition("Forbidden transition for this user");
            }

            // Special invariant: BLOCKED requires reason_code
            if ($to === JobStatus::BLOCKED && !$reasonCode) {
                throw new InvalidJobTransition("BLOCKED requires reason_code");
            }

            // Special invariant: Accept (ASSIGNED -> IN_PROGRESS)
            if ($to === JobStatus::IN_PROGRESS) {
                // Must be technician with ACTIVE assignment on this job
                $assignment = $job->activeAssignment()
                    ->where('technician_user_id', $actor->id)
                    ->first();

                if (!$assignment) {
                    throw new InvalidJobTransition("Accept requires ACTIVE assignment for this technician");
                }

                // Idempotency / double-accept protection
                if ($assignment->accepted_at !== null) {
                    // Already accepted, do not re-accept
                    // We still allow transition if job already IN_PROGRESS, but here from must be ASSIGNED.
                    throw new InvalidJobTransition("Assignment already accepted");
                }

                $assignment->accepted_at = now();
                $assignment->save();
            }

            // 1) update job current status
            $job->status = $to->value;
            $job->save();

            // 2) append immutable status history
            $job->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by_user_id' => $actor->id,
                'reason_code' => $reasonCode,
                'reason_note' => $reasonNote,
                'changed_at' => now(),
            ]);

            return $job->refresh();
        });
    }
}