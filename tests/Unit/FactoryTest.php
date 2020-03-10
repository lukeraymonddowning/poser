<?php

namespace Lukeraymonddowning\Poser\Tests\Unit;

use Lukeraymonddowning\Poser\Tests\Factories\UserFactory;
use Lukeraymonddowning\Poser\Tests\Models\User;
use Lukeraymonddowning\Poser\Tests\TestCase;

class FactoryTest extends TestCase
{
    /** @test */
    public function it_returns_a_new_instance_of_the_factory()
    {
        $this->assertInstanceOf(UserFactory::class, UserFactory::new());

        $this->assertInstanceOf(UserFactory::class, UserFactory::times(5));
    }

    /** @test */
    public function it_creates_a_user()
    {
        $this->assertEquals(0, User::count());

        $john = UserFactory::new()->create();

        $this->assertInstanceOf(User::class, $john);
        $this->assertEquals(1, User::count());

        $jane = UserFactory::new()();

        $this->assertInstanceOf(User::class, $jane);
        $this->assertEquals(2, User::count());
    }

    /** @test */
    public function it_makes_a_user_without_persisting_to_the_database()
    {
        $this->assertEquals(0, User::count());

        $john = UserFactory::new()->make();

        $this->assertInstanceOf(User::class, $john);
        $this->assertFalse($john->exists);
        $this->assertEquals(0, User::count());
    }

    /** @test */
    public function it_creates_a_user_with_attributes()
    {
        $user = UserFactory::new()->create(['name' => 'John']);
        $this->assertEquals('John', $user->fresh()->name);

        $user = UserFactory::new()->withAttributes(['name' => 'Jane'])();
        $this->assertEquals('Jane', $user->fresh()->name);
    }

    /** @test */
    public function it_creates_a_user_with_a_state()
    {
        $john = UserFactory::new()->state('inactive')->create();
        $this->assertFalse($john->active);

        $jane = UserFactory::new()->as('inactive')->create();
        $this->assertFalse($jane->active);
    }

    /** @test */
    public function it_creates_a_user_with_states()
    {
        $john = UserFactory::new()->states(['inactive', 'unverified'])->create();

        $this->assertFalse($john->active);
        $this->assertNull($john->email_verified_at);

        $jane = UserFactory::new()->states('inactive', 'unverified')->create();

        $this->assertFalse($jane->active);
        $this->assertNull($jane->email_verified_at);
    }

    /** @test */
    public function it_creates_a_few_users()
    {
        $this->assertEquals(0, User::count());

        UserFactory::new()->times(5)->create();

        $this->assertEquals(5, User::count());
    }
}
