# 2.8.0-beta
Relationship methods may now be called statically to create a new Factory instance for a single
model, instead of calling `::new()`.

# 2.7.0-beta
Added the `withoutEvents()` method, which will disable events from being fired during model
creation.

# 2.6.2-beta
Fixed case sensitivity issues on Linux systems. Added exit codes for the `make:poser` command.
Added a new `factories_location` key to `poser` config.

# 2.6.1-beta
Previously, invoking would not allow multiple attributes to be passed. This PATCH release fixes that.

# 2.6.0-beta
Added support for default attributes

# 2.5.0-beta
Added multiple attribute set support for the `withPivotAttributes()` method, similar to standard attributes in 2.4.0-beta.

# 2.4.0-beta
Sometimes, you want to specify different arguments for each model you create. For example, imagine we wanted to create 30 users, 10 called "Bob", 10 called "Barry" and 10 called "Gordon". Poser now allows that with the following syntax:

```php
UserFactory::times(30)->create(["name" => "Bob"], ["name" => "Barry"], ["name" => "Gordon"]);
```

Check out README.md for more information

# 2.3.0-beta
- Poser is now able to call `create` automatically when a method or property that should belong to the created model/collection
is accessed. This is especially useful for `for[RealtionshipMethodName]` relationships, which used to have to be created. Now you
can treat them the same as their `with` counterparts. Please note that this will not work if you never access a property or method 
from the model in your tests. In that case, simply use the `create` or `()` method calls.

# 2.0.1-beta
- The `poser.models_directory` config entry has been renamed to `poser.models_namespace`. Whilst the former will still work,
it is deprecated and should be replaced with the latter. It will be removed completely in the next MAJOR version.
- The `poser.factories_directory` config entry has been renamed to `poser.factories_namespace`. Whilst the former will still work,
it is deprecated and should be replaced with the latter. It will be removed completely in the next MAJOR version.
