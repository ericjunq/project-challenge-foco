<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_usa_o_banco_de_testes(): void
    {
        $this->assertSame('foco_challenge_test', DB::connection()->getDatabaseName());
    }
}