<?php

namespace App\Services;

use App\Models\Client;
use App\Repositories\ClientRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\AuthHelper;

class ClientService
{
    public function __construct(
        private ClientRepository $clientRepository
    ) {}

    public function listClients(array $filters = []): LengthAwarePaginator
    {
        return $this->clientRepository->getAllByUser(
            AuthHelper::id(),
            $filters
        );
    }

    public function getClient(int $id): Client
    {
        $client = $this->clientRepository->findByIdAndUser(
            $id,
            AuthHelper::id()
        );

        if (!$client) {
            $this->notFound();
        }

        return $client;
    }

    public function createClient(array $data): Client
    {
        return $this->clientRepository->create([
            ...$data,
            'user_id' => AuthHelper::id(),
        ]);
    }

    public function updateClient(int $id, array $data): Client
    {
        $client = $this->getClient($id);
        return $this->clientRepository->update($client, $data);
    }

    public function deleteClient(int $id): void
    {
        $client = $this->getClient($id);
        $this->clientRepository->delete($client);
    }

    private function notFound(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Client introuvable.',
                'data'    => null,
                'errors'  => [],
            ], Response::HTTP_NOT_FOUND)
        );
    }
}
