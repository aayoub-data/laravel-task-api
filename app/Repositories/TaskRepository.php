<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class TaskRepository
{
    public function __construct(
        protected Task $model
    ) {}

    /**
     * Get paginated tasks with optional filters.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['owner:id,name,email', 'assignee:id,name,email']);

        $this->applyFilters($query, $filters);

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Find a task by ID with relationships.
     */
    public function findById(int $id): ?Task
    {
        return $this->model->newQuery()
            ->with(['owner', 'assignee'])
            ->find($id);
    }

    /**
     * Find a task by ID or throw 404.
     */
    public function findOrFail(int $id): Task
    {
        return $this->model->newQuery()
            ->with(['owner', 'assignee'])
            ->findOrFail($id);
    }

    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        $task = $this->model->newQuery()->create($data);

        return $task->load(['owner', 'assignee']);
    }

    /**
     * Update an existing task.
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['owner', 'assignee']);
    }

    /**
     * Delete a task (soft delete).
     */
    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Get tasks assigned to a specific user.
     */
    public function getByAssignee(User $user, string $status = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('assignee_id', $user->id)
            ->with(['owner:id,name']);

        if ($status) {
            $query->withStatus($status);
        }

        return $query->orderBy('due_date', 'asc')->get();
    }

    /**
     * Get overdue tasks.
     */
    public function getOverdue(): Collection
    {
        return $this->model->newQuery()
            ->overdue()
            ->with(['owner:id,name', 'assignee:id,name'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get task statistics for a user.
     */
    public function getStatsForUser(int $userId): array
    {
        $base = $this->model->newQuery()->where('owner_id', $userId);

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->withStatus(Task::STATUS_PENDING)->count(),
            'in_progress' => (clone $base)->withStatus(Task::STATUS_IN_PROGRESS)->count(),
            'completed' => (clone $base)->withStatus(Task::STATUS_COMPLETED)->count(),
            'overdue' => (clone $base)->overdue()->count(),
        ];
    }

    /**
     * Apply filters to the query builder.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->withPriority($filters['priority']);
        }

        if (!empty($filters['assignee_id'])) {
            $query->assignedTo($filters['assignee_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['due_before'])) {
            $query->where('due_date', '<=', $filters['due_before']);
        }

        if (!empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
    }
}
