<?php

if (!function_exists('modelsNamespace')) {
    function modelsNamespace()
    {
        return config('poser.models_namespace', config('poser.models_directory', "App\\"));
    }
}

if (!function_exists('factoriesNamespace')) {
    function factoriesNamespace()
    {
        return config('poser.factories_namespace', config('poser.factories_directory', "Tests\\Factories\\"));
    }
}

if (!function_exists('factoriesLocation')) {
    function factoriesLocation()
    {
        return config('poser.factories_location', "tests/Factories/");
    }
}
