<?php

namespace App\Domain\Jobs\Services;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Exceptions\InvalidJobTransition;
use App\Models\Job;
use App\Models\JobAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class JobAssignmentService
{
    public function __construct(
        private readonly JobWorkflowService $workflow
    ) {}

    public function assign(Job $job, User $technician, User $actor): Job
    {
        return DB::transaction(function () use ($job, $technician, $actor) {

            $current = JobStatus::from($job->status);

            if (!in_array($actor->role, ['ADMIN', 'OPERATOR'], true)) {
                throw new InvalidJobTransition("Only operator/admin can assign or reassign");
            }

            if ($current !== JobStatus::TRIAGED) {
                throw new InvalidJobTransition("Assign allowed only from TRIAGED. Current: {$current->value}");
            }

            if ($technician->role !== 'TECHNICIAN') {
                throw new InvalidJobTransition("Assigned user must have TECHNICIAN role");
            }

            if ($job->activeAssignment()->exists()) {
                throw new InvalidJobTransition("Job already has an active assignment");
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

            if (!in_array($actor->role, ['ADMIN', 'OPERATOR'], true)) {
                throw new InvalidJobTransition("Only operator/admin can assign or reassign");
            }

            if ($current !== JobStatus::ASSIGNED) {
                throw new InvalidJobTransition("Reassign allowed only from ASSIGNED. Current: {$current->value}");
            }

            if ($newTechnician->role !== 'TECHNICIAN') {
                throw new InvalidJobTransition("Assigned user must have TECHNICIAN role");
            }

            $active = $job->activeAssignment()->first();

            if (!$active) {
                throw new InvalidJobTransition("Cannot reassign: no active assignment found");
            }

            if ($active->accepted_at !== null) {
                throw new InvalidJobTransition("Cannot reassign: assignment already accepted");
            }

            $active->is_active = false;
            $active->deactivated_at = now();
            $active->deactivated_by_user_id = $actor->id;
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