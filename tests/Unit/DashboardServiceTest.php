<?php

namespace Tests\Unit;

use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithMockeryCleanup;
use Tests\Traits\WithFakeModels;

class DashboardServiceTest extends TestCase
{
    use WithMockeryCleanup, WithFakeModels;

    private DashboardService $service;
    private MockInterface $dashboardRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Set authenticated user
        request()->merge([
            'auth_user' => [
                'sub'   => 'user-uuid-123',
                'email' => 'test@example.com',
            ]
        ]);

        $this->dashboardRepository = Mockery::mock(DashboardRepository::class);
        $this->service             = new DashboardService($this->dashboardRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // getStats()
    // ==========================================

    public function test_get_stats_returns_all_required_keys()
    {
        $this->dashboardRepository
            ->shouldReceive('getTotalRevenueThisMonth')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(1000.00);

        $this->dashboardRepository
            ->shouldReceive('getTotalRevenue')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(5000.00);

        $this->dashboardRepository
            ->shouldReceive('getRevenueByMonth')
            ->once()
            ->with('user-uuid-123', now()->year)
            ->andReturn([
                'January'   => 500,
                'February'  => 600,
                'March'     => 400,
            ]);

        $this->dashboardRepository
            ->shouldReceive('getInvoiceCountByStatus')
            ->once()
            ->with('user-uuid-123')
            ->andReturn([
                'draft'   => 2,
                'sent'    => 5,
                'paid'    => 8,
                'overdue' => 1,
            ]);

        $this->dashboardRepository
            ->shouldReceive('getPendingAmount')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(2500.00);

        $this->dashboardRepository
            ->shouldReceive('getOverdueAmount')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(500.00);

        $this->dashboardRepository
            ->shouldReceive('getCollectionRate')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(88.5);

        $this->dashboardRepository
            ->shouldReceive('getTopClients')
            ->once()
            ->with('user-uuid-123')
            ->andReturn($this->makeClients(3));

        $this->dashboardRepository
            ->shouldReceive('getRecentInvoices')
            ->once()
            ->with('user-uuid-123')
            ->andReturn($this->makeInvoices(5));

        // Execute
        $result = $this->service->getStats();

        // Assertions - Verify structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('invoices', $result);
        $this->assertArrayHasKey('collection_rate', $result);
        $this->assertArrayHasKey('top_clients', $result);
        $this->assertArrayHasKey('recent_invoices', $result);

        // Assertions - Verify revenue values
        $this->assertEquals(1000.00, $result['revenue']['this_month']);
        $this->assertEquals(5000.00, $result['revenue']['total']);
        $this->assertCount(3, $result['revenue']['by_month']);

        // Assertions - Verify invoice values
        $this->assertEquals(
            [
                'draft'   => 2,
                'sent'    => 5,
                'paid'    => 8,
                'overdue' => 1,
            ],
            $result['invoices']['by_status']
        );
        $this->assertEquals(2500.00, $result['invoices']['pending_amount']);
        $this->assertEquals(500.00, $result['invoices']['overdue_amount']);

        // Assertions - Verify other values
        $this->assertEquals(88.5, $result['collection_rate']);
        $this->assertCount(3, $result['top_clients']);
        $this->assertCount(5, $result['recent_invoices']);
    }

    public function test_get_stats_uses_current_year()
    {
        $currentYear = now()->year;

        $this->dashboardRepository
            ->shouldReceive('getTotalRevenueThisMonth')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getTotalRevenue')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getRevenueByMonth')
            ->with('user-uuid-123', $currentYear)
            ->once()
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getInvoiceCountByStatus')
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getPendingAmount')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getOverdueAmount')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getCollectionRate')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getTopClients')
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getRecentInvoices')
            ->andReturn([]);

        // Execute - This call should pass the current year
        $this->service->getStats();

        // Mockery checks getRevenueByMonth was called with the current year
        $this->assertTrue(true);
    }

    public function test_get_stats_passes_correct_user_id_to_all_methods()
    {
        $userId = 'user-uuid-123';

        // Each repository method must receive the correct user ID
        $this->dashboardRepository
            ->shouldReceive('getTotalRevenueThisMonth')
            ->with($userId)
            ->once()
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getTotalRevenue')
            ->with($userId)
            ->once()
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getRevenueByMonth')
            ->with($userId, \Mockery::any())
            ->once()
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getInvoiceCountByStatus')
            ->with($userId)
            ->once()
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getPendingAmount')
            ->with($userId)
            ->once()
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getOverdueAmount')
            ->with($userId)
            ->once()
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getCollectionRate')
            ->with($userId)
            ->once()
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getTopClients')
            ->with($userId)
            ->once()
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getRecentInvoices')
            ->with($userId)
            ->once()
            ->andReturn([]);

        // Execute
        $this->service->getStats();

        $this->assertTrue(true);
    }

    public function test_get_stats_handles_empty_results()
    {
        $this->dashboardRepository
            ->shouldReceive('getTotalRevenueThisMonth')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getTotalRevenue')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getRevenueByMonth')
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getInvoiceCountByStatus')
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getPendingAmount')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getOverdueAmount')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getCollectionRate')
            ->andReturn(0);

        $this->dashboardRepository
            ->shouldReceive('getTopClients')
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getRecentInvoices')
            ->andReturn([]);

        // Execute
        $result = $this->service->getStats();

        // Assertions - Verify structure is valid even with empty data
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['revenue']['this_month']);
        $this->assertCount(0, $result['top_clients']);
        $this->assertCount(0, $result['recent_invoices']);
    }

    public function test_get_stats_preserves_data_types()
    {
        $this->dashboardRepository
            ->shouldReceive('getTotalRevenueThisMonth')
            ->andReturn(1500.50);

        $this->dashboardRepository
            ->shouldReceive('getTotalRevenue')
            ->andReturn(5000.00);

        $this->dashboardRepository
            ->shouldReceive('getRevenueByMonth')
            ->andReturn(['Jan' => 100.50]);

        $this->dashboardRepository
            ->shouldReceive('getInvoiceCountByStatus')
            ->andReturn(['draft' => 2]);

        $this->dashboardRepository
            ->shouldReceive('getPendingAmount')
            ->andReturn(250.75);

        $this->dashboardRepository
            ->shouldReceive('getOverdueAmount')
            ->andReturn(100.25);

        $this->dashboardRepository
            ->shouldReceive('getCollectionRate')
            ->andReturn(92.5);

        $this->dashboardRepository
            ->shouldReceive('getTopClients')
            ->andReturn([]);

        $this->dashboardRepository
            ->shouldReceive('getRecentInvoices')
            ->andReturn([]);

        // Execute
        $result = $this->service->getStats();

        // Assertions - Types must be preserved
        $this->assertIsFloat($result['revenue']['this_month']);
        $this->assertIsFloat($result['revenue']['total']);
        $this->assertIsArray($result['revenue']['by_month']);
        $this->assertIsFloat($result['invoices']['pending_amount']);
        $this->assertIsFloat($result['collection_rate']);
    }
}
