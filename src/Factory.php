<?php


namespace Lukeraymonddowning\Poser;

use Closure;
use App\User;
use Illuminate\Support\Str;
use Tests\Factories\UserFactory;
use Illuminate\Support\Collection;
use Tests\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Integer;
use Lukeraymonddowning\Poser\Exceptions\ModelNotBuiltException;
use Lukeraymonddowning\Poser\Exceptions\ArgumentsNotSatisfiableException;

abstract class Factory
{

    protected static
        $modelName = null,
        $relationshipPrefixes = ['with', 'for'];

    protected
        $count = 1,
        $saveMethodRelationships,
        $belongsToRelationships,
        $attributes = [],
        $pivotAttributes = [],
        $states = [];

    public $factory;

    /**
     * Returns a new instance of the given factory
     *
     * @return static
     */
    public static function new()
    {
        return new static();
    }

    /**
     * Returns a new instance of the given factory, and specifies the number of models that should be built
     * when `make()` or `create()` is called.
     *
     * @param int $count
     * @return static
     */
    public static function times($count)
    {
        $factory = new static();
        $factory->count = $count;

        return $factory;
    }

    public function __construct()
    {
        $this->saveMethodRelationships = collect([]);
        $this->belongsToRelationships = collect([]);
    }

    /**
     * Shorthand syntax for the `create()` function.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are created.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model|Model[]|Collection
     */
    public function __invoke($attributes = [])
    {
        return $this->create($attributes);
    }

    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, 'with')) {
            $this->handleSaveMethodRelationships($name, $arguments);

            return $this;
        }

        if (Str::startsWith($name, 'for')) {
            $this->handleBelongsToRelationships($name, $arguments);

            return $this;
        }

        throw new ModelNotBuiltException($this, $name, $this->getModelName());
    }

    public function __get($name)
    {
        throw new ModelNotBuiltException($this, $name, $this->getModelName());
    }

    /**
     * If specified, Poser will use these attributes on the model/s when it/they are created. If you also pass
     * attributes into the `make()` or `create()` commands, they will take precedence over attributes passed
     * in here.
     *
     * @param array $attributes An associative array of column names and values that should be inserted into the table
     *
     * @return $this
     */
    public function withAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * If specified, Poser will save the given attributes to the pivot table when dealing with Many-to-Many
     * relationships. It should be called on the relationship factory, not on the parent factory.
     *
     * @param array $attributes An associative array of column names and values that should be inserted into the pivot
     *                          table
     *
     * @return $this
     */
    public function withPivotAttributes($attributes)
    {
        $this->pivotAttributes = $attributes;

        return $this;
    }

    /**
     * Applies the given state to the model/s. This uses Laravel's factory states, so please see
     * https://laravel.com/docs/database-testing#creating-models for more details.
     *
     * @param string $state
     *
     * @return $this
     */
    public function as($state)
    {
        return $this->state($state);
    }

    /**
     * Applies the given state to the model/s. This uses Laravel's factory states, so please see
     * https://laravel.com/docs/database-testing#creating-models for more details.
     *
     * @param string $state
     *
     * @return $this
     */
    public function state($state)
    {
        $this->states[] = $state;

        return $this;
    }

    /**
     * Applies the given states to the model/s. This uses Laravel's factory states, so please see
     * https://laravel.com/docs/database-testing#creating-models for more details.
     *
     * @param string ...$states
     *
     * @return $this
     */
    public function states(...$states)
    {
        collect($states)->flatten()->each(function ($state) {
            $this->states[] = $state;
        });

        return $this;
    }

    /**
     * Persists the model/s to the database, then returns it/them.
     * This is also the stage where all requested relationships will be bound to the model/s.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are created.
     *
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model[]|\Illuminate\Database\Eloquent\Model
     */
    public function create($attributes = [])
    {
        $result = $this->make($attributes);

        $this->performActionToModels($result, function ($model) {
            $this->addBelongsToRelationships($model);
        });

        if ($result instanceof Collection) {
            $result->each(function ($model) {
                $model->save();
            });
        } elseif ($result instanceof Model) {
            $result->save();
        }

        $this->factory->callAfterCreating($result);

        if ($result instanceof Collection) {
            $result->each(function ($model) {
                $this->addSaveMethodRelationships($model);
            });
        } elseif ($result instanceof Model) {
            $this->addSaveMethodRelationships($result);
        }

        return $result;
    }

    /**
     * Builds and returns the model instances, but does not persist them to the database.
     * Often, you will want the create() method instead, as this will not handle model relationships.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are made.
     *
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model[]|\Illuminate\Database\Eloquent\Model
     */
    public function make($attributes = [])
    {
        if (empty($this->factory)) {
            $this->factory = factory($this->getModelName());
        }

        $this->factory->states($this->states);

        if ($this->count > 1) {
            $this->factory->times($this->count);
        }

        $result = $this->factory->make(array_merge($this->attributes, $attributes));

        return $result;
    }

    protected function handleSaveMethodRelationships($functionName, $arguments)
    {
        $this->saveMethodRelationships[$this->getRelationshipMethodName($functionName)] = $this->getModelDataFromFunctionArguments($functionName,
            $arguments);
    }

    protected function handleBelongsToRelationships($functionName, $arguments)
    {
        $this->belongsToRelationships[$this->getRelationshipMethodName($functionName)] = $this->getModelDataFromFunctionArguments($functionName,
            $arguments);
    }

    private function getRelationshipMethodName($functionName)
    {
        $prefix = collect(static::$relationshipPrefixes)->filter(function ($prefix) use ($functionName) {
            return Str::contains($functionName, $prefix);
        })->first();

        return Str::camel(Str::after($functionName, $prefix));
    }

    private function getModelDataFromFunctionArguments($functionName, $arguments)
    {
        if (isset($arguments[0]) && !is_int($arguments[0]) && !is_array($arguments[0])) {
            return $arguments[0];
        }

        $relationshipMethodName = $this->getRelationshipMethodName($functionName);
        $factory = $this->getFactoryFor($relationshipMethodName);

        if (empty($factory)) {
            throw new ArgumentsNotSatisfiableException(class_basename($this), $functionName, $relationshipMethodName, [
                $this->generateFactoryName($relationshipMethodName),
                $this->generateFactoryName($relationshipMethodName, "Factory")
            ]);
        }

        $factory = isset($arguments[0]) && is_int($arguments[0]) ? call_user_func($factory . '::times',
            $arguments[0]) : call_user_func($factory . '::new');

        if (isset($arguments[0]) && is_array($arguments[0])) {
            $factory->withAttributes($arguments[0]);
        }

        if (isset($arguments[1]) && is_array($arguments[1])) {
            $factory->withAttributes($arguments[1]);
        }

        return $factory;
    }

    protected function getFactoryFor($relationshipMethodName)
    {
        if (class_exists($this->generateFactoryName($relationshipMethodName))) {
            return $this->generateFactoryName($relationshipMethodName);
        }

        if (class_exists($this->generateFactoryName($relationshipMethodName, "Factory"))) {
            return $this->generateFactoryName($relationshipMethodName, "Factory");
        }

        return null;
    }

    private function generateFactoryName($relationshipMethodName, $suffix = "")
    {
        $factoryLocation = config('poser.factories_directory', "Tests\\Factories\\");

        return $factoryLocation . Str::studly(Str::singular($relationshipMethodName)) . $suffix;
    }

    private function performActionToModels($models, $closure)
    {
        if ($models instanceof Collection) {
            $models->each(function ($model) use ($closure) {
                $closure($model);
            });
        } elseif ($models instanceof Model) {
            $this->addBelongsToRelationships($models);
        }
    }

    protected function addSaveMethodRelationships($model)
    {
        $this->saveMethodRelationships->each(function ($relatedModels, $relationshipName) use ($model) {
            $pivotAttributes = collect($relatedModels instanceof Factory ? $relatedModels->pivotAttributes : []);
            $models = $relatedModels instanceof Factory ? $relatedModels->make() : $relatedModels;

            if ($models instanceof Model) {
                $models = collect([$models]);
            }

            $models->each(function ($relatedModel) use ($model, $relationshipName, $pivotAttributes) {
                $model->{$relationshipName}()->save($relatedModel, $pivotAttributes->toArray());
            });

            if ($relatedModels instanceof Factory) {
                $models->each(function ($model) use ($relatedModels) {
                    $relatedModels->addSaveMethodRelationships($model);
                });
            }
        });

        return $this;
    }

    protected function addBelongsToRelationships($model)
    {
        $this->belongsToRelationships->each(function ($owningModel, $relationshipName) use ($model) {
            $owningModel = $owningModel instanceof Factory ? $owningModel->create() : $owningModel;
            $model->{$relationshipName}()->associate($owningModel);
        });

        return $this;
    }

    protected function getModelName()
    {
        if (!empty(static::$modelName)) {
            return static::$modelName;
        }

        return config('poser.models_directory', "App\\") . Str::beforeLast(class_basename($this), "Factory");
    }
}
