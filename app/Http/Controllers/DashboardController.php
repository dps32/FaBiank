<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function show(Request $request): View
    {
        $data = $this->dashboardService->build($request->user());

        $response = view('dashboard', $data);
        $response->withHeaders([
            'Cache-Control' => 'private, max-age=30',
            'ETag' => '"' . md5(json_encode($data)) . '"',
        ]);

        return $response;
    }

    public function state(Request $request): JsonResponse
    {
        return response()
            ->json($this->dashboardService->build($request->user()))
            ->withHeaders([
                'Cache-Control' => 'private, max-age=30',
            ]);
    }
}
