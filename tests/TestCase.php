<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Ignore developer/production config caches before Laravel boots or runs migration traits.
        $environment = [
            'APP_CONFIG_CACHE' => sys_get_temp_dir().'/apmalls-test-config-'.bin2hex(random_bytes(12)).'.php',
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '',
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array', 'BCRYPT_ROUNDS' => '4',
        ];
        foreach ($environment as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tests may only run against in-memory SQLite.');
        }
        return $app;
    }
}
