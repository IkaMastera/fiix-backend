<?php

namespace App\Domain\Jobs\Services;

use App\Domain\Jobs\Enums\JobStatus;
use App\Domain\Jobs\Exceptions\InvalidJobTransition;
use App\Models\Job;
use App\Models\JobAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;