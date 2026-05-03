<?php
namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();

        if ($clients->isEmpty()) {
            $this->command->warn('No clients found. Run ClientSeeder first.');
            return;
        }

        $clients->each(function ($client) {
            Invoice::factory(3)
                ->create([
                    'user_id'   => $client->user_id,
                    'client_id' => $client->id,
                ])
                ->each(function ($invoice) {
                    $invoice->items()->createMany(
                        \App\Models\InvoiceItem::factory(
                            fake()->numberBetween(1, 5)
                        )->make()->toArray()
                    );
                });
        });
    }
}
