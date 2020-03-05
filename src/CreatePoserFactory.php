<?php

namespace Lukeraymonddowning\Poser;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;

class CreatePoserFactory extends GeneratorCommand
{
    protected $signature = 'make:poser {name : The name of the Poser Factory}
                                       {--m|model= : The model that this factory is linked too}';

    protected $description = 'Creates a Poser Model Factory with the given name';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub($stubVariant = null)
    {
        return __DIR__.'/stubs/FactoryStub' . ($stubVariant ? '.' . $stubVariant : '') . '.txt';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        $this->info("Creating Poser Factory called " . $name);

        $factoriesDirectory = config('poser.factories_directory', 'Tests\\Factories');
        $modelsDirectory = config('poser.models_directory', 'App\\');

        $expectedModelNameSpace = '\\' . $modelsDirectory . Str::beforeLast($name, 'Factory');
        $linkedModelNamespace = $this->option('model')
            ? '\\' . $this->qualifyClass($this->option('model'))
            : $expectedModelNameSpace;

        $destinationDirectory = base_path()."/".str_replace("\\", "/", $factoriesDirectory);

        if (!File::exists($destinationDirectory)) {
            File::makeDirectory($destinationDirectory);
        }

        $destination = $destinationDirectory.$name.".php";

        if (File::exists($destination)) {
            $this->error("There is already a Factory called " . $name . " at " . $destinationDirectory);
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
                $name,
                $linkedModelNamespace,
            ],
            $value
        );

        file_put_contents($destination, $valueFormatted);

        $this->info($name . " successfully created at " . $destination);
        $this->line("");
        $this->line("Remember, you should have a corresponding model, database factory and migration");
        $this->line("");
        $this->info("Please consider starring the repo at https://github.com/lukeraymonddowning/poser");
    }
}
