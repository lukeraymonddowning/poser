<p align="center">
<img src="https://github.com/lukeraymonddowning/poser/raw/master/poser-logo.png" width="150">
</p>

# Poser
<!-- ALL-CONTRIBUTORS-BADGE:START - Do not remove or modify this section -->
[![All Contributors](https://img.shields.io/badge/all_contributors-3-orange.svg?style=flat-square)](#contributors-)
<!-- ALL-CONTRIBUTORS-BADGE:END -->

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Laravel Class-based Model Factories in literal seconds! Write tests that look as sexy as this...

```php
/** @test */
public function a_user_can_have_customers()
{
    UserFactory::times(20)
               ->hasAddress()
               ->withCustomers(CustomerFactory::times(20)->withBooks(5))();

    $this->assertCount(20 * 20 * 5, Book::all());
}
```
...with a Factory that looks like this...

```php
namespace Tests\Factories;

use Lukeraymonddowning\Poser\Factory;

class UserFactory extends Factory {

    // No need to write any code here
    
}
```


## Install
First, install into your Laravel project using composer.

`composer require lukeraymonddowning/poser`

Next, publish the Poser config file by calling

`php artisan vendor:publish --tag=poser`

To get started quickly, we provide a `php artisan make:poser` command. You may pass the desired name
of your factory as an argument. So the command to create the `UserFactory` would be `php artisan make:poser UserFactory`.

If you want to let Poser do all of the work, simply call `php artisan make:poser` to turn all the models defined in your poser.models_namespace config entry into Poser Factories.

More of a visual person? [Watch this video demonstration of Poser](https://vimeo.com/395500107)

## Usage
Poser takes all of the boilerplate out of writing [class-based model factories](https://tighten.co/blog/tidy-up-your-tests-with-class-based-model-factories).
To get started, install Poser and go to your test suite. Please note: Poser uses the database (obviously), so make sure
your test class extends Laravel's TestCase, not PhpUnit's.

### The Basics
Let's imagine you have a user model that has many customers...
```php
<?php

namespace App;

class User extends Authenticatable
{

    // ...a little while later

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}

```

To set up the factory for this, create a class (we suggest a 'Factories' directory in your 'tests' folder)
called `UserFactory` (you can also just call it `User`, but we think the `Factory` suffix helps), and a class
called `CustomerFactory`. 

Both of these classes should extend the `Poser/Factory` abstract class. Poser can take care of this for you via the `make:poser` command,
so you can call `php artisan make:poser UserFactory` and `php artisan make:poser CustomerFactory`.

You should also have `CustomerFactory` and `UserFactory` as entries in your `database/factories` directory ([standard Laravel stuff](https://laravel.com/docs/database-testing#writing-factories))


Now, head to the test you want to write, and type the following:

```php
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(CustomerFactory::times(30))
        ->create();

    $this->assertCount(30, $user->customers);
}
```

The test should pass with flying colors. Hurrah! Notice that we didn't have to implement the
`withCustomers()` method: Poser was able to intelligently decide what we were trying to do.

For `HasOne` or `HasMany` relationships, you can simply prepend `with` to the relationship method in
the model (eg: the `customers()` method in the `User` model becomes `withCustomers` in the tests),
and Poser will do the rest. 

Let's add a little more complexity: each customer can own many books...

```php
class Customer extends Model
{

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
```

So far, so good. Let's create another factory class, this time called `BookFactory`, 
that again extends Poser's abstract `Factory` class. That's all there is to it! Modify your original
test to give our customers 5 books each...

```php
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(
            CustomerFactory::times(30)->withBooks(BookFactory::times(5))
        )
        ->create();

    $this->assertCount(30, $user->customers);
    $this->assertCount(150, Book::all());
}
``` 

...and watch the tests pass. Pretty nice, huh?

#### Magic Bindings
If your model relationship method name (ie: the `customers()` method on our `User` model) is the same
or a plural version of our `Factory` class (ie: `CustomerFactory`), then we can take advantage of Magic Bindings
in Poser.

Let's take another look at our `User`/`Customer` example.

```php
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(CustomerFactory::times(30))
        ->create();

    $this->assertCount(30, $user->customers);
}
```

Poser is smart enough to be able to work out that `withCustomers()` is a reference to the `CustomerFactory`,
and allows us to rewrite our test like this:

```php
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(30)
        ->create();

    $this->assertCount(30, $user->customers);
}
```

The first argument passed to `withCustomers()` is the number of customers we want to create, in this case: `30`.

Imagine, for a contrived example, that every customer should be called "Joe Bloggs". We can pass a second
argument to `withCustomers()` that defines an associative array of column names and values, just like we
do with the `create()`, `make()` and `withAttributes()` methods:

```php
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(30, [
            "name" => "Joe Bloggs"
        ])
        ->create();

    $this->assertCount(30, $user->customers);
}
```

For HasOne relationships, like our `User`'s Address, we can do very much the same:

```php
/** @test */
public function user_has_address()
{
    $user = UserFactory::new()
        ->withAddress()
        ->create();

    $this->assertNotEmpty($user->address);
}
```

We can also pass an array of attributes, but in this case we pass it as the first argument:
```php
/** @test */
public function user_has_address()
{
    $user = UserFactory::new()
        ->withAddress([
            "line_1" => "1 Test Street" 
        ])
        ->create();

    $this->assertNotEmpty($user->address);
}
```

Sometimes, `with[RelationshipMethodName]` might not be the most readable choice. 
Poser also supports the `has[RelationshipMethodName]` syntax, like so:
```php
/** @test */
public function user_has_address()
{
    $user = UserFactory::new()
        ->hasAddress([
            "line_1" => "1 Test Street" 
        ])
        ->create();

    $this->assertNotEmpty($user->address);
}
```

Let's now put this all together, and demonstrate how simple it is to world build in Poser. Imagine we
want 10 Users, each with an Address and 20 customers. Each customer should have 5 books. That should 
be 10 `User`s, 10 `Address`es, 200 `Customer`s and 1000 `Book`s. Check it out:

```php
/** @test */
public function users_with_addresses_can_have_customers_with_books() {
    UserFactory::times(10)
               ->hasAddress()
               ->withCustomers(CustomerFactory::times(20)->withBooks(5))();

    $this->assertCount(1000, Book::all());
    $this->assertCount(200, Customer::all());
    $this->assertCount(10, User::all());
    $this->assertCount(10, Address::all());
}
```

Let's break down this code. First, we ask the UserFactory to create 10 users, and pass it the
`hasAddress()` function. Poser is able to find the `AddressFactory`, so it automatically instantiates
it for us and gives each user an `Address`.

Next, we call `withCustomers()`. Because we want to specify additional parameters for each `Customer`,
we instantiate `CustomerFactory` directly, asking for `20` at a time. We then chain `withBooks()` onto
the `CustomerFactory`, simply passing the integer `5`. Poser looks for a `BookFactory`, which it finds,
and automatically calls `BookFactory::times(5)` under the hood.

Finally, we complete the statement by invoking the UserFactory with `()`. This is a shorthand syntax
for calling `create()` on the `UserFactory`.

For reference, the same test using Laravel's built in factories looks like this:

```php
/** @test */
public function users_with_addresses_can_have_customers_with_books() {
    $user = factory(User::class)->times(10)->create();
    $user->each(function($user) {
        $user->address()->save(factory(Address::class)->make());
        $customers = factory(Customer::class)->times(20)->make();
        $user->customers()->saveMany($customers);
        $user->customers->each(function($customer) {
            $customer->books()->saveMany(factory(Book::class)->times(5)->make());
        });
    });

    $this->assertCount(1000, Book::all());
    $this->assertCount(200, Customer::all());
    $this->assertCount(10, User::all());
    $this->assertCount(10, Address::all());
}
```

### Belongs To Many Relationships
Poser supports Many-to-Many relationships using the exact same `with[RelationshipMethodName]()` or `has[RelationshipMethodName]()` 
syntax you're now used to. 
Let's take the commonly used example of a `User` that can have many `Role`s, and a `Role` that can have many `User`s.

```php
/** @test */
public function a_user_can_have_many_roles() {
    $user = UserFactory::new()->withRoles(3)();

    $this->assertCount(3, $user->roles);
}

/** @test */
public function a_role_can_have_many_users() {
    $role = RoleFactory::new()->hasUsers(5)();

    $this->assertCount(5, $role->users);
}
```

Poser also allows you to save data to your pivot table when handing Many-to-Many relationships using the `withPivotAttributes()` method:

```php
/** @test */
public function a_user_can_have_many_roles() {
    $expiry = now();
    $user = UserFactory::new()->withRoles(RoleFactory::new()->withPivotAttributes([
        'expires_at' => $expiry
    ]))();

    $this->assertDatabaseHas('role_user', [
        'user_id' => $user->id,
        'expires_at' => $expiry
    ]);
}
```

It is important to note that you should not use the `make()`, `create()` or `invoke()` methods on the relationship factory
when adding pivot attributes, as Poser will have no way to access them when saving the models.

```php
$user = UserFactory::new()->withRoles(RoleFactory::new()->withPivotAttributes([
    'expires_at' => $expiry
])->make())(); // Don't do this

$user = UserFactory::new()->withRoles(RoleFactory::new()->withPivotAttributes([
    'expires_at' => $expiry
]))(); // Do this instead
```

### Polymorphic Relationships
Poser supports all polymorphic relationship types using the same `with[RelationshipMethodName]()` or `has[RelationshipMethodName]()` 
syntax you're now very used to. Imagine that both our `User` and `Customer` models can have `Comment`s. Your Poser tests might look something like
this:

```php
/** @test */
public function a_user_can_have_many_comments() {
    $user = UserFactory::new()->withComments(10)();

    $this->assertCount(10, $user->comments);
}

/** @test */
public function a_customer_can_have_many_comments() {
    $customer = CustomerFactory::new()->withComments(25)->forUser(UserFactory::new()())();

    $this->assertCount(25, $customer->comments);
}
```

Many to Many polymorphic relationships work in exactly the same way.

### Belongs To Relationships
What if we want to create a `BelongsTo` relationship? Poser makes this easy too. Instead of 
prepending `with`, we can prepend `for`. Let's take another look at our examples. Say we wanted to 
request that a customer is given a user. Simply do this:

```php
/** @test */
public function customer_has_user()
{
    $customer = CustomerFactory::new()
        ->forUser(
            UserFactory::new()->create()
        )->create();

    $this->assertNotEmpty($customer->user);
}
```

### Factory States
If you have setup any States in your laravel factories, then you can also use them with Poser.

So if in your Laravel customer factory class you have the following states setup

```php
$factory->state(Customer::class, 'active', function (Faker $faker) {
    return [
        'active' => true,
    ];
});

$factory->state(Customer::class, 'inactive', function (Faker $faker) {
    return [
        'active' => false,
    ];
});

``` 

Then you can use the `state` method to tell Poser to also use these factory states

```php
/** @test */
public function customer_is_active()
{
    $customer = CustomerFactory::new()
        ->state('active')
        ->create();

    $this->assertTrue($customer->active);
}
```

You may alternatively use the `as` method, which calls the `state` method under the hood
```php
/** @test */
public function customer_is_active()
{
    $customer = CustomerFactory::new()
        ->as('active')
        ->create();

    $this->assertTrue($customer->active);
}
```

Like Laravel's Factories, there is also a `states` method to allow you to use multiple states

```php
$customer = CustomerFactory::new()
    ->states('state1', 'state2', 'etc')
    ->create();

```

As Poser makes use of the built in Laravel factory methods, you can use the `afterMaking()`, `afterCreating()`, `afterMakingState()` and `afterCreatingState()` callbacks in your Laravel database factories as you always have done, and they will be called as you would expect.  

### After Creating

Similar to Laravel's model factories, Poser also offers an `afterCreating()` method that will accept a closure to run after the record(s) have been created.

**Example:**
- User belongs to a company
- Company has a single Main user (stored on company record)
  - setMainUser on Company is a function to just update the main_user_id column on Company

```php
CompanyFactory::new()
    ->afterCreating(\App\Company $company) {
        $company->setMainUser(UserFactory::new()->forCompany($company)->create());
    })->create();
```

So after the Company has been created, it will create a new user for that company, and set it as the main user for the company.

This will also work when using `times()` to create multiple companies, and it will create a new user for each created company

```php
CompanyFactory::times(3)
    ->afterCreating(\App\Company $company) {
        $company->setMainUser(UserFactory::new()->forCompany($company)->create());
    })->create();
```

Logic like this is likely to be commonly used, and this is where Poser being classed based helps, as you can store this
logic behind a function on the `CompanyFactory` class, as such:

```php
class CompanyFactory extends Factory {
    public function withMainUser()
    {
        return $this->afterCreating(function(Company $company) {
            $company->setMainUser(UserFactory::new()->forCompany($company)->create());
        });
    }
}
```

Allowing you to then just call `withMainUser()`

```php
CompanyFactory::times(3)
    ->withMainUser()
    ->create();
```

### Factory API

#### `::new()`
Creates a new instance of the factory. If you only want to create one model, use this to instantiate the class.

#### `::times($count)`
Creates a new instance of the factory, but informs the factory that you will be creating multiple models.
Use this to instantiate the class when you wish to create multiple entries in the database.

#### `->create(array $attributes)` or `()`
Similar to the Laravel factory `create` command, this will create the models, persisting them to the database.
You may pass an associative array of column names with desired values, which will be applied to the 
created models. You can optionally call create by invoking the Factory. This allows for a shorter syntax.

#### `->make(array $attributes)`
Similar to the Laravel factory `make` command, this will make the models without persisting them to the 
database. You may pass an associative array of column names with desired values, which will be applied to the 
created models.

#### `->withAttributes(array $attributes)`
You may pass an associative array of column names with desired values, which will be applied to the 
created models.

#### `->state(string $state)` or `->as(string $state)`
You may pass a factory state that you have defined in your laravel model factory, which will be applied
to the created models.

#### `->states(...$states)`
Similar to `->state(string $state)`, but allows you to pass in multiple states that will all be applied
to the created models.

#### `->withPivotAttributes(array $attributes)`
When working with Many-to-Many relationships, you may want to store data on the pivot table. You may use
this method to do so, passing in an associative array of column names with desired values. This should
be called on the related factory, not the root-level factory.

### `php artisan make:poser` API

If no arguments are passed to the command, Poser will attempt to create matching factories for every model in your 
application. It does this by looking at your `poser.models_namespace` config entry, and scanning for models in that given
namespace. You may call `make:poser` multiple times without fear of it overriding your existing factories; if it finds
that a given factory already exists, it will simply skip over it.

#### Individual Factories
You may optionally pass a name to the command, which corresponds to the name of the factory you want to create. For
instance, `php artisan make:poser UserFactory` would create a factory called `UserFactory` in the namespace defined
in your `poser.factories_namespace` config file.

#### The `-m` or `-model` Flag
If your model name is different to the name you wish to give your factory, you may pass a `-m` or `-model` flag, along 
with the name of the model that the factory will correspond to. So `php artisan make:poser ClientFactory -m Customer`
would create a factory called `ClientFactory`, but point it to the `Customer` model.

#### The `-f` or `-factory` Flag
You may pass `-f` or `-factory` to the command to optionally generate a corresponding [Laravel database factory](https://laravel.com/docs/database-testing#writing-factories). 
The database factory will take the form `[modelName]Factory`.

### Things to note
#### Models location
By default, Poser looks for your models in the `App` directory, which should be fine for most projects.
If you have your models in a different directory, you can let Poser know about it by editing the `models_namespace`
entry in the `poser.php` config file.

If you need to override the model location for a single instance, you can override the `$modelName` static variable
in your Factory class, passing it the fully qualified class name of the corresponding model. 

#### Factories location
By default, Poser will search the `Tests/Factories` directory for your Factory classes.
If you have your Factories in a different directory (eg: `Tests/Models/Factories`),
you can let Poser know about it by editing the `factories_namespace` entry in the poser.php config
file.

#### The `->create()` and `->make()` commands
You should call the `create` command at the end of the outermost Factory statement to cause it to persist to the
database. You do not need to call `create()` or `make()` on nested Factory statements, as Poser will do this for you.

The only exception to this is BelongsTo relationships, in which case you must call `create()` on nested Factory
statements.

If you like terse syntax, you can replace `->create()` with `()`, as the Factory `__invoke` function simply
calls `create()` under the hood:

```php
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(
            CustomerFactory::times(30)->withBooks(BookFactory::times(5))
        )();

    $this->assertCount(30, $user->customers);
    $this->assertCount(150, Book::all());
}
```

### Troubleshooting
#### I keep getting a `ModelNotBuiltException` when trying to access properties or functions on the model I created
This exception is thrown when you have forgotten to call `create()` or `()` on the factory. As such, you're actually
trying to access a property or function on the factory, not the model. Just pop `create()` or `()` on the end of the statement and it should all work as expected. Here's an example...

```php
/** @test */
public function the_user_has_a_name() {
    $user = UserFactory::new()->withAttributes([ 'name' => "John Doe" ])->withCustomers(10);
    
    $this->assertEquals("John Doe", $user->name); // Whoops! This will throw a ModelNotBuiltException
}
```

...and here's the solution
```php
/** @test */
public function the_user_has_a_name() {
    $user = UserFactory::new()->withAttributes([ 'name' => "John Doe" ])
        ->withCustomers(10)->create(); // <- note the call to `create()` at the end
    
    $this->assertEquals("John Doe", $user->name); // Hoorah! This will pass
}
```

#### When using magic binding, I get an `ArgumentsNotSatisfiableException`
This error is thrown when Poser cannot find a factory that satifies the requested relationship method call. So, imagine you called `UserFactory::new()->withCustomers(10)();`, but there was no `CustomerFactory`, Poser would throw this error. The solution is to create the Factory. In this case, we could call `php artisan make:poser CustomerFactory` from the terminal to automatically create the factory for us.

The other time this error can crop up is if your Parent Model's relationship method name is different to the Child Model name.
To illustrate, imaging that we have a `UserFactory` that has a `clients()` method. That method returns a has-many relationship for the `Customer` model, and you have a Poser `CustomerFactory`.

When we call `UserFactory::new()->withClients()()`, Poser understands that you're using the `clients()` method on the `User` model, but it can't find a corresponding `ClientFactory` (because, it is in fact called `CustomerFactory`). The solution to this is to resort to standard bindings. So our updated call would be:

```php
UserFactory::new()->withClients(CustomerFactory::times(10))();
```

## Changelog
Take a look at the `CHANGELOG.md` file for details on changes from update to update.

## Credits

- [Luke Raymond Downing](https://github.com/lukeraymonddowning)

Follow me on Twitter [@LukeDowning19](https://twitter.com/LukeDowning19) for updates!

## Security
If you discover any security-related issues, please email lukeraymonddowning@gmail.com instead of using the issue tracker.

## Testing
```shell script
composer install
composer test
```

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.

## Contributors ✨

Thanks goes to these wonderful people ([emoji key](https://allcontributors.org/docs/en/emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore-start -->
<!-- markdownlint-disable -->
<table>
  <tr>
    <td align="center"><a href="https://github.com/AlanHolmes"><img src="https://avatars2.githubusercontent.com/u/4289202?v=4" width="100px;" alt=""/><br /><sub><b>Alan Holmes</b></sub></a><br /><a href="https://github.com/lukeraymonddowning/poser/commits?author=AlanHolmes" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/andreich1980"><img src="https://avatars1.githubusercontent.com/u/17148882?v=4" width="100px;" alt=""/><br /><sub><b>AndrewP</b></sub></a><br /><a href="https://github.com/lukeraymonddowning/poser/commits?author=andreich1980" title="Documentation">📖</a> <a href="https://github.com/lukeraymonddowning/poser/commits?author=andreich1980" title="Tests">⚠️</a></td>
    <td align="center"><a href="https://github.com/veganista"><img src="https://avatars2.githubusercontent.com/u/405763?v=4" width="100px;" alt=""/><br /><sub><b>veganista</b></sub></a><br /><a href="#ideas-veganista" title="Ideas, Planning, & Feedback">🤔</a></td>
  </tr>
</table>

<!-- markdownlint-enable -->
<!-- prettier-ignore-end -->
<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors](https://github.com/all-contributors/all-contributors) specification. Contributions of any kind welcome!
