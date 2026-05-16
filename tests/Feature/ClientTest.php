<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use Tests\Traits\WithAuthUser;

class ClientTest extends TestCase
{
    use WithAuthUser;

    private ClientService $clientService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientService = Mockery::mock(ClientService::class);
        $this->app->instance(ClientService::class, $this->clientService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function makeClient(array $attributes = []): Client
    {
        $client = new Client();

        $defaults = [
            'id'           => 1,
            'user_id'      => 'user-uuid-123',
            'name'         => 'Acme Corp',
            'email'        => 'acme@example.com',
            'phone'        => '+1234567890',
            'address'      => '123 Main Street',
            'city'         => 'Paris',
            'postal_code'  => '75001',
            'country'      => 'FR',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $client->$key = $value;
        }

        return $client;
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

    // ==========================================
    // GET /api/v1/clients — index()
    // ==========================================

    public function test_index_returns_client_list()
    {
        $clients = $this->fakePaginator([
            $this->makeClient(['id' => 1, 'name' => 'Acme Corp']),
            $this->makeClient(['id' => 2, 'name' => 'Beta Ltd']),
        ]);

        $this->clientService
            ->shouldReceive('listClients')
            ->once()
            ->andReturn($clients);

        $response = $this->callAsUser('GET', '/api/v1/clients');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customers successfully recovered.',
                'errors'  => [],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'data',
            ]);
    }

    public function test_index_returns_empty_list()
    {
        $this->clientService
            ->shouldReceive('listClients')
            ->once()
            ->andReturn($this->fakePaginator([]));

        $response = $this->callAsUser('GET', '/api/v1/clients');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'errors'  => [],
            ]);
    }

    public function test_index_passes_filters_to_service()
    {
        $this->clientService
            ->shouldReceive('listClients')
            ->once()
            ->with(Mockery::on(function (array $filters) {
                return isset($filters['search']) && $filters['search'] === 'Acme';
            }))
            ->andReturn($this->fakePaginator());

        $response = $this->callAsUser('GET', '/api/v1/clients?search=Acme');

        $response->assertStatus(200);
    }

    public function test_index_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/clients');

        $response->assertStatus(401);
    }

    // ==========================================
    // GET /api/v1/clients/{id} — show()
    // ==========================================

    public function test_show_returns_client()
    {
        $client = $this->makeClient(['id' => 1, 'name' => 'Acme Corp']);

        $this->clientService
            ->shouldReceive('getClient')
            ->once()
            ->with(1)
            ->andReturn($client);

        $response = $this->callAsUser('GET', '/api/v1/clients/1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer successfully retrieved.',
                'errors'  => [],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    }

    public function test_show_returns_404_if_client_not_found()
    {
        $this->clientService
            ->shouldReceive('getClient')
            ->once()
            ->with(99)
            ->andThrow(new HttpResponseException(
                response()->json(['success' => false, 'message' => 'Client not found.'], 404)
            ));

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->getJson('/api/v1/clients/99');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Client not found.',
            ]);
    }

    public function test_show_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/clients/1');

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/v1/clients — store()
    // ==========================================

    public function test_store_creates_client_successfully()
    {
        $client = $this->makeClient(['name' => 'New Client']);

        $this->clientService
            ->shouldReceive('createClient')
            ->once()
            ->andReturn($client);

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/clients', [
                'name'  => 'New Client',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully.',
            ]);
    }

    public function test_store_fails_when_required_fields_missing()
    {
        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/clients', []);

        $response->assertStatus(422);
    }

    public function test_store_returns_401_when_unauthenticated()
    {
        $response = $this->postJson('/api/v1/clients', [
            'name'  => 'New Client',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // PUT /api/v1/clients/{id} — update()
    // ==========================================

    public function test_update_updates_client()
    {
        $client = $this->makeClient(['name' => 'Updated Name']);

        $this->clientService
            ->shouldReceive('updateClient')
            ->once()
            ->with(1, Mockery::type('array'))
            ->andReturn($client);

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/clients/1', [
                'name'  => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully.',
            ]);
    }

    public function test_update_returns_404_if_client_not_found()
    {
        $this->clientService
            ->shouldReceive('updateClient')
            ->once()
            ->andThrow(new HttpResponseException(
                response()->json(['success' => false, 'message' => 'Client not found.'], 404)
            ));

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/clients/99', ['name' => 'Test']);

        $response->assertStatus(404);
    }

    public function test_update_returns_401_when_unauthenticated()
    {
        $response = $this->putJson('/api/v1/clients/1', ['name' => 'Test']);

        $response->assertStatus(401);
    }

    // ==========================================
    // DELETE /api/v1/clients/{id} — destroy()
    // ==========================================

    public function test_destroy_deletes_client()
    {
        $this->clientService
            ->shouldReceive('deleteClient')
            ->once()
            ->with(1);

        $response = $this->callAsUser('DELETE', '/api/v1/clients/1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer deleted successfully.',
                'data'    => null,
                'errors'  => [],
            ]);
    }

    public function test_destroy_returns_404_if_client_not_found()
    {
        $this->clientService
            ->shouldReceive('deleteClient')
            ->once()
            ->andThrow(new HttpResponseException(
                response()->json(['success' => false, 'message' => 'Client not found.'], 404)
            ));

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->deleteJson('/api/v1/clients/99');

        $response->assertStatus(404);
    }

    public function test_destroy_returns_401_when_unauthenticated()
    {
        $response = $this->deleteJson('/api/v1/clients/1');

        $response->assertStatus(401);
    }
}
