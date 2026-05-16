<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getStats();

            return response()->json([
                'success' => true,
                'data'    => $stats,
                'message' => 'Dashboard stats retrieved successfully.',
                'errors'  => [],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Error occurred while retrieving dashboard stats.',
                'errors'  => [
                    'exception' => $e->getMessage(),
                ],
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
