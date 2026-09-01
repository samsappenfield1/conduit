<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FieldController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return FieldResource::collection(Field::all());
    }
}
