<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Lukeraymonddowning\Poser\Tests\Models\User;

/** @var Factory $factory */
$factory->define(
    User::class,
    function (Faker $faker) {
        return [
            'name'              => $faker->name,
            'email'             => $faker->email,
            'email_verified_at' => '2020-01-01',
            'active'            => true,
        ];
    }
);

$factory->state(User::class, 'inactive', ['active' => false]);

$factory->state(User::class, 'unverified', ['email_verified_at' => null]);
