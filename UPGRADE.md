# Upgrade Guide

This guide will help you update your app built using Poser when moving up MAJOR version numbers.
As Poser uses [semantic versioning](https://www.google.com/url?sa=t&rct=j&q=&esrc=s&source=web&cd=1&cad=rja&uact=8&ved=2ahUKEwiDj7vJ94foAhXtTRUIHeiSAiwQFjAAegQIARAB&url=https%3A%2F%2Fsemver.org%2F&usg=AOvVaw2wqeU7SPQk7aq7nuXGCrz-)
there is no need to make any upgrade changes unless the major version number increases.

## 3.x.x-beta to 1.0.0
Expected upgrade time: 0 seconds

This is an update by name only. No breaking changes have been added. Party on, Garth!

## 2.x.x-beta - 3.x.x-beta
Expected upgrade time: 30 seconds

This update adds a new config entry to the `config/poser.php` file that enables better compatibility with Linux systems. You may either republish the config file using `php artisan vendor:publish --tag=poser` or add the entry yourself:

```php
return [

  // Previous config entries
  
  "factories_location" => "tests/Factories/"
  
];
```

## 1.x.x-beta - 2.x.x-beta
Expected upgrade time: 0 minutes

This update is mainly a cleanup. It refactors the abstract Factory class to be more readable and 
easier to change in the future. No public methods have changed, so if you have not overridden 
any protected properties or methods, you do not need to make any changes.

### Changes to protected properties

- `$saveMethodRelationships` is now called `$withRelationships`.
- `$belongsToRelationships` is now called `$forRelationships`.

### Changes to protected methods

- `handleSaveMethodRelationships` is now called `handleWithRelationship`.
- `handleBelongsToRelatioships` is now called `handleForRelationship`.
- `getModelDataFromFunctionArguments` is now called `buildRelationshipData`.
- `getFactoryFor` is now called `getFactoryNameFromMethodNameOrFail`. It will also now throw an `ArgumentsNotSatisfiableException` if it cannot find the corresponding factory.
- `addSaveMethodRelationships` is now called `buildAllWithRelationships`.
- `addBelongsToRelationships` is now called `buildAllForRelationships`.
