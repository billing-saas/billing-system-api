<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    public function getAllByUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Client::where('user_id', $userId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findByIdAndUser(int $id, string $userId): ?Client
    {
        return Client::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);
        return $client->fresh();
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
