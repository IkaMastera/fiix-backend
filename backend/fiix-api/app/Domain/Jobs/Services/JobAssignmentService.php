<?php

namespace App\Domain\Jobs\Services;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Exceptions\InvalidJobTransition;
use App\Domain\Users\Enums\UserRole;
use App\Models\Job;
use App\Models\JobAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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

            // Only operator/admin can assign
            if (!in_array($actor->role, [UserRole::ADMIN->value, UserRole::OPERATOR->value], true)) {
                throw new AuthorizationException("Only operator/admin can assign or reassign");
            }

            // Job must be TRIAGED before assignment
            if ($current !== JobStatus::TRIAGED) {
                throw new InvalidJobTransition(
                    "Assign allowed only from TRIAGED. Current: {$current->value}",
                    $current->value,
                    JobStatus::ASSIGNED->value
                );
            }

            // Target user must be a technician
            if ($technician->role !== UserRole::TECHNICIAN->value) {
                throw new InvalidJobTransition("Assigned user must have TECHNICIAN role");
            }

            // No double assignment
            if ($job->activeAssignment()->exists()) {
                throw new InvalidJobTransition(
                    "Job already has an active assignment",
                    $current->value,
                    null
                );
            }

            JobAssignment::create([
                'job_id'                => $job->id,
                'technician_user_id'    => $technician->id,
                'assigned_by_user_id'   => $actor->id,
                'assigned_at'           => now(),
                'is_active'             => true,
            ]);

            return $this->workflow->transition(
                $job,
                JobStatus::ASSIGNED,
                $actor,
                'ASSIGNED',
                null,
                false // already inside a transaction
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

            // Only operator/admin can reassign
            if (!in_array($actor->role, [UserRole::ADMIN->value, UserRole::OPERATOR->value], true)) {
                throw new AuthorizationException("Only operator/admin can assign or reassign");
            }

            // Job must be ASSIGNED to reassign
            if ($current !== JobStatus::ASSIGNED) {
                throw new InvalidJobTransition(
                    "Reassign allowed only from ASSIGNED. Current: {$current->value}",
                    $current->value,
                    JobStatus::TRIAGED->value
                );
            }

            // New target must be a technician
            if ($newTechnician->role !== UserRole::TECHNICIAN->value) {
                throw new InvalidJobTransition("Assigned user must have TECHNICIAN role");
            }

            $active = $job->activeAssignment()->first();

            // Must have an active assignment to reassign
            if (!$active) {
                throw new InvalidJobTransition(
                    "Cannot reassign: no active assignment found",
                    $current->value,
                    null
                );
            }

            // Cannot reassign after technician already accepted
            if ($active->accepted_at !== null) {
                throw new InvalidJobTransition(
                    "Cannot reassign: assignment already accepted",
                    $current->value,
                    null
                );
            }

            // Deactivate old assignment with reason
            $active->is_active = false;
            $active->deactivated_at = now();
            $active->deactivated_by_user_id = $actor->id;
            $active->deactivation_reason = $reasonCode ?? 'reassigned';
            $active->save();

            // Step back to TRIAGED
            $this->workflow->transition(
                $job,
                JobStatus::TRIAGED,
                $actor,
                $reasonCode ?? 'REASSIGN',
                $reasonNote,
                false
            );

            // Create new assignment
            JobAssignment::create([
                'job_id'                => $job->id,
                'technician_user_id'    => $newTechnician->id,
                'assigned_by_user_id'   => $actor->id,
                'assigned_at'           => now(),
                'is_active'             => true,
            ]);

            // Move to ASSIGNED again
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