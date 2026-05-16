<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\PdfService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use Tests\Traits\WithAuthUser;

class InvoiceTest extends TestCase
{
    use WithAuthUser;

    private $invoiceService;
    private $pdfService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = Mockery::mock(InvoiceService::class);
        $this->pdfService     = Mockery::mock(PdfService::class);

        $this->app->instance(InvoiceService::class, $this->invoiceService);
        $this->app->instance(PdfService::class, $this->pdfService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockExistsRule(): void
    {
        $presenceVerifier = Mockery::mock(\Illuminate\Validation\DatabasePresenceVerifierInterface::class);

        // Always return 1 (exists) for any validation check
        $presenceVerifier->shouldReceive('getCount')->andReturn(1);
        $presenceVerifier->shouldReceive('getMultiCount')->andReturn(1);
        $presenceVerifier->shouldReceive('setConnection')->andReturnNull();

        $this->app->make('validator')->setPresenceVerifier($presenceVerifier);
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function makeInvoice(array $attributes = []): Invoice
    {
        $invoice = new Invoice();

        $defaults = [
            'id'             => 1,
            'user_id'        => 'user-uuid-123',
            'client_id'      => 1,
            'invoice_number' => 'INV-2024-0001',
            'status'         => 'draft',
            'issue_date'     => '2024-01-01',
            'due_date'       => '2024-01-31',
            'subtotal'       => 100.00,
            'tax_rate'       => 20,
            'tax_amount'     => 20.00,
            'total'          => 120.00,
            'currency'       => 'EUR',
            'notes'          => null,
            'terms'          => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $invoice->$key = $value;
        }

        return $invoice;
    }

    private function fakePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $items,
            total: count($items),
            perPage: 15,
            currentPage: 1,
        );
    }

    private function notFoundException(): HttpResponseException
    {
        return new HttpResponseException(
            response()->json(['success' => false, 'message' => 'Invoice not found.'], 404)
        );
    }

    private function forbiddenException(string $message): HttpResponseException
    {
        return new HttpResponseException(
            response()->json(['success' => false, 'message' => $message], 403)
        );
    }

    // ==========================================
    // GET /api/v1/invoices — index()
    // ==========================================

