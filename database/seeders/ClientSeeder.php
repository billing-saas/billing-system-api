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
            'user_id' => '7360130f-57bc-4486-b9cb-421ef4e7562b',
        ]);
    }
}
