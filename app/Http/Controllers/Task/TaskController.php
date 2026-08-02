<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(
        protected TaskService $taskService
    ) {}

    #[OA\Get(
        path: '/projects/{projectId}/tasks',
        summary: 'List paginated tasks for a project with optional filters',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status (todo, in_progress, done)', required: false, schema: new OA\Schema(type: 'string', enum: ['todo', 'in_progress', 'done'])),
            new OA\Parameter(name: 'priority', in: 'query', description: 'Filter by priority (low, medium, high)', required: false, schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search title by keyword', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tasks fetched successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project Not Found'),
        ]
    )]
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status', 'priority', 'search']);

        $tasks = $this->taskService->getProjectTasks($project, $filters, $perPage);

        return $this->successResponse(
            TaskResource::collection($tasks)->response()->getData(true),
            'Tasks retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/projects/{projectId}/tasks',
        summary: 'Create a new task in a project',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Setup CI/CD pipeline'),
                    new OA\Property(property: 'description', type: 'string', example: 'Configure GitHub actions workflow'),
                    new OA\Property(property: 'status', type: 'string', enum: ['todo', 'in_progress', 'done'], example: 'todo'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'high'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2026-12-31'),
                ]
            )
        ),
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Task created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $task = $this->taskService->createTask($project, $request->validated());

        return $this->successResponse(
            new TaskResource($task),
            'Task created successfully',
            Response::HTTP_CREATED
        );
    }

    #[OA\Get(
        path: '/projects/{projectId}/tasks/{taskId}',
        summary: 'View a single task details',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', description: 'Task ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task details retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Task Not Found'),
        ]
    )]
    public function show(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', [$task, $project]);

        return $this->successResponse(
            new TaskResource($task),
            'Task retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/projects/{projectId}/tasks/{taskId}',
        summary: 'Update an existing task',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Updated Task Title'),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['todo', 'in_progress', 'done'], example: 'done'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'high'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2026-12-31'),
                ]
            )
        ),
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', description: 'Task ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Task Not Found'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', [$task, $project]);

        $updatedTask = $this->taskService->updateTask($task, $request->validated());

        return $this->successResponse(
            new TaskResource($updatedTask),
            'Task updated successfully'
        );
    }

    #[OA\Delete(
        path: '/projects/{projectId}/tasks/{taskId}',
        summary: 'Soft delete a task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', description: 'Task ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Task Not Found'),
        ]
    )]
    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', [$task, $project]);

        $this->taskService->deleteTask($task);

        return $this->successResponse(null, 'Task deleted successfully');
    }
}
