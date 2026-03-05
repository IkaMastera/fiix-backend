<?php

namespace App\Http\Resources;

use App\Domain\Jobs\Enums\JobStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JobResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $actor = $request->user();

        // Normalize status using enum = single source of truth
        $statusEnum = JobStatus::from((string) $this->status);
        $status = $statusEnum->value;

        $isOperatorOrAdmin =
            $actor && in_array($actor->role, ['admin', 'operator'], true);

        $isCustomerOwner =
            $actor && (string) $this->customer_user_id === (string) $actor->id;

        $isTech = $actor && $actor->role === 'technician';

        $isJobInTechVisibleState = in_array(
            $statusEnum,
            [JobStatus::ASSIGNED, JobStatus::IN_PROGRESS],
            true
        );

        // IMPORTANT: Resources must not query the DB.
        // If activeAssignment isn't eager-loaded, we default to "not allowed".
        $isActiveAssignee = false;

        if ($isTech && $isJobInTechVisibleState) {
            if ($this->resource->relationLoaded('activeAssignment')) {
                $active = $this->activeAssignment;
                $isActiveAssignee =
                    $active && (string) $active->technician_user_id === (string) $actor->id;
            }
        }

        $canSeeSensitive =
            $isOperatorOrAdmin ||
            $isCustomerOwner ||
            ($isTech && $isJobInTechVisibleState && $isActiveAssignee);

        return [
            // Core identifiers
            'id' => (string) $this->id,
            'customer_user_id' => (string) $this->customer_user_id,

            // Service
            'service_id' => (string) $this->service_id,
            'original_service_id' => $this->original_service_id ? (string) $this->original_service_id : null,

            // State machine + priority
            'status' => $status,
            'urgency' => (string) $this->urgency,

            // Content
            'title' => $this->title,
            'description' => $this->description,

            // Location (sensitive for tech before assigned)
            'address_text' => $canSeeSensitive ? $this->address_text : null,
            'city_code' => (string) $this->city_code,

            // Contact snapshot (sensitive)
            'customer_phone_snapshot' => $canSeeSensitive ? $this->customer_phone_snapshot : null,
            'customer_email_snapshot' => $canSeeSensitive ? $this->customer_email_snapshot : null,

            // Assignment (only if eager-loaded)
            'active_assignment' => $this->whenLoaded('activeAssignment', function () {
                if (!$this->activeAssignment) {
                    return null;
                }

                return [
                    'id' => (string) $this->activeAssignment->id,
                    'technician_user_id' => (string) $this->activeAssignment->technician_user_id,
                    'is_active' => (bool) $this->activeAssignment->is_active,
                    'accepted_at' => $this->activeAssignment->accepted_at?->toIso8601String(),
                ];
            }),

            // Photos/media (ONLY when sensitive allowed, and only if eager-loaded)
            'photos' => ($canSeeSensitive)
                ? $this->whenLoaded('photos', function () {
                    return $this->photos->map(function ($p) {
                        return [
                            'id' => isset($p->id) ? (string) $p->id : null,
                            'url' => $p->url ?? ($p->path ?? null),
                        ];
                    })->values();
                })
                : null,

            // Operator metadata
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reviewed_by_user_id' => $this->reviewed_by_user_id ? (string) $this->reviewed_by_user_id : null,

            // Cancellation
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_by_user_id' => $this->cancelled_by_user_id ? (string) $this->cancelled_by_user_id : null,
            'cancel_reason' => $this->cancel_reason,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}