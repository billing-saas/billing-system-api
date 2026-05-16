<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Services\ClientService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithAuthUser;

class ClientServiceTest extends TestCase
{
    use WithAuthUser;

    private ClientService $service;
    private MockInterface $clientRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setAuthUser(); // ← trait replaces manual request()->merge()

        $this->clientRepository = Mockery::mock(ClientRepository::class);
        $this->service          = new ClientService($this->clientRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    public function test_list_clients_without_filters()
    {
        $paginator = $this->fakePaginator([
            ['id' => 1, 'name' => 'Client A'],
            ['id' => 2, 'name' => 'Client B'],
        ]);

        $this->clientRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', [])
            ->andReturn($paginator);

        $result = $this->service->listClients();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
    }

    public function test_list_clients_with_filters()
    {
        $filters   = ['search' => 'Acme', 'status' => 'active'];
        $paginator = $this->fakePaginator([
            ['id' => 3, 'name' => 'Acme Corp'],
        ]);

        $this->clientRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', $filters)
            ->andReturn($paginator);

        $result = $this->service->listClients($filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result->items());
    }

    public function test_list_clients_returns_empty_list()
    {
        $this->clientRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', [])
            ->andReturn($this->fakePaginator([]));

        $result = $this->service->listClients();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(0, $result->items());
        $this->assertEquals(0, $result->total());
    }

    public function test_list_clients_throws_exception_if_repository_fails()
    {
        $this->clientRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('user-uuid-123', [])
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->listClients();
    }

    public function test_list_clients_passes_correct_user_id_to_repository()
    {
        $this->setAuthUser(['sub' => 'autre-uuid-456']); // ← override via trait

        $this->clientRepository
            ->shouldReceive('getAllByUser')
            ->once()
            ->with('autre-uuid-456', [])
            ->andReturn($this->fakePaginator());

        $this->service->listClients();

        $this->assertTrue(true);
    }


    // ==========================================
    // getClient()
    // ==========================================

    public function test_get_client_returns_client()
    {
        $client = Mockery::mock(Client::class);

        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(1, 'user-uuid-123')
            ->andReturn($client);

        $result = $this->service->getClient(1);

        $this->assertSame($client, $result);
    }

    public function test_get_client_throws_exception_if_not_found()
    {
        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(99, 'user-uuid-123')
            ->andReturn(null);

        $this->expectException(HttpResponseException::class);

        $this->service->getClient(99);
    }

    public function test_get_client_does_not_return_client_of_another_user()
    {
        // Even if the client exists, findByIdAndUser returns null
        // because it belongs to another user
        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(1, 'user-uuid-123')
            ->andReturn(null);

        $this->expectException(HttpResponseException::class);

        $this->service->getClient(1);
    }

    // ==========================================
    // createClient()
    // ==========================================

    public function test_create_client_creates_and_returns_client()
    {
        $data   = ['name' => 'Acme Corp', 'email' => 'acme@example.com'];
        $client = Mockery::mock(Client::class);

        $this->clientRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'name'    => 'Acme Corp',
                'email'   => 'acme@example.com',
                'user_id' => 'user-uuid-123', // ← injected automatically
            ])
            ->andReturn($client);

        $result = $this->service->createClient($data);

        $this->assertSame($client, $result);
    }

    public function test_create_client_injects_user_id_from_auth_helper()
    {
        $this->setAuthUser(['sub' => 'autre-uuid-456']);
        $this->service = new ClientService($this->clientRepository);

        $client         = Mockery::mock(Client::class);
        $capturedData   = [];

        $this->clientRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) use (&$capturedData) {
                $capturedData = $data; // ← capture received data
                return true;
            }))
            ->andReturn($client);

        $this->service->createClient(['name' => 'Test']);

        // ← explicit assertion on captured user_id
        $this->assertEquals('autre-uuid-456', $capturedData['user_id']);
    }

    public function test_create_client_throws_exception_if_repository_fails()
    {
        $this->clientRepository
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->createClient(['name' => 'Acme']);
    }

    // ==========================================
    // updateClient()
    // ==========================================

    public function test_update_client_updates_and_returns_client()
    {
        $client  = Mockery::mock(Client::class);
        $updated = Mockery::mock(Client::class);
        $data    = ['name' => 'New Name'];

        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(1, 'user-uuid-123')
            ->andReturn($client);

        $this->clientRepository
            ->shouldReceive('update')
            ->once()
            ->with($client, $data)
            ->andReturn($updated);

        $result = $this->service->updateClient(1, $data);

        $this->assertSame($updated, $result);
    }

    public function test_update_client_throws_exception_if_client_not_found()
    {
        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(99, 'user-uuid-123')
            ->andReturn(null);

        // update must never be called
        $this->clientRepository->shouldNotReceive('update');

        $this->expectException(HttpResponseException::class);

        $this->service->updateClient(99, ['name' => 'Test']);
    }

    public function test_update_client_throws_exception_if_repository_fails()
    {
        $client = Mockery::mock(Client::class);

        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($client);

        $this->clientRepository
            ->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->updateClient(1, ['name' => 'Test']);
    }

    // ==========================================
    // deleteClient()
    // ==========================================

    public function test_delete_client_deletes_client()
    {
        $client = Mockery::mock(Client::class);

        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(1, 'user-uuid-123')
            ->andReturn($client);

        $this->clientRepository
            ->shouldReceive('delete')
            ->once()
            ->with($client);

        $this->service->deleteClient(1);

        // Mockery checks delete() was called once
        $this->assertTrue(true);
    }

    public function test_delete_client_throws_exception_if_client_not_found()
    {
        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(99, 'user-uuid-123')
            ->andReturn(null);

        // delete must never be called
        $this->clientRepository->shouldNotReceive('delete');

        $this->expectException(HttpResponseException::class);

        $this->service->deleteClient(99);
    }

    public function test_delete_client_throws_exception_if_repository_fails()
    {
        $client = Mockery::mock(Client::class);

        $this->clientRepository
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->andReturn($client);

        $this->clientRepository
            ->shouldReceive('delete')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->deleteClient(1);
    }
}
