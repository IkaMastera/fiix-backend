<?php

namespace App\Domain\Jobs\Services;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Exceptions\InvalidJobTransition;
use App\Models\Job;
use App\Models\JobAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;

final class JobAssignmentService
{
    public function __construct(
        private readonly JobWorkflowService $workflow
    ) {}

    public function assign(Job $job, User $technician, User $actor): Job
    {
        return DB::transaction(function () use ($job, $technician, $actor) {

            $current = JobStatus::from($job->status);

            if (!in_array($actor->role, ['admin', 'operator'], true)) {
                throw new AuthorizationException("Only operator/admin can assign or reassign");
            }

            if ($current !== JobStatus::TRIAGED) {
                throw new InvalidJobTransition("Assign allowed only from TRIAGED. Current: {$current->value}", $current->value, JobStatus::ASSIGNED->value);
            }

            if ($technician->role !== 'technician') {
                throw new InvalidJobTransition("Assigned user must have TECHNICIAN role");
            }

            if ($job->activeAssignment()->exists()) {
                throw new InvalidJobTransition("Job already has an active assignment", $current->value, null);
            }

            JobAssignment::create([
                'job_id' => $job->id,
                'technician_user_id' => $technician->id,
                'assigned_by_user_id' => $actor->id,
                'is_active' => true,
            ]);

            return $this->workflow->transition(
                $job,
                JobStatus::ASSIGNED,
                $actor,
                'ASSIGNED',
                null,
                false // no nested transaction
            );
        });
    }

    public function reassign(
        Job $job,
        User $newTechnician,
        User $actor,
        ?string $reasonCode = null,
        ?string $reasonNote = null
    ): Job {
        return DB::transaction(function () use ($job, $newTechnician, $actor, $reasonCode, $reasonNote) {

            $current = JobStatus::from($job->status);

            if (!in_array($actor->role, ['admin', 'operator'], true)) {
                throw new AuthorizationException("Only operator/admin can assign or reassign");
            }

            if ($current !== JobStatus::ASSIGNED) {
                throw new InvalidJobTransition("Reassign allowed only from ASSIGNED. Current: {$current->value}", $current->value, JobStatus::TRIAGED->value);
            }

            if ($newTechnician->role !== 'technician') {
                throw new InvalidJobTransition("Assigned user must have TECHNICIAN role");
            }

            $active = $job->activeAssignment()->first();

            if (!$active) {
                throw new InvalidJobTransition("Cannot reassign: no active assignment found", $current->value, null);
            }

            if ($active->accepted_at !== null) {
                throw new InvalidJobTransition("Cannot reassign: assignment already accepted", $current->value, null);
            }

            $active->is_active = false;
            $active->deactivated_at = now();
            $active->deactivated_by_user_id = $actor->id;
            $active->deactivation_reason = 'reassigned';
            $active->save();

            $this->workflow->transition(
                $job,
                JobStatus::TRIAGED,
                $actor,
                $reasonCode ?? 'REASSIGN',
                $reasonNote,
                false
            );

            JobAssignment::create([
                'job_id' => $job->id,
                'technician_user_id' => $newTechnician->id,
                'assigned_by_user_id' => $actor->id,
                'is_active' => true,
            ]);

            return $this->workflow->transition(
                $job,
                JobStatus::ASSIGNED,
                $actor,
                'REASSIGNED',
                null,
                false
            );
        });
    }
}