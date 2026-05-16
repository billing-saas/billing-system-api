<?php

namespace Tests\Unit\Services;

use App\Jobs\SendInvoiceEmailJob;
use App\Jobs\SendPaidEmailJob;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Services\InvoiceService;
use App\Services\StripeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithAuthUser;
use Illuminate\Support\Facades\Queue;

class InvoiceServiceTest extends TestCase
{
    use WithAuthUser, RefreshDatabase;

    private InvoiceService $service;
    private MockInterface $invoiceRepository;
    private MockInterface $stripeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setAuthUser();

        $this->invoiceRepository = Mockery::mock(InvoiceRepository::class);
        $this->stripeService     = Mockery::mock(StripeService::class);
        $this->service           = new InvoiceService(
            $this->invoiceRepository,
            $this->stripeService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function fakePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $items,
            total: count($items),
            perPage: 15,
            currentPage: 1,
        );
    }

    private function fakeInvoice(array $attributes = []): MockInterface
    {
        $invoice = Mockery::mock(Invoice::class)->makePartial();

        $defaults = [
            'id'         => 1,
            'status'     => 'draft',
            'tax_rate'   => 10,
            'client_id'  => 1,
            'issue_date' => '2024-01-01',
            'due_date'   => '2024-01-31',
            'currency'   => 'USD',
            'notes'      => null,
            'terms'      => null,
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $invoice->$key = $value;
        }

        return $invoice;
    }

    // ==========================================
    // listInvoices()
    // ==========================================

    public function test_list_invoices_without_filters()
    {
        $paginator = $this->fakePaginator([
            ['id' => 1, 'status' => 'draft'],
            ['id' => 2, 'status' => 'sent'],
        ]);

        $this->invoiceRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', [])
            ->andReturn($paginator);

        $result = $this->service->listInvoices();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
    }

    public function test_list_invoices_with_filters()
    {
        $filters   = ['status' => 'sent'];
        $paginator = $this->fakePaginator([['id' => 2, 'status' => 'sent']]);

        $this->invoiceRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', $filters)
            ->andReturn($paginator);

        $result = $this->service->listInvoices($filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result->items());
    }

    public function test_list_invoices_returns_empty_list()
    {
        $this->invoiceRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', [])
            ->andReturn($this->fakePaginator([]));

        $result = $this->service->listInvoices();

        $this->assertCount(0, $result->items());
        $this->assertEquals(0, $result->total());
    }

    // ==========================================
    // getInvoice()
    // ==========================================

    public function test_get_invoice_returns_invoice()
    {
        $invoice = $this->fakeInvoice();

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(1, 'user-uuid-123')
            ->andReturn($invoice);

        $result = $this->service->getInvoice(1);

        $this->assertSame($invoice, $result);
    }

    public function test_get_invoice_throws_exception_if_not_found()
    {
        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(99, 'user-uuid-123')
            ->andReturn(null);

        $this->expectException(HttpResponseException::class);

        $this->service->getInvoice(99);
    }

    // ==========================================
    // createInvoice()
    // ==========================================

    public function test_create_invoice_creates_and_returns_invoice()
    {
        $invoice = $this->fakeInvoice();
        $data    = [
            'client_id'  => 1,
            'issue_date' => '2024-01-01',
            'due_date'   => '2024-01-31',
            'tax_rate'   => 20,
            'currency'   => 'EUR',
            'items'      => [
                ['description' => 'Service A', 'quantity' => 2, 'unit_price' => 100],
            ],
        ];

        $capturedData = [];

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(function (array $invoiceData) use (&$capturedData) {
                    $capturedData = $invoiceData;
                    return true;
                }),
                Mockery::type('array')
            )
            ->andReturn($invoice);

        $result = $this->service->createInvoice($data);

        $this->assertSame($invoice, $result);
        $this->assertEquals('user-uuid-123', $capturedData['user_id']);
        $this->assertEquals('draft', $capturedData['status']);
        $this->assertEquals(200.0, $capturedData['subtotal']);
        $this->assertEquals(40.0, $capturedData['tax_amount']);
        $this->assertEquals(240.0, $capturedData['total']);
    }

    public function test_create_invoice_calculates_totals_without_tax()
    {
        $invoice      = $this->fakeInvoice();
        $capturedData = [];

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(function (array $data) use (&$capturedData) {
                    $capturedData = $data;
                    return true;
                }),
                Mockery::type('array')
            )
            ->andReturn($invoice);

        $this->service->createInvoice([
            'client_id'  => 1,
            'issue_date' => '2024-01-01',
            'due_date'   => '2024-01-31',
            'tax_rate'   => 0,
            'items'      => [
                ['description' => 'Item', 'quantity' => 3, 'unit_price' => 50],
            ],
        ]);

        $this->assertEquals(150.0, $capturedData['subtotal']);
        $this->assertEquals(0.0, $capturedData['tax_amount']);
        $this->assertEquals(150.0, $capturedData['total']);
    }

    public function test_create_invoice_without_items_has_zero_totals()
    {
        $invoice      = $this->fakeInvoice();
        $capturedData = [];

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(function (array $data) use (&$capturedData) {
                    $capturedData = $data;
                    return true;
                }),
                Mockery::type('array')
            )
            ->andReturn($invoice);

        $this->service->createInvoice([
            'client_id'  => 1,
            'issue_date' => '2024-01-01',
            'due_date'   => '2024-01-31',
        ]);

        $this->assertEquals(0.0, $capturedData['subtotal']);
        $this->assertEquals(0.0, $capturedData['tax_amount']);
        $this->assertEquals(0.0, $capturedData['total']);
    }

    // ==========================================
    // updateInvoice()
    // ==========================================

    public function test_update_invoice_updates_invoice()
    {
        $invoice = $this->fakeInvoice(['status' => 'draft']);
        $updated = $this->fakeInvoice(['status' => 'draft']);
        $data    = ['currency' => 'EUR', 'items' => []];

        $invoice->shouldReceive('isDraft')->once()->andReturn(true);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($updated);

        $result = $this->service->updateInvoice(1, $data);

        $this->assertSame($updated, $result);
    }

    public function test_update_invoice_throws_exception_if_not_draft()
    {
        $invoice = $this->fakeInvoice(['status' => 'sent']);
        $invoice->shouldReceive('isDraft')->once()->andReturn(false);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository->shouldNotReceive('update');

        $this->expectException(HttpResponseException::class);

        $this->service->updateInvoice(1, ['currency' => 'EUR']);
    }

    public function test_update_invoice_throws_exception_if_not_found()
    {
        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn(null);

        $this->invoiceRepository->shouldNotReceive('update');

        $this->expectException(HttpResponseException::class);

        $this->service->updateInvoice(99, []);
    }

    // ==========================================
    // sendInvoice()
    // ==========================================

    public function test_send_invoice_sends_invoice_and_dispatches_job()
    {
        Queue::fake();

        $invoice = $this->fakeInvoice(['status' => 'draft']);
        $invoice->shouldReceive('isDraft')->once()->andReturn(true);
        $invoice->shouldReceive('load')->once()->with(['client', 'items']);
        $invoice->shouldReceive('update')->once()->with(Mockery::type('array'));

        $session               = new \Stripe\Checkout\Session();
        $session->payment_intent = 'pi_fake_123';
        $session->url            = 'https://stripe.com/pay/fake';

        $this->stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->with($invoice)
            ->andReturn($session);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($invoice, 'sent')
            ->andReturn($invoice);

        $result = $this->service->sendInvoice(1);

        $this->assertSame($invoice, $result);
        Queue::assertPushed(SendInvoiceEmailJob::class);
    }

    public function test_send_invoice_throws_exception_if_not_draft()
    {
        Queue::fake();

        $invoice = $this->fakeInvoice(['status' => 'sent']);
        $invoice->shouldReceive('isDraft')->once()->andReturn(false);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->stripeService->shouldNotReceive('createCheckoutSession');
        $this->invoiceRepository->shouldNotReceive('updateStatus');

        $this->expectException(HttpResponseException::class);

        $this->service->sendInvoice(1);

        Queue::assertNotPushed(SendInvoiceEmailJob::class);
    }

    // ==========================================
    // markAsPaid()
    // ==========================================

    public function test_mark_as_paid_updates_status()
    {
        $invoice = $this->fakeInvoice(['status' => 'sent']);
        $invoice->shouldReceive('isPaid')->once()->andReturn(false);
        $invoice->shouldReceive('update')->once()->with(Mockery::on(
            fn($data) => isset($data['paid_at'])
        ));

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($invoice, 'paid')
            ->andReturn($invoice);

        $result = $this->service->markAsPaid(1);

        $this->assertSame($invoice, $result);
    }

    public function test_mark_as_paid_throws_exception_if_already_paid()
    {
        $invoice = $this->fakeInvoice(['status' => 'paid']);
        $invoice->shouldReceive('isPaid')->once()->andReturn(true);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository->shouldNotReceive('updateStatus');

        $this->expectException(HttpResponseException::class);

        $this->service->markAsPaid(1);
    }

    // ==========================================
    // markAsPaidByWebhook()
    // ==========================================

    public function test_mark_as_paid_by_webhook_updates_status()
    {
        Queue::fake();

        $invoice = $this->fakeInvoice(['status' => 'sent']);
        $invoice->shouldReceive('isPaid')->once()->andReturn(false);
        $invoice->shouldReceive('update')->once()->with(Mockery::on(
            fn($data) => isset($data['paid_at'])
        ));

        $this->invoiceRepository
            ->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($invoice);

        $this->invoiceRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($invoice, 'paid')
            ->andReturn($invoice);

        $result = $this->service->markAsPaidByWebhook(1);

        $this->assertSame($invoice, $result);
        Queue::assertPushed(SendPaidEmailJob::class);
    }

    public function test_mark_as_paid_by_webhook_returns_invoice_if_already_paid()
    {
        Queue::fake();

        $invoice = $this->fakeInvoice(['status' => 'paid']);
        $invoice->shouldReceive('isPaid')->once()->andReturn(true);

        $this->invoiceRepository
            ->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($invoice);

        $this->invoiceRepository->shouldNotReceive('updateStatus');

        $result = $this->service->markAsPaidByWebhook(1);

        $this->assertSame($invoice, $result);
        Queue::assertNotPushed(SendPaidEmailJob::class);
    }

    public function test_mark_as_paid_by_webhook_throws_exception_if_not_found()
    {
        $this->invoiceRepository
            ->shouldReceive('getById')
            ->once()
            ->with(99)
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice 99 not found.');

        $this->service->markAsPaidByWebhook(99);
    }

    // ==========================================
    // deleteInvoice()
    // ==========================================

    public function test_delete_invoice_deletes_invoice()
    {
        $invoice = $this->fakeInvoice(['status' => 'draft']);
        $invoice->shouldReceive('isDraft')->once()->andReturn(true);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository
            ->shouldReceive('delete')
            ->once()
            ->with($invoice);

        $this->service->deleteInvoice(1);

        $this->assertTrue(true);
    }

    public function test_delete_invoice_throws_exception_if_not_draft()
    {
        $invoice = $this->fakeInvoice(['status' => 'sent']);
        $invoice->shouldReceive('isDraft')->once()->andReturn(false);

        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($invoice);

        $this->invoiceRepository->shouldNotReceive('delete');

        $this->expectException(HttpResponseException::class);

        $this->service->deleteInvoice(1);
    }

    public function test_delete_invoice_throws_exception_if_not_found()
    {
        $this->invoiceRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn(null);

        $this->invoiceRepository->shouldNotReceive('delete');

        $this->expectException(HttpResponseException::class);

        $this->service->deleteInvoice(99);
    }

    // ==========================================
    // checkAndMarkOverdue()
    // ==========================================

    public function test_check_and_mark_overdue_returns_invoice_count()
    {
        $invoice1 = $this->fakeInvoice(['id' => 1]);
        $invoice2 = $this->fakeInvoice(['id' => 2]);
        $invoices = new Collection([$invoice1, $invoice2]);

        $this->invoiceRepository
            ->shouldReceive('getOverdueInvoices')
            ->once()
            ->andReturn($invoices);

        $this->invoiceRepository
            ->shouldReceive('updateStatus')
            ->twice()
            ->with(Mockery::any(), 'overdue');

        $count = $this->service->checkAndMarkOverdue();

        $this->assertEquals(2, $count);
    }

    public function test_check_and_mark_overdue_returns_zero_if_no_invoices()
    {
        $this->invoiceRepository
            ->shouldReceive('getOverdueInvoices')
            ->once()
            ->andReturn(new Collection());

        $this->invoiceRepository->shouldNotReceive('updateStatus');

        $count = $this->service->checkAndMarkOverdue();

        $this->assertEquals(0, $count);
    }
}
