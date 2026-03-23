<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;
    private MockInterface $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = Mockery::mock(TaskRepository::class);
        $this->taskService = new TaskService($this->taskRepository);
    }

    public function test_create_task_sets_default_status(): void
    {
        $userId = 1;
        $data = ['title' => 'New Task'];

        $expectedData = [
            'title' => 'New Task',
            'user_id' => $userId,
            'status' => Task::STATUS_PENDING,
        ];

        $task = new Task($expectedData);

        $this->taskRepository
            ->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn($task);

        $result = $this->taskService->createTask($userId, $data);

        $this->assertEquals('New Task', $result->title);
        $this->assertEquals(Task::STATUS_PENDING, $result->status);
    }

    public function test_get_task_throws_when_not_found(): void
    {
        $this->taskRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with(999, 1)
            ->andReturn(null);

        $this->expectException(ValidationException::class);

        $this->taskService->getTask(999, 1);
    }

    public function test_get_task_returns_task_when_found(): void
    {
        $task = new Task([
            'id' => 1,
            'title' => 'Found Task',
            'user_id' => 1,
        ]);

        $this->taskRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with(1, 1)
            ->andReturn($task);

        $result = $this->taskService->getTask(1, 1);

        $this->assertEquals('Found Task', $result->title);
    }

    public function test_delete_task_calls_repository(): void
    {
        $task = new Task(['id' => 1, 'title' => 'To Delete', 'user_id' => 1]);

        $this->taskRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with(1, 1)
            ->andReturn($task);

        $this->taskRepository
            ->shouldReceive('delete')
            ->once()
            ->with($task)
            ->andReturn(true);

        $result = $this->taskService->deleteTask(1, 1);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
