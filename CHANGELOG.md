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
