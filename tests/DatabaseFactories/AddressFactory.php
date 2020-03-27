<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Lukeraymonddowning\Poser\Tests\Models\Address;

/** @var Factory $factory */
$factory->define(Address::class, function (Faker $faker) {
    return [
        'line_1' => $faker->streetAddress,
    ];
});
