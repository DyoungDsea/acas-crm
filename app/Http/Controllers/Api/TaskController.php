<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with('assignedUser');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->boolean('mine')) {
            $query->where('assigned_to', $request->user()->id);
        }

        return TaskResource::collection($query->orderBy('due_at')->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreTaskRequest $request)
    {
        return new TaskResource(Task::create($request->validated())->load('assignedUser'));
    }

    public function show(Task $task)
    {
        return new TaskResource($task->load('assignedUser'));
    }

    public function update(StoreTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return new TaskResource($task->load('assignedUser'));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}
