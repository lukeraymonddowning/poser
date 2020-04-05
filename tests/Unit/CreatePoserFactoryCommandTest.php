<?php

namespace Lukeraymonddowning\Poser\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Lukeraymonddowning\Poser\Tests\TestCase;

class CreatePoserFactoryCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var string */
    private $newFactoriesLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setBasePath(realpath(__DIR__ . '/../storage'));
        $this->app['config']->set('poser.models_namespace', 'Lukeraymonddowning\\Poser\\Tests\\Models\\');
        $this->app['config']->set('poser.factories_location', 'NewTestsDir/Factories/');
        $this->app['config']->set('poser.factories_namespace', 'Lukeraymonddowning\\Poser\\NewTestsDir\\Factories\\');

        $this->newFactoriesLocation = base_path(config('poser.factories_location'));

        File::deleteDirectory(base_path('NewTestsDir'));
    }

    /** @test */
    public function it_creates_a_poser_factory_and_fills_up_wildcards()
    {
        $this->artisan('make:poser', ['name' => 'NotARealClass'])->assertExitCode(1);

        $this->assertFalse(File::exists($this->newFactoriesLocation . 'UserFactory.php'));

        $this->artisan('make:poser', ['name' => 'UserFactory'])->assertExitCode(0);

        $this->assertTrue(File::exists($this->newFactoriesLocation . 'UserFactory.php'));
        $fileContents = File::get($this->newFactoriesLocation . 'UserFactory.php');

        $this->artisan('make:poser', ['name' => 'UserFactory'])->assertExitCode(2);

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\NewTestsDir\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\Lukeraymonddowning\Poser\Tests\Models\User[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class UserFactory extends Factory', $fileContents);
    }

    /** @test */
    public function it_creates_a_poser_factory_for_a_model_with_custom_namespace_and_fills_up_wildcards()
    {
        $this->artisan('make:poser', [
            'name' => 'Name',
            '--model' => '\App\NotARealClass'
        ])->assertExitCode(1);

        $this->assertFalse(File::exists($this->newFactoriesLocation . 'AuthorFactory.php'));

        $this->artisan('make:poser', [
            'name' => 'AuthorFactory',
            '--model' => '\Lukeraymonddowning\Poser\Tests\Models\Address'
        ])->assertExitCode(0);

        $this->assertTrue(File::exists($this->newFactoriesLocation . 'AuthorFactory.php'));
        $fileContents = File::get($this->newFactoriesLocation . 'AuthorFactory.php');

        $this->artisan('make:poser', [
            'name' => 'AuthorFactory',
            '--model' => '\Lukeraymonddowning\Poser\Tests\Models\Address'
        ])->assertExitCode(2);

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\NewTestsDir\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\Lukeraymonddowning\Poser\Tests\Models\Address[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class AuthorFactory extends Factory', $fileContents);
    }

    /** @test */
    public function it_creates_multiple_poser_factories()
    {
        $oldNamespace = config('poser.models_namespace');
        $this->app['config']->set('poser.models_namespace', 'App\\Models\\');
        $this->artisan('make:poser')->assertExitCode(1); //Couldn't find any classes at the namespace
        $this->app['config']->set('poser.models_namespace', $oldNamespace);

        $filesBeforeRun = count(File::glob($this->newFactoriesLocation . '*.php'));

        $this->artisan('make:poser')->assertExitCode(0); //Models created, success response
        $this->assertGreaterThan($filesBeforeRun, count(File::glob($this->newFactoriesLocation . '*.php')));

        //Models existed but didn't create any Factories since they already existed
        $this->artisan('make:poser')->assertExitCode(2);

        $this->app['config']->set('poser.models_namespace', $oldNamespace);

        //Run the rest of the file checks
        $this->assertTrue(File::exists($this->newFactoriesLocation . 'UserProfileFactory.php'));
        $fileContents = File::get($this->newFactoriesLocation . 'UserProfileFactory.php');

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\NewTestsDir\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\Lukeraymonddowning\Poser\Tests\Models\UserProfile[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class UserProfileFactory extends Factory', $fileContents);
    }
}
