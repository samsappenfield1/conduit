<?php

namespace App\Observers;

use App\Models\Pipeline;
use RuntimeException;

class PipelineObserver
{
    public function creating(Pipeline $pipeline): void
    {
        if (Pipeline::count() >= 2) {
            throw new RuntimeException('Pipelines are limited to the two system-defined pipelines: Self serve and Enterprise.');
        }
    }

    public function deleting(Pipeline $pipeline): void
    {
        throw new RuntimeException('System-defined pipelines cannot be deleted.');
    }
}
