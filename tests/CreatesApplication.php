<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $this->assertTestingDatabaseIsSafe();

        return $app;
    }

    private function assertTestingDatabaseIsSafe(): void
    {
        if (app()->environment() !== 'testing') {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $normalized = strtolower($database);

        if ($connection === 'sqlite' && $database === ':memory:') {
            return;
        }

        if (str_contains($normalized, 'test')) {
            return;
        }

        throw new RuntimeException("Tests bloqueados: la base de datos configurada no es segura para testing ({$connection}:{$database}).");
    }
}
