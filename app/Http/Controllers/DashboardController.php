<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->dashboardService->getStats();

        return response()->json([
            'success' => true,
            'data'    => $stats,
            'message' => 'Dashboard stats retrieved successfully.',
            'errors'  => [],
        ]);
    }
}
