<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
    }

    public function test_user_can_list_own_tasks(): void
    {
        Sanctum::actingAs($this->user);

        Task::factory()->count(3)->create(['user_id' => $this->user->id]);
        Task::factory()->count(2)->create(); // other user's tasks

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_task(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'A test task description',
            'priority' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Test Task']);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_own_task(): void
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_user_cannot_access_other_users_task(): void
    {
        Sanctum::actingAs($this->user);

        $otherTask = Task::factory()->create();

        $response = $this->getJson("/api/tasks/{$otherTask->id}");
        $response->assertStatus(422);
    }

    public function test_user_can_delete_own_task(): void
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        Sanctum::actingAs($this->user);

        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => Task::STATUS_COMPLETED,
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => Task::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/tasks?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
