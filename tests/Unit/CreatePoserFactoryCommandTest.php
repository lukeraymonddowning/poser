<?php

namespace Tests\Unit;

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

        $this->newFactoriesDirectory = str_replace(
            '\\',
            '/',
            base_path('Lukeraymonddowning\\Poser\\Tests\\Factories\\')
        );

        File::deleteDirectory(base_path('Lukeraymonddowning'));
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
            '--model' => '\App\Really\Different\ModelsNamespace\User'
        ]);

        $this->assertTrue(File::exists($this->newFactoriesDirectory . 'AuthorFactory.php'));
        $fileContents = File::get($this->newFactoriesDirectory . 'AuthorFactory.php');

        $this->assertStringNotContainsString('{{ Namespace }}', $fileContents);
        $this->assertStringContainsString('namespace Lukeraymonddowning\Poser\Tests\Factories;', $fileContents);

        $this->assertStringNotContainsString('{{ ModelNamespace }}', $fileContents);
        $this->assertStringContainsString('\App\Really\Different\ModelsNamespace\User[]', $fileContents);

        $this->assertStringNotContainsString('{{ ClassName }}', $fileContents);
        $this->assertStringContainsString('class AuthorFactory extends Factory', $fileContents);
    }

    /** @test */
//    public function it_creates_database_factory()
//    {
////        $spy = $this->spy(FactoryMakeCommand::class);
}
