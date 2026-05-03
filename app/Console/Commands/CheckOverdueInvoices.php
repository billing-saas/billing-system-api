<?php
namespace App\Console\Commands;

use App\Jobs\SendOverdueEmailJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature   = 'invoices:check-overdue';
    protected $description = 'Mark overdue invoices and send notification emails';

    public function handle(): void
    {
        $invoices = Invoice::where('status', 'sent')
            ->whereDate('due_date', '<', now())
            ->with(['client', 'items'])
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            $invoice->update(['status' => 'overdue']);
            SendOverdueEmailJob::dispatch($invoice);
            $count++;
        }

        $this->info("Marked {$count} invoices as overdue.");
    }
}
