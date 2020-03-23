<?php

namespace Lukeraymonddowning\Poser\Tests\Factories;

use Lukeraymonddowning\Poser\Factory;

class AddressFactory extends Factory
{
    public function defaultUser()
    {
        return UserFactory::new();
    }
}
