<?php

namespace Lukeraymonddowning\Poser;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreatePoserFactory extends Command
{
    protected $signature = 'make:poser {name}';

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

        $this->info("Creating Poser Factory called " . $name);

        $factoriesDirectory = config('poser.factories_directory', 'Tests\\Factories');

        $destinationDirectory = base_path()."/".str_replace("\\", "/", $factoriesDirectory);

        if (!File::exists($destinationDirectory))
            File::makeDirectory($destinationDirectory);

        $destination = $destinationDirectory.$name.".php";

        if (File::exists($destination)) {
            $this->error("There is already a Factory called " . $name . " at " . $destinationDirectory);
            return;
        }

        File::copy(__DIR__.'/stubs/FactoryStub.txt', $destination);

        $value = file_get_contents($destination);

        $namespace = str_replace('/', '\\', $factoriesDirectory);
        if (Str::endsWith($namespace, '\\'))
            $namespace = Str::beforeLast($namespace, '\\');

        $valueWithNamespace = str_replace("{{ Namespace }}", $namespace, $value);
        $valueFormatted = str_replace("{{ ClassName }}", $name, $valueWithNamespace);

        file_put_contents($destination, $valueFormatted);

        $this->info($name . " successfully created at " . $destination);
        $this->line("");
        $this->line("Remember, you should have a corresponding model, database factory and migration");
        $this->line("");
        $this->info("Please consider starring the repo at https://github.com/lukeraymonddowning/poser");
    }
}
