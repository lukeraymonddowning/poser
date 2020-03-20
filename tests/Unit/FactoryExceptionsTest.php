<?php

namespace Lukeraymonddowning\Poser\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lukeraymonddowning\Poser\Exceptions\ArgumentsNotSatisfiableException;
use Lukeraymonddowning\Poser\Tests\Factories\UserFactory;

class FactoryExceptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_throws_an_exception_when_unknown_relationship_is_used()
    {
        $this->expectException(ArgumentsNotSatisfiableException::class);

        UserFactory::new()->withPets(10)->create();
    }

    /** @test */
    public function it_throws_an_exception_when_calling_relationship_with_different_factory_name()
    {
        $this->expectException(ArgumentsNotSatisfiableException::class);

        UserFactory::new()->withProfile()->create();
    }
}
