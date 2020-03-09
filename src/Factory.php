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
        $withRelationships,
        $forRelationships,
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
        return self::times(1);
    }

    /**
     * Returns a new instance of the given factory, and specifies the number of models that should be built
     * when `make()` or `create()` is called.
     *
     * @param int $count
     * @return static
     */
    public static function times(int $count)
    {
        $factory = new static();
        $factory->count = $count;

        return $factory;
    }

    public function __construct()
    {
        $this->factory = factory($this->getModelName());
        $this->withRelationships = collect([]);
        $this->forRelationships = collect([]);
    }

    /**
     * Shorthand syntax for the `create()` function.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are created.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model|Model[]|Collection
     */
    public function __invoke(array $attributes = [])
    {
        return $this->create($attributes);
    }

    public function __call(string $name, array $arguments)
    {
        if (Str::startsWith($name, 'with')) {
            $this->handleWithRelationship($name, $arguments);

            return $this;
        }

        if (Str::startsWith($name, 'for')) {
            $this->handleForRelationship($name, $arguments);

            return $this;
        }

        throw new ModelNotBuiltException($this, $name, $this->getModelName());
    }

    public function __get(string $name)
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
    public function withAttributes(array $attributes)
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
    public function withPivotAttributes(array $attributes)
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
    public function as(string $state)
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
    public function state(string $state)
    {
        return $this->states($state);
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
        collect($states)->flatten()->each(
            function ($state) {
                $this->states[] = $state;
            }
        );

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
    public function create(array $attributes = [])
    {
        $result = $this->make($attributes);

        $returnFirstCollectionResultAtEnd = !$result instanceof Collection;
        $result = $returnFirstCollectionResultAtEnd ? collect([$result]) : $result;

        $result->each(
            function ($model) {
                $this->buildAllForRelationships($model);
                $model->save();
            }
        );

        $this->factory->callAfterCreating($returnFirstCollectionResultAtEnd ? $result->first() : $result);

        $result->each(
            function ($model) {
                $this->buildAllWithRelationships($model);
            }
        );

        return $returnFirstCollectionResultAtEnd ? $result->first() : $result;
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
    public function make(array $attributes = [])
    {
        if ($this->count > 1) {
            $this->factory->times($this->count);
        }

        return $this->factory->states($this->states)->make(array_merge($this->attributes, $attributes));
    }

    protected function handleWithRelationship(string $functionName, array $arguments)
    {
        $this->withRelationships[$this->getRelationshipMethodName($functionName)] = $this->buildRelationshipData(
            $functionName,
            $arguments
        );
    }

    protected function handleForRelationship(string $functionName, array $arguments)
    {
        $this->forRelationships[$this->getRelationshipMethodName($functionName)] = $this->buildRelationshipData(
            $functionName,
            $arguments
        );
    }

    protected function getRelationshipMethodName(string $functionName)
    {
        $prefix = collect(static::$relationshipPrefixes)->filter(
            function ($prefix) use ($functionName) {
                return Str::contains($functionName, $prefix);
            }
        )->first();

        return Str::camel(Str::after($functionName, $prefix));
    }

    protected function buildRelationshipData(string $functionName, array $arguments)
    {
        if ($this->factoryShouldBeHandledManually($arguments)) {
            return $arguments[0];
        }

        $factory = call_user_func(
            $this->getFactoryNameFromFunctionNameOrFail($functionName) . '::times',
            isset($arguments[0]) && is_int($arguments[0]) ? $arguments[0] : 1
        );

        collect($arguments)->filter(
            function ($argument) {
                return is_array($argument);
            }
        )->first(
            function ($attributes) use ($factory) {
                $factory->withAttributes($attributes);
            }
        );

        return $factory;
    }

    protected function factoryShouldBeHandledManually($arguments)
    {
        return isset($arguments[0]) && !is_int($arguments[0]) && !is_array($arguments[0]);
    }

    protected function getFactoryNameFromFunctionNameOrFail(string $functionName)
    {
        $relationshipMethodName = $this->getRelationshipMethodName($functionName);

        return collect(["", "Factory"])->map(
            function ($suffix) use ($relationshipMethodName) {
                return $this->getFactoryName($relationshipMethodName, $suffix);
            }
        )->filter(
            function ($class) {
                return class_exists($class);
            }
        )->whenEmpty(
            function () use ($functionName, $relationshipMethodName) {
                throw new ArgumentsNotSatisfiableException(
                    class_basename($this), $functionName,
                    $relationshipMethodName, [
                        $this->getFactoryName($relationshipMethodName),
                        $this->getFactoryName($relationshipMethodName, "Factory")
                    ]
                );
            }
        )->first();
    }

    protected function getFactoryName(string $relationshipMethodName, string $suffix = "")
    {
        $factoryLocation = config('poser.factories_directory', "Tests\\Factories\\");

        return $factoryLocation . Str::studly(Str::singular($relationshipMethodName)) . $suffix;
    }

    protected function buildAllWithRelationships($model)
    {
        $this->withRelationships->each(
            function ($relatedModels, $relationshipName) use ($model) {
                $models = $relatedModels instanceof Factory ? $relatedModels->make() : $relatedModels;

                if ($models instanceof Model) {
                    $models = collect([$models]);
                }

                $models->each(
                    function ($relatedModel) use ($model, $relationshipName, $relatedModels) {
                        $model->{$relationshipName}()->save($relatedModel, $relatedModels->pivotAttributes ?? []);

                        if ($relatedModels instanceof Factory) {
                            $relatedModels->buildAllWithRelationships($relatedModel);
                        }
                    }
                );
            }
        );

        return $this;
    }

    protected function buildAllForRelationships(Model $model)
    {
        $this->forRelationships->each(
            function ($owningModel, $relationshipName) use ($model) {
                $model->{$relationshipName}()->associate(
                    $owningModel instanceof Factory ? $owningModel->create() : $owningModel
                );
            }
        );

        return $this;
    }

    protected function getModelName()
    {
        return static::$modelName ??
            config('poser.models_directory', "App\\") . Str::beforeLast(class_basename($this), "Factory");
    }
}
