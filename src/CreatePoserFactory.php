<?php

namespace Lukeraymonddowning\Poser;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class CreatePoserFactory extends GeneratorCommand
{

    protected $signature = 'make:poser {name? : The name of the Poser Factory}
                                       {--m|model= : The model that this factory is linked too}
                                       {--f|factory : Also create the Laravel database factory}';

    protected $description = 'Creates a Poser Model Factory with the given name';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub($stubVariant = null)
    {
        return __DIR__ . '/stubs/FactoryStub' . ($stubVariant ? '.' . $stubVariant : '') . '.txt';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        if ($name) {
            $this->createFactory($name);

            return;
        }

        $this->createAllFactories();
    }

    protected function createFactory($factoryName, $className = null)
    {
        $this->info("Creating Poser Factory called " . $factoryName);

        $factoriesDirectory = config('poser.factories_directory', 'Tests\\Factories');
        $modelsDirectory = config('poser.models_directory', 'App\\');

        $expectedModelNameSpace = '\\' . $modelsDirectory . Str::beforeLast($factoryName, 'Factory');
        $linkedModelNamespace = $this->option('model')
            ? '\\' . $this->qualifyClass($this->option('model'))
            : $expectedModelNameSpace;

        $destinationDirectory = base_path() . "/" . str_replace("\\", "/", $factoriesDirectory);

        if (!File::exists($destinationDirectory)) {
            File::makeDirectory($destinationDirectory);
        }

        $destination = $destinationDirectory . $factoryName . ".php";

        if (File::exists($destination)) {
            $this->error("There is already a Factory called " . $factoryName . " at " . $destinationDirectory);

            return;
        }

        $stubVariant = null;
        if ($expectedModelNameSpace !== $linkedModelNamespace) {
            $stubVariant = 'model';
        }

        File::copy($this->getStub($stubVariant), $destination);

        $value = file_get_contents($destination);

        $namespace = str_replace('/', '\\', $factoriesDirectory);
        if (Str::endsWith($namespace, '\\')) {
            $namespace = Str::beforeLast($namespace, '\\');
        }

        $valueFormatted = str_replace(
            [
                "{{ Namespace }}",
                "{{ ClassName }}",
                "{{ ModelNamespace }}",
            ],
            [
                $namespace,
                $factoryName,
                $linkedModelNamespace,
            ],
            $value
        );

        file_put_contents($destination, $valueFormatted);

        $this->info($factoryName . " successfully created at " . $destination);
        $this->line("");
        $this->line("Remember, you should have a corresponding model, database factory and migration");

        if ($this->option('factory')) {
            $this->line("");
            $this->line("Creating database factory");

            $this->createDatabaseFactory($linkedModelNamespace);
        }

        $this->line("");
        $this->info("Please consider starring the repo at https://github.com/lukeraymonddowning/poser");
    }

    protected function createAllFactories()
    {
        $this->info("Creating Factories from all Models...");
        collect(File::files(str_replace('\\', '/', config('poser.models_directory'))))
            ->filter(
                function ($fileInfo) {
                    return class_exists(config('poser.models_directory') . File::name($fileInfo));
                }
            )->map(
                function ($fileInfo) {
                    return config('poser.models_directory') . File::name($fileInfo);
                }
            )->filter(
                function ($className) {
                    return is_subclass_of($className, Model::class);
                }
            )->map(
                function ($className) {
                    return Str::substr($className, Str::length(config('poser.models_directory')));
                }
            )->map(
                function ($modelType) {
                    return $modelType . "Factory";
                }
            )->filter(
                function ($factoryName) {
                    return !class_exists(config('poser.factories_directory', 'Tests\\Factories') . $factoryName);
                }
            )->each(
                function ($factoryName) {
                    $this->createFactory($factoryName);
                }
            );
    }

    /**
     * Create a database factory for the model.
     *
     * @param string $modelNamespace
     *
     * @return void
     */
    protected function createDatabaseFactory($modelNamespace)
    {
        $factory = Str::studly(class_basename($modelNamespace));

        $this->call(
            'make:factory',
            [
                'name' => "{$factory}Factory",
                '--model' => $this->qualifyClass($factory),
            ]
        );
    }
}
