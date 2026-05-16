<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use Mockery;
use Tests\TestCase;
use Tests\Traits\WithAuthUser;

class DashboardTest extends TestCase
{
    use WithAuthUser;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboardService = Mockery::mock(DashboardService::class);
        $this->app->instance(DashboardService::class, $this->dashboardService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function fakeStats(array $overrides = []): array
    {
        return array_merge([
            'total_invoices'    => 10,
            'total_clients'     => 5,
            'total_revenue'     => 1500.00,
            'pending_invoices'  => 3,
            'overdue_invoices'  => 2,
            'paid_invoices'     => 5,
        ], $overrides);
    }

    // ==========================================
    // GET /api/v1/dashboard/stats
    // ==========================================

    public function test_stats_returns_statistics()
    {
        $this->dashboardService
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($this->fakeStats());

        $response = $this->callAsUser('GET', '/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard stats retrieved successfully.',
                'errors'  => [],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'data' => [
                    'total_invoices',
                    'total_clients',
                    'total_revenue',
                    'pending_invoices',
                    'overdue_invoices',
                    'paid_invoices',
                ],
            ]);
    }

    public function test_stats_returns_correct_values()
    {
        $stats = $this->fakeStats([
            'total_invoices' => 42,
            'total_revenue'  => 9999.99,
        ]);

        $this->dashboardService
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($stats);

        $response = $this->callAsUser('GET', '/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_invoices' => 42,
                    'total_revenue'  => 9999.99,
                ],
            ]);
    }

    public function test_stats_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(401);
    }

    public function test_stats_returns_500_if_service_fails()
    {
        $this->dashboardService
            ->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));

        $response = $this->callAsUser('GET', '/api/v1/dashboard/stats');

        $response->assertStatus(500);
    }
}
