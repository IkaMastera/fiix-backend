<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Models\Job;
use Illuminate\Http\Request;

final class JobController extends Controller
{
    public function show(Request $request, string $jobId): JobResource
    {
        $job = Job::query()
            ->with(['activeAssignment'])
            ->findOrFail($jobId);

        return new JobResource($job);
    }
}