<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Services\JobAssignmentService;
use App\Domain\Jobs\Services\JobWorkflowService;
use App\Domain\Users\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\AssignJobRequest;
use App\Http\Requests\Operator\ReassignJobRequest;
use App\Http\Requests\Operator\TriageJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OperatorJobController extends Controller
{
    public function __construct(
        private readonly JobWorkflowService $workflow,
        private readonly JobAssignmentService $assignments
    ) {}

    // List All jobs (operator Queue)
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        // Only Operators and admins can see the full job queue
        if(!in_array($actor->role, [UserRole::OPERATOR->value, UserRole::ADMIN->value], true)) {
            return response()->json([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'Only operators and admins can view the job queue.',
                    'details' => (object) [],
                ]
            ], 403);
        }

        $jobs = Job::with(['activeAssignment'])
            // Filter by status if provided
            ->when($request->query('status'), fn($q, $status) => $q->where('status', $status))
            // Filter by city if provided 
            ->when($request->query('city_code'), fn($q, $city) => $q->where('city_code', $city))
            // Filter by urgency if provided (e.g. ?urgency=emergency)
            ->when($request->query('urgency'), fn($q, $urgency) => $q->where('urgency', $urgency))
            // Oldest first so urgent jobs don't get missed
            ->orderBy('created_at', 'asc')
            ->paginate(25);

        return response()->json([
                'data' => JobResource::collection($jobs),
                'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
                'last_page'    => $jobs->lastPage(),
            ],
        ], 200);
    }

    // Triage a job (Operator reviews + edits)
    public function triage(TriageJobRequest $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if (!in_array($actor->role, [UserRole::OPERATOR->value, UserRole::ADMIN->value], true)) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only operators can triage jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $job = Job::with(['activeAssignment'])->findOrFail($id);
        $data = $request->validated();

        // Apply any operator corrections before transitioning
        $updates = array_filter([
            'service_id'   => $data['service_id'] ?? null,
            'urgency'      => $data['urgency'] ?? null,
            'address_text' => $data['address_text'] ?? null,
            'lat'          => $data['lat'] ?? null,
            'lng'          => $data['lng'] ?? null,
            'reviewed_at'  => now(),
            'reviewed_by_user_id' => $actor->id,
        ], fn($v) => $v !== null);

        if (!empty($updates)) {
            $job->update($updates);
        }

        $job = $this->workflow->transition(
            $job,
            JobStatus::TRIAGED,
            $actor,
            'TRIAGED',
            $data['operator_notes'] ?? null
        );

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }

    // Assign a technician to a job
    public function assign(AssignJobRequest $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if (!in_array($actor->role, [UserRole::OPERATOR->value, UserRole::ADMIN->value], true)) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only operators can assign jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $job        = Job::with(['activeAssignment'])->findOrFail($id);
        $technician = User::findOrFail($request->validated()['technician_id']);

        $job = $this->assignments->assign($job, $technician, $actor);

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }

    // ─── Reassign a job to a different technician ────────────────────
    public function reassign(ReassignJobRequest $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if (!in_array($actor->role, [UserRole::OPERATOR->value, UserRole::ADMIN->value], true)) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only operators can reassign jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $job        = Job::with(['activeAssignment'])->findOrFail($id);
        $data       = $request->validated();
        $technician = User::findOrFail($data['technician_id']);

        $job = $this->assignments->reassign(
            $job,
            $technician,
            $actor,
            $data['reason_code'] ?? null,
            $data['reason_note'] ?? null
        );

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }
}