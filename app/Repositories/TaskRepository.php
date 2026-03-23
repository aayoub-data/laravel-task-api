<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepository
{
    public function __construct(
        protected Task $model
    ) {}

    /**
     * Get all tasks for a user with optional filtering.
     */
    public function getAllForUser(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId);

        if ($status) {
            $query->status($status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find a task by ID.
     */
    public function findById(int $id): ?Task
    {
        return $this->model->find($id);
    }

    /**
     * Find a task by ID that belongs to a specific user.
     */
    public function findByIdForUser(int $id, int $userId): ?Task
    {
        return $this->model->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        return $this->model->create($data);
    }

    /**
     * Update a task.
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh();
    }

    /**
     * Delete a task.
     */
    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Get overdue tasks for a user.
     */
    public function getOverdueForUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->where('due_date', '<', now())
            ->get();
    }
}
