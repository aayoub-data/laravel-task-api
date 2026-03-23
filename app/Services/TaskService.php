<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TaskService
{
    public function __construct(
        protected TaskRepository $taskRepository
    ) {}

    /**
     * List tasks with filters and pagination.
     */
    public function listTasks(
        array $filters = [],
        int $perPage = 15,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->taskRepository->getPaginated($filters, $perPage, $sortBy, $sortDirection);
    }

    /**
     * Get a single task by ID.
     */
    public function getTask(int $id): Task
    {
        return $this->taskRepository->findOrFail($id);
    }

    /**
     * Create a new task.
     */
    public function createTask(array $data, User $owner): Task
    {
        return DB::transaction(function () use ($data, $owner) {
            $taskData = array_merge($data, [
                'owner_id' => $owner->id,
                'status' => Task::STATUS_PENDING,
            ]);

            $task = $this->taskRepository->create($taskData);

            Log::info('Task created', [
                'task_id' => $task->id,
                'owner_id' => $owner->id,
                'title' => $task->title,
            ]);

            return $task;
        });
    }

    /**
     * Update an existing task.
     */
    public function updateTask(int $taskId, array $data, User $user): Task
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->authorizeTaskAccess($task, $user);

        return DB::transaction(function () use ($task, $data) {
            $updatedTask = $this->taskRepository->update($task, $data);

            Log::info('Task updated', [
                'task_id' => $task->id,
                'changes' => array_keys($data),
            ]);

            return $updatedTask;
        });
    }

    /**
     * Mark a task as completed.
     */
    public function completeTask(int $taskId, User $user): Task
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->authorizeTaskAccess($task, $user);

        if ($task->status === Task::STATUS_COMPLETED) {
            throw new InvalidArgumentException('Task is already completed.');
        }

        if ($task->status === Task::STATUS_CANCELLED) {
            throw new InvalidArgumentException('Cannot complete a cancelled task.');
        }

        $task->markCompleted();

        Log::info('Task completed', [
            'task_id' => $task->id,
            'completed_by' => $user->id,
        ]);

        return $task->fresh(['owner', 'assignee']);
    }

    /**
     * Assign a task to a user.
     */
    public function assignTask(int $taskId, int $assigneeId, User $currentUser): Task
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->authorizeTaskAccess($task, $currentUser);

        $assignee = User::findOrFail($assigneeId);

        if (!$assignee->is_active) {
            throw new InvalidArgumentException('Cannot assign task to an inactive user.');
        }

        $updatedTask = $this->taskRepository->update($task, [
            'assignee_id' => $assignee->id,
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        Log::info('Task assigned', [
            'task_id' => $task->id,
            'assignee_id' => $assignee->id,
            'assigned_by' => $currentUser->id,
        ]);

        return $updatedTask;
    }

    /**
     * Delete a task.
     */
    public function deleteTask(int $taskId, User $user): void
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->authorizeTaskAccess($task, $user);

        $this->taskRepository->delete($task);

        Log::info('Task deleted', [
            'task_id' => $taskId,
            'deleted_by' => $user->id,
        ]);
    }

    /**
     * Get task statistics for the current user.
     */
    public function getUserStats(User $user): array
    {
        return $this->taskRepository->getStatsForUser($user->id);
    }

    /**
     * Get overdue tasks.
     */
    public function getOverdueTasks(): Collection
    {
        return $this->taskRepository->getOverdue();
    }

    /**
     * Verify user has access to modify the task.
     */
    protected function authorizeTaskAccess(Task $task, User $user): void
    {
        if ($task->owner_id !== $user->id && $task->assignee_id !== $user->id) {
            throw new InvalidArgumentException(
                'You do not have permission to modify this task.'
            );
        }
    }
}
