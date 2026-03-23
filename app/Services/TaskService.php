<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        protected TaskRepository $taskRepository
    ) {}

    /**
     * List tasks for the authenticated user.
     */
    public function listTasks(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getAllForUser($userId, $status, $perPage);
    }

    /**
     * Create a new task for a user.
     */
    public function createTask(int $userId, array $data): Task
    {
        $data['user_id'] = $userId;
        $data['status'] = $data['status'] ?? Task::STATUS_PENDING;

        return $this->taskRepository->create($data);
    }

    /**
     * Get a specific task, ensuring it belongs to the user.
     *
     * @throws ValidationException
     */
    public function getTask(int $taskId, int $userId): Task
    {
        $task = $this->taskRepository->findByIdForUser($taskId, $userId);

        if (!$task) {
            throw ValidationException::withMessages([
                'task' => ['Task not found or access denied.'],
            ]);
        }

        return $task;
    }

    /**
     * Update an existing task.
     *
     * @throws ValidationException
     */
    public function updateTask(int $taskId, int $userId, array $data): Task
    {
        $task = $this->getTask($taskId, $userId);
        return $this->taskRepository->update($task, $data);
    }

    /**
     * Delete a task.
     *
     * @throws ValidationException
     */
    public function deleteTask(int $taskId, int $userId): bool
    {
        $task = $this->getTask($taskId, $userId);
        return $this->taskRepository->delete($task);
    }

    /**
     * Get overdue tasks for a user.
     */
    public function getOverdueTasks(int $userId): Collection
    {
        return $this->taskRepository->getOverdueForUser($userId);
    }
}
