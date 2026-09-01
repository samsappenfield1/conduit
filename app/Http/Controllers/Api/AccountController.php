<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\ActivityResource;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Activitylog\Models\Activity;

class AccountController extends Controller
{
    /**
     * Archived accounts are excluded automatically: Account's own
     * SoftDeletingScope already filters them out of every query here.
     */
    protected function baseQuery(): Builder
    {
        return Account::query()->with(['pipeline', 'owner', 'contacts', 'fieldValues.field']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = $this->baseQuery()
            ->when($request->filled('pipeline_id'), fn ($query) => $query->where('pipeline_id', $request->query('pipeline_id')))
            ->when($request->filled('current_stage'), fn ($query) => $query->where('current_stage', $request->query('current_stage')))
            ->get();

        return AccountResource::collection($accounts);
    }

    public function show(string $uuid): AccountResource
    {
        $account = $this->baseQuery()->where('uuid', $uuid)->firstOrFail();

        return new AccountResource($account);
    }

    public function activity(string $uuid): AnonymousResourceCollection
    {
        $account = Account::where('uuid', $uuid)->firstOrFail();

        $activities = Activity::forSubject($account)
            ->with('causer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return ActivityResource::collection($activities);
    }
}
