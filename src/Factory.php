<?php


namespace Lukeraymonddowning\Poser;

use Mockery\Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Lukeraymonddowning\Poser\Exceptions\ModelNotBuiltException;
use Lukeraymonddowning\Poser\Exceptions\ArgumentsNotSatisfiableException;

abstract class Factory
{

    protected static
        $modelName = null,
        $relationshipPrefixes = ['with', 'has', 'for'];

    protected
        $count = 1,
        $withRelationships,
        $forRelationships,
        $afterCreating,
        $attributes = [],
        $pivotAttributes = [],
        $states = [],
        $createdInstance;

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
        $this->afterCreating = collect([]);
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
        if (Str::startsWith($name, ['with', 'has'])) {
            $this->handleWithRelationship($name, $arguments);

            return $this;
        }

        if (Str::startsWith($name, 'for')) {
            $this->handleForRelationship($name, $arguments);

            return $this;
        }

        try {
            $model = $this->createdInstance ?? $this->create();

            return call_user_func_array([$model, $name], $arguments);
        } catch (Exception $e) {
            throw new ModelNotBuiltException($this, $name, $this->getModelName());
        }
    }

    public function __get(string $name)
    {
        try {
            $model = $this->createdInstance ?? $this->create();

            return $model->$name;
        } catch (Exception $e) {
            throw new ModelNotBuiltException($this, $name, $this->getModelName());
        }
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
    public function withAttributes(...$attributes)
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
    public function create(...$attributes)
    {
        $result = $this->make(...$attributes);

        $returnFirstCollectionResultAtEnd = !$result instanceof Collection;
        $result = $returnFirstCollectionResultAtEnd ? collect([$result]) : $result;

        $result->each(
            function ($model) {
                $this->buildAllForRelationships($model);
                $model->save();
            }
        );

        $this->factory->callAfterCreating($returnFirstCollectionResultAtEnd ? $result->first() : $result);
        $this->processAfterCreating($result);

        $result->each(
            function ($model) {
                $this->buildAllWithRelationships($model);
            }
        );

        $this->createdInstance = $returnFirstCollectionResultAtEnd ? $result->first() : $result;

        return $this->createdInstance;
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
    public function make(...$attributes)
    {
        $models = collect([]);

        for ($i = 0; $i < $this->count; $i++) {
            $attributeMethodData = $this->getDesiredAttributeData($this->attributes, $i);
            $providedOverrideAttributes = $this->getDesiredAttributeData($attributes, $i);

            $models->push(
                $this->factory->states($this->states)->make(
                    array_merge($attributeMethodData, $providedOverrideAttributes)
                )
            );
        }

        return $models->count() > 1 ? $models : $models->first();
    }

    /**
     * Provide a closure that will be called after the factory has created the record(s)
     *
     * @param \Closure $closure
     *
     * @return $this
     */
    public function afterCreating(\Closure $closure)
    {
        $this->afterCreating->push($closure);

        return $this;
    }

    /**
     * Given an array of attribute sets, returns the desired set if available, else the first one available
     * either through looping or retrieving the first one, or an empty array.
     *
     * @param array $attributes   The array of arrays from which to extract an attribute set.
     * @param int   $desiredIndex The ideal index of the attribute set you would like
     * @return array|mixed
     */
    protected function getDesiredAttributeData(array $attributes, int $desiredIndex)
    {
        if (count($attributes) > 0) {
            $desiredIndex -= (count($attributes) * floor(($desiredIndex) / count($attributes)));
        }

        return $attributes[$desiredIndex] ?? $attributes[0] ?? [];
    }

    /**
     * Prepares a `with[RelationshipName]` by parsing it and storing it until the factory calls `create()`.
     *
     * @param string $functionName The name of the function that was called by the user.
     * @param array  $arguments    The arguments that were passed to the function.
     */
    protected function handleWithRelationship(string $functionName, array $arguments)
    {
        $this->withRelationships[] = [
            $this->getRelationshipMethodName($functionName),
            $this->buildRelationshipData(
                $functionName,
                $arguments
            )
        ];
    }

    /**
     * Prepares a `for[RelationshipName]` by parsing it and storing it until the factory calls `create()`.
     *
     * @param string $functionName The name of the function that was called by the user.
     * @param array  $arguments    The arguments that were passed to the function.
     */
    protected function handleForRelationship(string $functionName, array $arguments)
    {
        $this->forRelationships[] = [
            $this->getRelationshipMethodName($functionName),
            $this->buildRelationshipData(
                $functionName,
                $arguments
            )
        ];
    }

    /**
     * Parses the given function name to calculate the relationship method that should exist
     * on the factory's Model. For example, if `withCustomers` was passed to this function,
     * `customers` would be returned.
     *
     * @param string $functionName The name of the function that was called by the user.
     * @return string The relationship method that should exist on the Model.
     */
    protected function getRelationshipMethodName(string $functionName)
    {
        $prefix = collect(static::$relationshipPrefixes)->filter(
            function ($prefix) use ($functionName) {
                return Str::contains($functionName, $prefix);
            }
        )->first();

        return Str::camel(Str::after($functionName, $prefix));
    }

    /**
     * Works out how a relationship method should be handled. If a Poser Factory, Laravel factory
     * Model or Collection of models is passed as an argument, it will simply be returned.
     * However, if an integer is passed, this function is in charge of working out the factory
     * that should be returned, and using the integer to set the number of models that should be
     * created.
     *
     * @param string $functionName The name of the relationship method that was called by the user.
     * @param array  $arguments    Any arguments passed to the relationship method.
     * @throws ArgumentsNotSatisfiableException
     * @return mixed Usually a Poser Factory, but could be a Model or Collection of Models.
     */
    protected function buildRelationshipData(string $functionName, array $arguments)
    {
        if ($this->factoryShouldBeHandledManually($arguments)) {
            return $arguments[0];
        }

        $factory = call_user_func(
            $this->getFactoryNameFromMethodNameOrFail($functionName) . '::times',
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

    /**
     * Decides if the Poser factory should be handled manually or if an automated factory should be created.
     *
     * @param array $arguments The arguments passed to the relationship method.
     * @return bool True if the factory should be handled manually, otherwise false.
     */
    protected function factoryShouldBeHandledManually(array $arguments)
    {
        return isset($arguments[0]) && !is_int($arguments[0]) && !is_array($arguments[0]);
    }

    /**
     * Given a function name, calculates the fully qualified Poser Factory name that matches and returns it.
     * For example, `withCustomers()` should return the fully qualified `CustomerFactory`.
     *
     * @param string $methodName The name of the method that was called by the user.
     * @throws ArgumentsNotSatisfiableException
     * @return string The Poser factory class name that matches the function name.
     */
    protected function getFactoryNameFromMethodNameOrFail(string $methodName)
    {
        $relationshipMethodName = $this->getRelationshipMethodName($methodName);

        return collect(["", "Factory"])->map(
            function ($suffix) use ($relationshipMethodName) {
                return $this->getFactoryName($relationshipMethodName, $suffix);
            }
        )->filter(
            function ($class) {
                return class_exists($class);
            }
        )->whenEmpty(
            function () use ($methodName, $relationshipMethodName) {
                throw new ArgumentsNotSatisfiableException(
                    class_basename($this), $methodName,
                    $relationshipMethodName, [
                        $this->getFactoryName($relationshipMethodName),
                        $this->getFactoryName($relationshipMethodName, "Factory")
                    ]
                );
            }
        )->first();
    }

    /**
     * Constructs and returns fully qualified Poser factory name.
     *
     * @param string $relationshipMethodName The relationship method found on the Laravel Model.
     * @param string $suffix                 A suffix that should be applied to the Poser Factory name.
     * @return string The constructed fully qualified Poser factory name.
     */
    protected function getFactoryName(string $relationshipMethodName, string $suffix = "")
    {
        return factoriesNamespace() . Str::studly(Str::singular($relationshipMethodName)) . $suffix;
    }

    /**
     * Iterates over the requested `with[RelationshipMethod]` relationships and adds them to the
     * given model/s.
     *
     * @param Model|Collection $model A Model or collection of models that should be given the relationships.
     * @return $this
     */
    protected function buildAllWithRelationships($model)
    {
        $this->withRelationships->each(
            function ($data) use ($model) {
                $relationshipName = $data[0];
                $relatedModels = $data[1];
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

                if ($relatedModels instanceof Factory) {
                    $relatedModels->processAfterCreating($models, $model);
                }
            }
        );

        return $this;
    }

    /**
     * Iterates over the requested `for[RelationshipMethod]` relationships and adds them to the
     * given model/s.
     *
     * @param Model|Collection $model A Model or collection of models that should be given the relationships.
     * @return $this
     */
    protected function buildAllForRelationships(Model $model)
    {
        $this->forRelationships->each(
            function ($data) use ($model) {
                $relationshipName = $data[0];
                $owningModel = $data[1];
                $model->{$relationshipName}()->associate(
                    $owningModel instanceof Factory ? $owningModel->create() : $owningModel
                );
            }
        );

        return $this;
    }

    /**
     * Process any closures added to the afterCreating collection
     *
     * The created model will be passed into the closure as the first param
     *
     * @param \Illuminate\Support\Collection $result
     * @param Model|null                     $model
     */
    protected function processAfterCreating(Collection $result, Model $model = null)
    {
        $result->each(
            function ($createdRelation) use ($model) {
                $this->afterCreating->each(
                    function ($closure) use ($createdRelation, $model) {
                        $closure($createdRelation, $model);
                    }
                );
            }
        );
    }

    /**
     * Returns that model class name that corresponds to this Poser factory.
     *
     * @return string
     */
    protected function getModelName()
    {
        return static::$modelName ??
            modelsNamespace() . Str::beforeLast(class_basename($this), "Factory");
    }
}
