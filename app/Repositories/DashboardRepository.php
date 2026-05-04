<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    public function getTotalRevenueThisMonth(string $userId): float
    {
        return Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');
    }

    public function getTotalRevenue(string $userId): float
    {
        return Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->sum('total');
    }

    public function getRevenueByMonth(string $userId, int $year): array
    {
        $results = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereYear('paid_at', $year)
            ->selectRaw('MONTH(paid_at) as month, SUM(total) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Remplir les 12 mois même si pas de données
        $months = array_fill(1, 12, 0);
        foreach ($results as $result) {
            $months[$result->month] = (float) $result->revenue;
        }

        return array_values($months);
    }

    public function getInvoiceCountByStatus(string $userId): array
    {
        $results = Invoice::where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'draft'   => $results->get('draft')?->count   ?? 0,
            'sent'    => $results->get('sent')?->count    ?? 0,
            'paid'    => $results->get('paid')?->count    ?? 0,
            'overdue' => $results->get('overdue')?->count ?? 0,
        ];
    }

    public function getPendingAmount(string $userId): float
    {
        return Invoice::where('user_id', $userId)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total');
    }

    public function getOverdueAmount(string $userId): float
    {
        return Invoice::where('user_id', $userId)
            ->where('status', 'overdue')
            ->sum('total');
    }

    public function getTopClients(string $userId, int $limit = 5): array
    {
        return Invoice::where('invoices.user_id', $userId)
            ->where('invoices.status', 'paid')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->selectRaw('clients.id, clients.name, clients.company_name, SUM(invoices.total) as total_revenue, COUNT(invoices.id) as invoice_count')
            ->groupBy('clients.id', 'clients.name', 'clients.company_name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getRecentInvoices(string $userId, int $limit = 5): array
    {
        return Invoice::where('user_id', $userId)
            ->with('client')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getCollectionRate(string $userId): float
    {
        $total = Invoice::where('user_id', $userId)
            ->whereIn('status', ['paid', 'sent', 'overdue'])
            ->count();

        if ($total === 0) return 0;

        $paid = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->count();

        return round(($paid / $total) * 100, 1);
    }
}
