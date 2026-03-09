<?php

namespace App\Http\Controllers;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Enums\JobUrgency;
use App\Domain\Jobs\Services\JobWorkflowService;
use App\Domain\Users\Enums\UserRole;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JobController extends Controller
{
    public function __construct(
        private readonly JobWorkflowService $workflow
    ) {}

    // -- Submit a new job (customer only) --
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id'   => ['required', 'uuid', 'exists:services,id'],
            'title'        => ['nullable', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:10'],
            'address_text' => ['required', 'string', 'min:5'],
            'city_code'    => ['required', 'in:tbilisi,batumi'],
            'urgency'      => ['required', 'in:low,normal,high,emergency'],
            'lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'lng'          => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $actor = $request->user();

        // Only Customers can submit jobs
        if ($actor->role !== UserRole::CUSTOMER->value) {
            return response()->json([
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'Only customers can submit jobs.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        // Customer must have a verified phone number before submitting
        if (!$actor->phone || !$actor->phone_verified_at) {
            return response()->json([
                'error' => [
                    'code'    => 'phone_not_verified',
                    'message' => 'You must verify your phone before submitting a job.',
                    'details' => (object) [],
                ],
            ], 403);
        }

        $service = Service::findOrFail($data['service_id']);

        $job = Job::create([
            'customer_user_id'       => $actor->id,
            'customer_phone_snapshot'=> $actor->phone,
            'customer_email_snapshot'=> $actor->email,
            'service_id'             => $service->id,
            'original_service_id'    => $service->id,
            'title'                  => $data['title'] ?? null,
            'description'            => $data['description'],
            'address_text'           => $data['address_text'],
            'city_code'              => $data['city_code'],
            'urgency'                => $data['urgency'],
            'lat'                    => $data['lat'] ?? null,
            'lng'                    => $data['lng'] ?? null,
            'location_source'        => isset($data['lat']) ? 'user_pin' : 'user_text',
            'location_accuracy'      => isset($data['lat']) ? 'precise' : 'approx',
            'status'                 => JobStatus::SUBMITTED->value,
        ]);

        // Write the first audit trail entry
        $job->statusHistory()->create([
            'from_status'        => 'none',
            'to_status'          => JobStatus::SUBMITTED->value,
            'changed_by_user_id' => $actor->id,
            'reason_code'        => null,
            'reason_note'        => null,
            'changed_at'         => now(),
        ]);

        return response()->json([
            'data' => new JobResource($job),
        ], 201);
    }

    // View a single job
    public function show(Request $request, string $id): JsonResponse
    {
        $job = Job::with(['activeAssignment'])->findOrFail($id);

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }

    // Cancel a job(customer)
    public function cancel(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $job = Job::with(['activeAssignment'])->findOrFail($id);
        $actor = $request->user();

        $job = $this->workflow->transition(
            $job,
            JobStatus::CANCELLED,
            $actor,
            'CUSTOMER_CANCELLED',
            $data['reason']
        );

        return response()->json([
            'data' => new JobResource($job),
        ], 200);
    }
}