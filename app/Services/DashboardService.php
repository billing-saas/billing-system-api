<?php

namespace App\Services;

use App\Helpers\AuthHelper;
use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        private DashboardRepository $dashboardRepository
    ) {}

    public function getStats(): array
    {
        $userId = AuthHelper::id();
        $year   = now()->year;

        return [
            'revenue' => [
                'this_month' => $this->dashboardRepository->getTotalRevenueThisMonth($userId),
                'total'      => $this->dashboardRepository->getTotalRevenue($userId),
                'by_month'   => $this->dashboardRepository->getRevenueByMonth($userId, $year),
            ],
            'invoices' => [
                'by_status'       => $this->dashboardRepository->getInvoiceCountByStatus($userId),
                'pending_amount'  => $this->dashboardRepository->getPendingAmount($userId),
                'overdue_amount'  => $this->dashboardRepository->getOverdueAmount($userId),
            ],
            'collection_rate' => $this->dashboardRepository->getCollectionRate($userId),
            'top_clients'     => $this->dashboardRepository->getTopClients($userId),
            'recent_invoices' => $this->dashboardRepository->getRecentInvoices($userId),
        ];
    }
}