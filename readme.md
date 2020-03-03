# poser

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
you're test class extends Laravel's TestCase, not PhpUnit's.

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

### ->withAttributes(array $attributes)
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

## Security
If you discover any security-related issues, please email lukeraymonddowning@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
