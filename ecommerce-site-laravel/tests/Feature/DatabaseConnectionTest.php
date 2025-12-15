<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class DatabaseConnectionTest extends TestCase
{
    // use RefreshDatabase; // Disable to see if connection without migration works

    public function test_database_connection_is_sqlite()
    {
        $connection = DB::getDefaultConnection();
        $driver = DB::connection()->getDriverName();
        $database = Config::get('database.connections.sqlite.database');
        
        fwrite(STDERR, "\nConnection: " . $connection . "\n");
        fwrite(STDERR, "Driver: " . $driver . "\n");
        fwrite(STDERR, "Database: " . $database . "\n");

        $this->assertEquals('pgsql', $driver);
    }
}
