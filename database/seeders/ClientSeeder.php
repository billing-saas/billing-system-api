<?php
// database/seeders/ClientSeeder.php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory(10)->create([
            'user_id' => 'cc1e6824-1853-47ca-a77d-1a3356fe4651',
        ]);
    }
}
