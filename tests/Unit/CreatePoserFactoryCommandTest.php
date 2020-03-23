<?php

namespace Lukeraymonddowning\Poser\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Lukeraymonddowning\Poser\Tests\TestCase;

class CreatePoserFactoryCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var string */
    private $newFactoriesDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setBasePath(realpath(__DIR__ . '/../storage'));
        $this->app['config']->set('poser.models_namespace', '\\App\\Models\\');
        $this->app['config']->set('poser.factories_directory', 'NewTestsDir/Factories/');

        $this->newFactoriesDirectory = base_path(config('poser.factories_directory'));

        File::deleteDirectory(base_path('NewTestsDir'));
    }

    /** @test */
    public function it_creates_a_poser_factory_and_fills_up_wildcards()
    {
        $this->assertFalse(File::exists($this->newFactoriesDirectory . 'BookFactory.php'));

        $this->artisan('make:poser', ['name' => 'BookFactory']);

        $this->assertTrue(File::exists($this->newFactoriesDirectory . 'BookFactory.php'));
        $fileContents = File::get($this->newFactoriesDirectory . 'BookFactory.php');

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\Tests\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\App\Models\Book[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class BookFactory extends Factory', $fileContents);
    }

    /** @test */
    public function it_creates_a_poser_factory_for_a_model_with_custom_namespace_and_fills_up_wildcards()
    {
        $this->assertFalse(File::exists($this->newFactoriesDirectory . 'AuthorFactory.php'));

        $this->artisan('make:poser', [
            'name' => 'AuthorFactory',
            '--model' => '\Lukeraymonddowning\Poser\Tests\Models\User'
        ]);

        $this->assertTrue(File::exists($this->newFactoriesDirectory . 'AuthorFactory.php'));
        $fileContents = File::get($this->newFactoriesDirectory . 'AuthorFactory.php');

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\Tests\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\Lukeraymonddowning\Poser\Tests\Models\User[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class AuthorFactory extends Factory', $fileContents);
    }

    /** @test */
    public function it_creates_multiple_poser_factories()
    {
        $this->artisan('make:poser')->assertExitCode(1); //Couldn't find any classes at the namespace

        $oldNamespace = config('poser.models_namespace');
        $this->app['config']->set('poser.models_namespace', 'Lukeraymonddowning\\Poser\\Tests\\Models\\');

        $this->assertEquals(0, count(File::glob($this->newFactoriesDirectory . '*.php')));

        $this->artisan('make:poser')->assertExitCode(0); //Models created, success response
        $this->assertGreaterThan(1, count(File::glob($this->newFactoriesDirectory . '*.php')));

        //Models existed but didn't create any Factories since they already existed
        $this->artisan('make:poser')->assertExitCode(2);

        $this->app['config']->set('poser.models_namespace', $oldNamespace);

        //Run the rest of the file checks
        $this->assertTrue(File::exists($this->newFactoriesDirectory . 'UserProfileFactory.php'));
        $fileContents = File::get($this->newFactoriesDirectory . 'UserProfileFactory.php');

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\Tests\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\Lukeraymonddowning\Poser\Tests\Models\UserProfile[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class UserProfileFactory extends Factory', $fileContents);
    }
}
