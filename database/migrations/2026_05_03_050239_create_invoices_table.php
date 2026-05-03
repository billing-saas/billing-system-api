<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue'])->default('draft');

            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();

            $table->decimal('subtotal',    10, 2)->default(0);
            $table->decimal('tax_rate',    5,  2)->default(0);
            $table->decimal('tax_amount',  10, 2)->default(0);
            $table->decimal('total',       10, 2)->default(0);

            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_payment_url')->nullable();

            $table->index('user_id');
            $table->index('status');
            $table->index('due_date');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