    public function test_index_returns_invoice_list()
    {
        $this->invoiceService
            ->shouldReceive('listInvoices')
            ->once()
            ->andReturn($this->fakePaginator([
                $this->makeInvoice(['id' => 1]),
                $this->makeInvoice(['id' => 2]),
            ]));

        $response = $this->callAsUser('GET', '/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoices retrieved successfully.',
                'errors'  => [],
            ]);
    }

    public function test_index_returns_empty_list()
    {
        $this->invoiceService
            ->shouldReceive('listInvoices')
            ->once()
            ->andReturn($this->fakePaginator([]));

        $response = $this->callAsUser('GET', '/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_index_passes_filters_to_service()
    {
        $this->invoiceService
            ->shouldReceive('listInvoices')
            ->once()
            ->with(Mockery::on(function (array $filters) {
                return isset($filters['status']) && $filters['status'] === 'sent';
            }))
            ->andReturn($this->fakePaginator());

        $response = $this->callAsUser('GET', '/api/v1/invoices?status=sent');

        $response->assertStatus(200);
    }

    public function test_index_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/invoices');

        $response->assertStatus(401);
    }

    // ==========================================
    // GET /api/v1/invoices/{id} — show()
    // ==========================================

    public function test_show_returns_invoice()
    {
        $invoice = $this->makeInvoice(['id' => 1]);

        $this->invoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with(1)
            ->andReturn($invoice);

        $response = $this->callAsUser('GET', '/api/v1/invoices/1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice retrieved successfully.',
                'errors'  => [],
            ]);
    }

    public function test_show_returns_404_if_invoice_not_found()
    {
        $this->invoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with(99)
            ->andThrow($this->notFoundException());

        $response = $this->callAsUser('GET', '/api/v1/invoices/99');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invoice not found.',
            ]);
    }

    public function test_show_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/invoices/1');

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/v1/invoices — store()
    // ==========================================

    public function test_store_creates_invoice_successfully()
    {
        $this->mockExistsRule();

        $invoice = $this->makeInvoice();

        $this->invoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($invoice);

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/invoices', [
                'client_id'  => 1,
                'issue_date' => '2024-01-01',
                'due_date'   => '2024-01-31',
                'currency'   => 'EUR',
                'items'      => [
                    [
                        'description' => 'Service A',
                        'quantity'    => 1,
                        'unit_price'  => 100,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice created successfully.',
                'errors'  => [],
            ]);
    }

    public function test_store_fails_when_required_fields_missing()
    {
        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/invoices', []);

        $response->assertStatus(422);
    }

    public function test_store_returns_401_when_unauthenticated()
    {
        $response = $this->postJson('/api/v1/invoices', []);

        $response->assertStatus(401);
    }

    // ==========================================
    // PUT /api/v1/invoices/{id} — update()
    // ==========================================

    public function test_update_updates_invoice()
    {
        $this->mockExistsRule();

        $invoice = $this->makeInvoice(['currency' => 'USD']);

        $this->invoiceService
            ->shouldReceive('updateInvoice')
            ->once()
            ->with(1, Mockery::type('array'))
            ->andReturn($invoice);

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/invoices/1', [
                'currency' => 'USD',
                'items'    => [
                    [
                        'description' => 'Service A',
                        'quantity'    => 1,
                        'unit_price'  => 100,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice updated successfully.',
            ]);
    }

    public function test_update_returns_404_if_invoice_not_found()
    {
        $this->invoiceService
            ->shouldReceive('updateInvoice')
            ->once()
            ->andThrow($this->notFoundException());

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/invoices/99', ['currency' => 'USD']);

        $response->assertStatus(404);
    }

    public function test_update_returns_403_if_invoice_not_draft()
    {
        $this->invoiceService
            ->shouldReceive('updateInvoice')
            ->once()
            ->andThrow($this->forbiddenException('Only draft invoices can be edited.'));

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/invoices/1', ['currency' => 'USD']);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only draft invoices can be edited.']);
    }

    public function test_update_returns_401_when_unauthenticated()
    {
        $response = $this->putJson('/api/v1/invoices/1', []);

        $response->assertStatus(401);
    }

    // ==========================================
    // DELETE /api/v1/invoices/{id} — destroy()
    // ==========================================

    public function test_destroy_deletes_invoice()
    {
        $this->invoiceService
            ->shouldReceive('deleteInvoice')
            ->once()
            ->with(1);

        $response = $this->callAsUser('DELETE', '/api/v1/invoices/1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice deleted successfully.',
                'data'    => null,
            ]);
    }

    public function test_destroy_returns_404_if_invoice_not_found()
    {
        $this->invoiceService
            ->shouldReceive('deleteInvoice')
            ->once()
            ->andThrow($this->notFoundException());

        $response = $this->callAsUser('DELETE', '/api/v1/invoices/99');

        $response->assertStatus(404);
    }

    public function test_destroy_returns_403_if_invoice_not_draft()
    {
        $this->invoiceService
            ->shouldReceive('deleteInvoice')
            ->once()
            ->andThrow($this->forbiddenException('Only draft invoices can be deleted.'));

        $response = $this->callAsUser('DELETE', '/api/v1/invoices/1');

        $response->assertStatus(403);
    }

    public function test_destroy_returns_401_when_unauthenticated()
    {
        $response = $this->deleteJson('/api/v1/invoices/1');

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/v1/invoices/{id}/send — send()
    // ==========================================

    public function test_send_sends_invoice()
    {
        $invoice = $this->makeInvoice(['status' => 'sent']);

        $this->invoiceService
            ->shouldReceive('sendInvoice')
            ->once()
            ->with(1)
            ->andReturn($invoice);

        $response = $this->callAsUser('POST', '/api/v1/invoices/1/send');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice sent successfully.',
            ]);
    }

    public function test_send_returns_403_if_invoice_not_draft()
    {
        $this->invoiceService
            ->shouldReceive('sendInvoice')
            ->once()
            ->andThrow($this->forbiddenException('Only draft invoices can be sent.'));

        $response = $this->callAsUser('POST', '/api/v1/invoices/1/send');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only draft invoices can be sent.']);
    }

    public function test_send_returns_404_if_invoice_not_found()
    {
        $this->invoiceService
            ->shouldReceive('sendInvoice')
            ->once()
            ->andThrow($this->notFoundException());

        $response = $this->callAsUser('POST', '/api/v1/invoices/99/send');

        $response->assertStatus(404);
    }

    public function test_send_returns_401_when_unauthenticated()
    {
        $response = $this->postJson('/api/v1/invoices/1/send');

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/v1/invoices/{id}/pay — markAsPaid()
    // ==========================================

    public function test_mark_as_paid_marks_invoice_as_paid()
    {
        $invoice = $this->makeInvoice(['status' => 'paid']);

        $this->invoiceService
            ->shouldReceive('markAsPaid')
            ->once()
            ->with(1)
            ->andReturn($invoice);

        $response = $this->callAsUser('POST', '/api/v1/invoices/1/pay');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice marked as paid.',
            ]);
    }

    public function test_mark_as_paid_returns_403_if_already_paid()
    {
        $this->invoiceService
            ->shouldReceive('markAsPaid')
            ->once()
            ->andThrow($this->forbiddenException('Invoice is already paid.'));

        $response = $this->callAsUser('POST', '/api/v1/invoices/1/pay');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Invoice is already paid.']);
    }

    public function test_mark_as_paid_returns_404_if_invoice_not_found()
    {
        $this->invoiceService
            ->shouldReceive('markAsPaid')
            ->once()
            ->andThrow($this->notFoundException());

        $response = $this->callAsUser('POST', '/api/v1/invoices/99/pay');

        $response->assertStatus(404);
    }

    public function test_mark_as_paid_returns_401_when_unauthenticated()
    {
        $response = $this->postJson('/api/v1/invoices/1/pay');

        $response->assertStatus(401);
    }

    // ==========================================
    // GET /api/v1/invoices/{id}/download — download()
    // ==========================================

    public function test_download_returns_pdf()
    {
        $this->mockExistsRule();

        $invoice = Mockery::mock(Invoice::class)->makePartial();

        // Assign attributes
        $invoice->invoice_number = 'INV-2024-0001';
        $invoice->client         = new \stdClass();
        $invoice->items          = collect([]);

        // Mock load() on the same object returned by the service
        $invoice->shouldReceive('load')
            ->once()
            ->with(['client', 'items']);

        $this->invoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with(1)
            ->andReturn($invoice); // same object

        $this->pdfService
            ->shouldReceive('streamInvoicePdf')
            ->once()
            ->andReturn('%PDF-fake-content');

        $response = $this->callAsUser('GET', '/api/v1/invoices/1/download');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_download_returns_404_if_invoice_not_found()
    {
        $this->invoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->andThrow($this->notFoundException());

        $response = $this->callAsUser('GET', '/api/v1/invoices/99/download');

        $response->assertStatus(404);
    }

    public function test_download_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/invoices/1/download');

        $response->assertStatus(401);
    }
}
