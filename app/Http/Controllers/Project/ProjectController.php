<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(
        protected ProjectService $projectService
    ) {}

    #[OA\Get(
        path: '/projects',
        summary: 'List paginated projects for authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status (active, completed, archived)', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'completed', 'archived'])),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Number of items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Projects fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Projects retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);

        $projects = $this->projectService->getUserProjects($request->user(), $filters, $perPage);

        return $this->successResponse(
            ProjectResource::collection($projects)->response()->getData(true),
            'Projects retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/projects',
        summary: 'Create a new project',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Website Redesign'),
                    new OA\Property(property: 'description', type: 'string', example: 'Redesign main landing page and dashboard'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], example: 'active'),
                ]
            )
        ),
        tags: ['Projects'],
        responses: [
            new OA\Response(response: 201, description: 'Project created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject($request->user(), $request->validated());

        return $this->successResponse(
            new ProjectResource($project),
            'Project created successfully',
            Response::HTTP_CREATED
        );
    }

    #[OA\Get(
        path: '/projects/{id}',
        summary: 'View single project details',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Project details retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project Not Found'),
        ]
    )]
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return $this->successResponse(
            new ProjectResource($project),
            'Project retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/projects/{id}',
        summary: 'Update an existing project',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Updated Project Name'),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], example: 'completed'),
                ]
            )
        ),
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Project updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project Not Found'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $updatedProject = $this->projectService->updateProject($project, $request->validated());

        return $this->successResponse(
            new ProjectResource($updatedProject),
            'Project updated successfully'
        );
    }

    #[OA\Delete(
        path: '/projects/{id}',
        summary: 'Delete a project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Project ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Project deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project Not Found'),
        ]
    )]
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->deleteProject($project);

        return $this->successResponse(null, 'Project deleted successfully');
    }
}
