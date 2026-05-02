<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $clients = $this->clientService->listClients(
            $request->only(['search', 'city', 'country', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data'    => new ClientCollection($clients),
            'message' => 'Customers successfully recovered.',
            'errors'  => [],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $client = $this->clientService->getClient($id);

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
            'message' => 'Customer successfully retrieved.',
            'errors'  => [],
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->createClient($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
            'message' => 'Customer created successfully.',
            'errors'  => [],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        $client = $this->clientService->updateClient($id, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
            'message' => 'Customer updated successfully.',
            'errors'  => [],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->clientService->deleteClient($id);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Customer deleted successfully.',
            'errors'  => [],
        ]);
    }
}
