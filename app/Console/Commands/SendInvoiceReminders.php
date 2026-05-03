<?php
namespace App\Console\Commands;

use App\Jobs\SendReminderEmailJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature   = 'invoices:send-reminders';
    protected $description = 'Send reminder emails for invoices due in 3 days';

    public function handle(): void
    {
        $invoices = Invoice::where('status', 'sent')
            ->whereDate('due_date', now()->addDays(3)->toDateString())
            ->with(['client', 'items'])
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            SendReminderEmailJob::dispatch($invoice);
            $count++;
        }

        $this->info("Sent {$count} reminder emails.");
    }
}
