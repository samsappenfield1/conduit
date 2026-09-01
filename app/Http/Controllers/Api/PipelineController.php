<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PipelineResource;
use App\Models\Pipeline;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PipelineController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PipelineResource::collection(Pipeline::all());
    }
}
