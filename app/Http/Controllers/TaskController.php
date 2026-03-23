<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
            ])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $tasks = $this->taskService->listTasks(
            $request->user()->id,
            $validated['status'] ?? null,
            $validated['per_page'] ?? 15
        );

        return response()->json($tasks);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'nullable|integer|min:1|max:5',
            'due_date' => 'nullable|date|after:today',
            'status' => ['nullable', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
            ])],
        ]);

        $task = $this->taskService->createTask(
            $request->user()->id,
            $validated
        );

        return response()->json($task, 201);
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getTask($id, $request->user()->id);
        return response()->json($task);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'nullable|integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'status' => ['sometimes', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
            ])],
        ]);

        $task = $this->taskService->updateTask(
            $id,
            $request->user()->id,
            $validated
        );

        return response()->json($task);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->taskService->deleteTask($id, $request->user()->id);
        return response()->json(null, 204);
    }
}
