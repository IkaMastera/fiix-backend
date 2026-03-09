<?php

namespace App\Http\Controllers\Technician;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Services\JobWorkflowService;
use App\Domain\Users\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Technician\BlockJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TechnicianJobController extends Controller
{
    public function __construct(
        private readonly JobWorkflowService $workflow
    ) {}

    // List all jobs assigned to this technician
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        // Only technicians can access this endpoint
        if ($actor->role !== UserRole::TECHNICIAN->value) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only technicians can access this endpoint.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        // Find all jobs where this technician has an active assignment
        $jobs = Job::with(['activeAssignment'])
            ->whereHas('activeAssignment', function ($q) use ($actor) {
                $q->where('technician_user_id', $actor->id)
                  ->where('is_active', true);
            })
            // Filter by status if provided (e.g. ?status=assigned)
            ->when($request->query('status'), fn($q, $status) => $q->where('status', $status))
            // Oldest first so technician sees most urgent jobs first
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

    // Accept a job (ASSIGNED → IN_PROGRESS) 
    public function accept(Request $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if ($actor->role !== UserRole::TECHNICIAN->value) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only technicians can accept jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $job = Job::with(['activeAssignment'])->findOrFail($id);

        $job = $this->workflow->transition(
            $job,
            JobStatus::IN_PROGRESS,
            $actor,
            'TECHNICIAN_ACCEPTED',
            null
        );

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }

    // Mark a job as done (IN_PROGRESS → DONE)
    public function done(Request $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if ($actor->role !== UserRole::TECHNICIAN->value) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only technicians can complete jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $job = Job::with(['activeAssignment'])->findOrFail($id);

        $job = $this->workflow->transition(
            $job,
            JobStatus::DONE,
            $actor,
            'TECHNICIAN_DONE',
            null
        );

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }

    // Block a job (IN_PROGRESS → BLOCKED)
    public function block(BlockJobRequest $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if ($actor->role !== UserRole::TECHNICIAN->value) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only technicians can block jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $job  = Job::with(['activeAssignment'])->findOrFail($id);
        $data = $request->validated();

        $job = $this->workflow->transition(
            $job,
            JobStatus::BLOCKED,
            $actor,
            $data['reason_code'],
            $data['reason_note'] ?? null
        );

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }
}