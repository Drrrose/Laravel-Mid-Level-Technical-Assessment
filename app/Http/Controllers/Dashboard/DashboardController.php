<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    #[OA\Get(
        path: '/dashboard',
        summary: 'Get dashboard statistics for authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Dashboard stats retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'total_projects', type: 'integer', example: 5),
                                new OA\Property(property: 'active_projects', type: 'integer', example: 3),
                                new OA\Property(property: 'total_tasks', type: 'integer', example: 12),
                                new OA\Property(property: 'completed_tasks', type: 'integer', example: 4),
                                new OA\Property(property: 'pending_tasks', type: 'integer', example: 6),
                                new OA\Property(property: 'overdue_tasks', type: 'integer', example: 2),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $stats = $this->dashboardService->getStatsForUser($request->user());

        return $this->successResponse($stats, 'Dashboard stats retrieved successfully');
    }
}
