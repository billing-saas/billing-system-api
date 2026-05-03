<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-3 months', 'now');
        $dueDate   = fake()->dateTimeBetween($issueDate, '+30 days');
        $subtotal  = fake()->randomFloat(2, 100, 5000);
        $taxRate   = fake()->randomElement([0, 10, 20]);
        $taxAmount = $subtotal * ($taxRate / 100);

        return [
            'user_id'        => 'test-user-uuid-1234',
            'client_id'      => Client::factory(),
            'invoice_number' => 'INV-' . fake()->unique()->numerify('####'),
            'status'         => fake()->randomElement(['draft', 'sent', 'paid', 'overdue']),
            'issue_date'     => $issueDate,
            'due_date'       => $dueDate,
            'paid_at'        => null,
            'subtotal'       => $subtotal,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'total'          => $subtotal + $taxAmount,
            'currency'       => 'USD',
            'notes'          => fake()->optional()->sentence(),
            'terms'          => fake()->optional()->sentence(),
        ];
    }
}
