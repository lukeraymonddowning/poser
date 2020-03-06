<?php

namespace Lukeraymonddowning\Poser;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class CreatePoserFactory extends Command
{

    protected $signature = 'make:poser {name?}';

    protected $description = 'Creates a Poser Model Factory with the given name';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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

        $destinationDirectory = base_path() . "/" . str_replace("\\", "/", $factoriesDirectory);

        if (!File::exists($destinationDirectory)) {
            File::makeDirectory($destinationDirectory);
        }

        $destination = $destinationDirectory . $factoryName . ".php";

        if (File::exists($destination)) {
            $this->error("There is already a Factory called " . $factoryName . " at " . $destinationDirectory);

            return;
        }

        File::copy(__DIR__ . '/stubs/FactoryStub.txt', $destination);

        $value = file_get_contents($destination);

        $namespace = str_replace('/', '\\', $factoriesDirectory);
        if (Str::endsWith($namespace, '\\')) {
            $namespace = Str::beforeLast($namespace, '\\');
        }

        $valueWithNamespace = str_replace("{{ Namespace }}", $namespace, $value);
        $valueFormatted = str_replace("{{ ClassName }}", $factoryName, $valueWithNamespace);

        file_put_contents($destination, $valueFormatted);

        $this->info($factoryName . " successfully created at " . $destination);
        $this->line("");
        $this->line("Remember, you should have a corresponding model, database factory and migration");
        $this->line("");
        $this->info("Please consider starring the repo at https://github.com/lukeraymonddowning/poser");
    }

    protected function createAllFactories()
    {
        $this->info("Creating Factories from all Models...");
        collect(File::files(str_replace('\\', '/', config('poser.models_directory'))))
            ->filter(function ($fileInfo) {
                return class_exists(config('poser.models_directory') . File::name($fileInfo));
            })->map(function ($fileInfo) {
                return config('poser.models_directory') . File::name($fileInfo);
            })->filter(function ($className) {
                return is_subclass_of($className, Model::class);
            })->map(function ($className) {
                return Str::substr($className, Str::length(config('poser.models_directory')));
            })->map(function ($modelType) {
                return $modelType . "Factory";
            })->filter(function ($factoryName) {
                return !class_exists(config('poser.factories_directory', 'Tests\\Factories') . $factoryName);
            })->each(function ($factoryName) {
                $this->createFactory($factoryName);
            });
    }
}
