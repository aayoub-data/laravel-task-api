<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    /**
     * Display a listing of tasks.
     *
     * GET /api/tasks
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
                Task::STATUS_CANCELLED,
            ])],
            'priority' => ['nullable', Rule::in([
                Task::PRIORITY_LOW,
                Task::PRIORITY_MEDIUM,
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT,
            ])],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', Rule::in(['created_at', 'due_date', 'priority', 'title'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tasks = $this->taskService->listTasks(
            filters: $validated,
            perPage: $validated['per_page'] ?? 15,
            sortBy: $validated['sort_by'] ?? 'created_at',
            sortDirection: $validated['sort_direction'] ?? 'desc',
        );

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Store a newly created task.
     *
     * POST /api/tasks
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in([
                Task::PRIORITY_LOW,
                Task::PRIORITY_MEDIUM,
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT,
            ])],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after:today'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0.5', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ]);

        $task = $this->taskService->createTask($validated, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }

    /**
     * Display the specified task.
     *
     * GET /api/tasks/{id}
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getTask($id);

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    /**
     * Update the specified task.
     *
     * PUT /api/tasks/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in([
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
                Task::STATUS_CANCELLED,
            ])],
            'priority' => ['sometimes', Rule::in([
                Task::PRIORITY_LOW,
                Task::PRIORITY_MEDIUM,
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT,
            ])],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0.5', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $task = $this->taskService->updateTask($id, $validated, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $task,
        ]);
    }

    /**
     * Remove the specified task.
     *
     * DELETE /api/tasks/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->taskService->deleteTask($id, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * Mark a task as completed.
     *
     * POST /api/tasks/{id}/complete
     */
    public function complete(int $id): JsonResponse
    {
        $task = $this->taskService->completeTask($id, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Task marked as completed.',
            'data' => $task,
        ]);
    }

    /**
     * Assign a task to a user.
     *
     * POST /api/tasks/{id}/assign
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $task = $this->taskService->assignTask($id, $validated['assignee_id'], Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully.',
            'data' => $task,
        ]);
    }

    /**
     * Get task statistics for the authenticated user.
     *
     * GET /api/tasks/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->taskService->getUserStats(Auth::user());

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
