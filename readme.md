# Poser

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Laravel Class-based Model Factories in literal seconds!

## Install
First, install into your Laravel project using composer.

`composer require lukeraymonddowning/poser`

Next, publish the Poser config file by calling

`php artisan vendor:publish`

## Usage
Poser takes all of the boilerplate out of writing [class-based model factories](https://tighten.co/blog/tidy-up-your-tests-with-class-based-model-factories).
To get started, install Poser and go to your test suite. Please note: Poser uses the database (obviously), so make sure
your test class extends Laravel's TestCase, not PhpUnit's.

### The Basics
Let's imagine you have a user model that has many customers...
```
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
called 'UserFactory' (you can also just call it 'User', but we think the 'Factory' suffix helps), and a class
called 'CustomerFactory'. Both of these classes should extend the 'Poser/Factory' abstract class.

Now, head to the test you want to write, and type the following:

```
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
'withCustomers' method: Poser was able to intelligently decide what we were trying to do.

For HasOne or HasMany relationships, you can simply prepend 'with' to the relationship method in
the model (eg: the customers() method in the User model becomes 'withCustomers' in the tests),
and Poser will do the rest. 

Let's add a little more complexity: each customer can own many books...

```
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

So far, so good. Let's create another factory class, this time called 'BookFactory', 
that again extends Poser's abstract 'Factory' class. That's all there is to it! Modify your original
test to give our customers 5 books each...

```
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
If your model relationship method name (ie: the 'customers()' method on our 'User' model) is the same
or a plural version of our Factory class (ie: 'CustomerFactory'), then we can take advantage of Magic Bindings
in Poser.

Let's take another look at our User/Customer example.

```
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(CustomerFactory::times(30))
        ->create();

    $this->assertCount(30, $user->customers);
}
```

Poser is smart enough to be able to work out that 'withCustomers' is a reference to the CustomerFactory,
and allows us to rewrite our test like this:

```
/** @test */
public function user_has_customers()
{
    $user = UserFactory::new()
        ->withCustomers(30)
        ->create();

    $this->assertCount(30, $user->customers);
}
```

The first argument passed to 'withCustomers()' is the number of customers we want to create, in this case: 30.

Imagine, for a contrived example, that every customer should be called "Joe Bloggs". We can pass a second
argument to 'withCustomers()' that defines an associative array of column names and values, just like we
do with the 'create()', 'make()' and 'withAttributes()' methods:

```
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

For HasOne relationships, like our User's Address, we can do very much the same:

```
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
```
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

Let's now put this all together, and demonstrate how simple it is to world build in Poser. Imagine we
want 10 Users, each with an Address and 20 customers. Each customer should have 5 books. That should 
be 10 Users, 10 Addresses, 200 Customers and 1000 Books. Check it out:

```
/** @test */
public function users_with_addresses_can_have_customers_with_books() {
    UserFactory::times(10)
               ->withAddress()
               ->withCustomers(CustomerFactory::times(20)->withBooks(5))();

    $this->assertCount(1000, Book::all());
    $this->assertCount(200, Customer::all());
    $this->assertCount(10, User::all());
    $this->assertCount(10, Address::all());
}
```

Let's break down this code. First, we ask the UserFactory to create 10 users, and pass it the
'withAddress()' function. Poser is able to find the AddressFactory, so it automatically instantiates
it for us and gives each user an Address.

Next, we call 'withCustomers()'. Because we want to specify additional parameters for each Customer,
we instantiate CustomerFactory directly, asking for 20 at a time. We then chain 'withBooks()' onto
the CustomerFactory, simply passing the integer 5. Poser looks for a BookFactory, which is finds,
and automatically calls 'BookFactory::times(5)' under the hood.

Finally, we complete the statement by invoking the UserFactory with '()'. This is a shorthand syntax
for calling 'create()' on the UserFactory.

For reference, the same test using Laravel's built in factories looks like this:

```
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

### Belongs To Relationships
What if we want to describe the inverse, a BelongsTo relationship? Poser makes this easy too. Instead of 
prepending 'with', we can prepend 'for'. Let's take another look at our examples. Say we wanted to 
request that a customer is given a user. Simply do this:

```
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

### Factory API

#### ::new()
Creates a new instance of the factory. If you only want to create one model, use this to instantiate the class.

#### ::times($count)
Creates a new instance of the factory, but informs the factory that you will be creating multiple models.
Use this to instantiate the class when you wish to create multiple entries in the database.

#### ->create(array $attributes)
Similar to the Laravel factory 'create' command, this will create the models, persisting them to the database.
You may pass an associative array of column names with desired values, which will be applied to the 
created models.

#### ->make(array $attributes)
Similar to the Laravel factory 'make' command, this will make the models without persisting them to the 
database. You may pass an associative array of column names with desired values, which will be applied to the 
created models.

#### ->withAttributes(array $attributes)
You may pass an associative array of column names with desired values, which will be applied to the 
created models.

### Things to note
#### Models location
By default, Poser looks for your models in the 'App' directory, which should be fine for most projects.
If you have your models in a different directory, you can let Poser know about it by editing the 'models_directory'
entry in the poser.php config file.

If you need to override the model location for a single instance, you can override the '$modelName' static variable
in your Factory class, passing it the fully qualified class name of the corresponding model. 

#### The ->create() and ->make() commands
You should call the create command at the end of the outermost Factory statement to cause it to persist to the
database. You do not need to call create() or make() on nested Factory statements, as Poser will do this for you.

The only exception to this is BelongsTo relationships, in which case you must call create() on nested Factory
statements.

If you like terse syntax, you can replace ->create() with (), as the Factory __invoke function simply
calls 'create()' under the hood:

```
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

## Credits

- [Luke Raymond Downing](https://github.com/lukeraymonddowning)

Follow me on Twitter [@LukeDowning19](https://twitter.com/LukeDowning19) for updates!

## Security
If you discover any security-related issues, please email lukeraymonddowning@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
