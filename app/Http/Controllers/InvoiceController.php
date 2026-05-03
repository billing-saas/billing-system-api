<?php
// app/Http/Controllers/InvoiceController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceCollection;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->invoiceService->listInvoices(
            $request->only([
                'status',
                'client_id',
                'search',
                'date_from',
                'date_to',
                'per_page'
            ])
        );

        return response()->json([
            'success' => true,
            'data'    => new InvoiceCollection($invoices),
            'message' => 'Invoices retrieved successfully.',
            'errors'  => [],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoice($id);

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
            'message' => 'Invoice retrieved successfully.',
            'errors'  => [],
        ]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->createInvoice($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
            'message' => 'Invoice created successfully.',
            'errors'  => [],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        $invoice = $this->invoiceService->updateInvoice($id, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
            'message' => 'Invoice updated successfully.',
            'errors'  => [],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->invoiceService->deleteInvoice($id);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Invoice deleted successfully.',
            'errors'  => [],
        ]);
    }

    public function send(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->sendInvoice($id);

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
            'message' => 'Invoice sent successfully.',
            'errors'  => [],
        ]);
    }

    public function markAsPaid(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->markAsPaid($id);

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
            'message' => 'Invoice marked as paid.',
            'errors'  => [],
        ]);
    }
}
