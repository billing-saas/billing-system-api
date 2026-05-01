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
            'message' => 'Clients récupérés avec succès.',
            'errors'  => [],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $client = $this->clientService->getClient($id);

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
            'message' => 'Client récupéré avec succès.',
            'errors'  => [],
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->createClient($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
            'message' => 'Client créé avec succès.',
            'errors'  => [],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        $client = $this->clientService->updateClient($id, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
            'message' => 'Client mis à jour avec succès.',
            'errors'  => [],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->clientService->deleteClient($id);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Client supprimé avec succès.',
            'errors'  => [],
        ]);
    }
}
