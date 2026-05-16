<?php

namespace Tests\Traits;

use Mockery;

trait WithMockeryCleanup
{
    /**
     * Nettoie les mocks après chaque test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
