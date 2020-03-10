<?php

namespace Lukeraymonddowning\Poser\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lukeraymonddowning\Poser\PoserServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/DatabaseFactories');

        $this->createTables();
    }

    /**
     * Get package providers
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            PoserServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
        $app['config']->set('poser.models_directory', 'Lukeraymonddowning\\Poser\\Tests\\Models\\');
        $app['config']->set('poser.factories_directory', 'Lukeraymonddowning\\Poser\\Tests\\Factories\\');
    }

    /**
     * Create tables for tests
     */
    protected function createTables()
    {
        Schema::create(
            'users',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('email');
                $table->dateTime('email_verified_at')->nullable();
                $table->boolean('active');
                $table->timestamps();
            }
        );
    }
}
