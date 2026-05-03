<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceRepository
{
    public function getAllByUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::where('user_id', $userId)
            ->with(['client', 'items']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas(
                        'client',
                        fn($q) =>
                        $q->where('name', 'like', "%{$search}%")
                    );
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findByIdAndUser(int $id, string $userId): ?Invoice
    {
        return Invoice::where('id', $id)
            ->where('user_id', $userId)
            ->with(['client', 'items'])
            ->first();
    }

    public function create(array $data, array $items): Invoice
    {
        $invoice = Invoice::create($data);
        $this->syncItems($invoice, $items);
        return $invoice->load(['client', 'items']);
    }

    public function update(Invoice $invoice, array $data, array $items = []): Invoice
    {
        $invoice->update($data);
        if (!empty($items)) {
            $this->syncItems($invoice, $items);
        }
        return $invoice->fresh(['client', 'items']);
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);
        return $invoice->fresh();
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->delete();
    }

    public function getOverdueInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::where('status', 'sent')
            ->whereDate('due_date', '<', now())
            ->get();
    }

    private function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();
        $invoice->items()->createMany($items);
    }
}
