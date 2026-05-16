<?php

namespace Tests\Traits;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\UserProfile;
use Mockery;
use Mockery\MockInterface;

trait WithFakeModels
{
    /**
     * Crée un mock partiel d'Invoice
     */
    protected function makeInvoice(array $attributes = []): MockInterface
    {
        $invoice = Mockery::mock(Invoice::class)->makePartial();

        $defaults = [
            'id'                       => 1,
            'user_id'                  => 'user-uuid-123',
            'client_id'                => 1,
            'invoice_number'           => 'INV-2024-0001',
            'status'                   => 'draft',
            'issue_date'               => '2024-01-01',
            'due_date'                 => '2024-01-31',
            'subtotal'                 => 100.00,
            'tax_rate'                 => 10.0,
            'tax_amount'               => 10.00,
            'total'                    => 110.00,
            'currency'                 => 'USD',
            'notes'                    => null,
            'terms'                    => null,
            'paid_at'                  => null,
            'stripe_payment_intent_id' => null,
            'stripe_payment_url'       => null,
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $invoice->$key = $value;
        }

        return $invoice;
    }

    /**
     * Crée un mock partiel de Client
     */
    protected function makeClient(array $attributes = []): MockInterface
    {
        $client = Mockery::mock(Client::class)->makePartial();

        $defaults = [
            'id'    => 1,
            'name'  => 'Test Client',
            'email' => 'client@example.com',
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $client->$key = $value;
        }

        return $client;
    }

    /**
     * Crée un mock partiel de UserProfile
     */
    protected function makeUserProfile(array $attributes = []): UserProfile
    {
        $profile = new UserProfile();

        $defaults = [
            'id'           => 1,
            'user_id'      => 'user-uuid-123',
            'first_name'   => 'John',
            'last_name'    => 'Doe',
            'email'        => 'john@example.com',
            'phone'        => '+1234567890',
            'company_name' => 'Acme Corp',
            'tax_number'   => 'FR12345678901',
            'address'      => '123 Main Street',
            'city'         => 'Paris',
            'postal_code'  => '75001',
            'country'      => 'FR',
            'currency'     => 'EUR',
            'logo_path'    => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $profile->$key = $value;
        }

        return $profile;
    }

    /**
     * Crée une collection d'invoices
     */
    protected function makeInvoices(int $count = 3, array $attributes = []): array
    {
        $invoices = [];
        for ($i = 1; $i <= $count; $i++) {
            $invoices[] = $this->makeInvoice([
                'id' => $i,
                'invoice_number' => "INV-2024-000{$i}",
                ...$attributes,
            ]);
        }
        return $invoices;
    }

    /**
     * Crée une collection de clients
     */
    protected function makeClients(int $count = 3, array $attributes = []): array
    {
        $clients = [];
        for ($i = 1; $i <= $count; $i++) {
            $clients[] = $this->makeClient([
                'id'    => $i,
                'name'  => "Client {$i}",
                'email' => "client{$i}@example.com",
                ...$attributes,
            ]);
        }
        return $clients;
    }
}
