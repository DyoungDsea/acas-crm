<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('user');

        if ($request->filled('subject_type') && $request->filled('subject_id')) {
            $query->where('subject_type', $request->string('subject_type'))
                ->where('subject_id', $request->string('subject_id'));
        }

        return ActivityResource::collection($query->latest('occurred_at')->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreActivityRequest $request)
    {
        $activity = Activity::create([...$request->validated(), 'user_id' => $request->user()->id]);

        return new ActivityResource($activity->load('user'));
    }

    public function show(Activity $activity)
    {
        return new ActivityResource($activity->load('user'));
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return response()->noContent();
    }
}
