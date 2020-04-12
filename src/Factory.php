<?php


namespace Lukeraymonddowning\Poser;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Mockery\Exception;
use ReflectionException;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Lukeraymonddowning\Poser\Tests\Models\User;
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
        $createdInstance,
        $defaultsToIgnore,
        $withEvents = true,
        $assertions,
        $phpUnit;

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

        $factory->phpUnit = $factory->getPhpUnit();

        return $factory;
    }

    public function __construct()
    {
        $this->factory = factory($this->getModelName());
        $this->withRelationships = collect([]);
        $this->forRelationships = collect([]);
        $this->afterCreating = collect([]);
        $this->defaultsToIgnore = collect([]);
        $this->assertions = collect([]);
    }

    /**
     * Shorthand syntax for the `create()` function.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are created.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model|Model[]|Collection
     */
    public function __invoke(...$attributes)
    {
        return $this->create(...$attributes);
    }

    public static function __callStatic($name, $arguments)
    {
        $factory = self::new();

        return $factory->$name(...$arguments);
    }

    public function __call(string $name, array $arguments)
    {
        if ($this->handleRelationship($name, $arguments)) {
            return $this;
        }

        if (Str::startsWith($name, 'assert')) {
            return $this->handleAssertion($name, $arguments);
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
    public function withPivotAttributes(...$attributes)
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

    public function withoutDefaults(...$defaultsToIgnore)
    {
        if (empty($defaultsToIgnore)) {
            $this->defaultsToIgnore->push("*");
        }

        $this->defaultsToIgnore = $this->defaultsToIgnore->merge(collect($defaultsToIgnore));

        return $this;
    }

    public function withoutEvents()
    {
        $this->withEvents = false;

        return $this;
    }

    /**
     * Persists the model/s to the database, then returns it/them.
     * This is also the stage where all requested relationships will be bound to the model/s.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are created.
     *
     * @return Collection|\Illuminate\Database\Eloquent\Collection|Model[]|Model
     */
    public function create(...$attributes)
    {
        $this->handleDefaultRelationships();

        $result = $this->make(...$attributes);

        $returnFirstCollectionResultAtEnd = !$result instanceof Collection;
        $result = $returnFirstCollectionResultAtEnd ? collect([$result]) : $result;

        $result->each(
            function ($model) {
                $this->buildAllForRelationships($model);
                $this->withEvents ? $this->saveModel($model) : $this->saveModelWithoutEvents($model);
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

        $this->processAssertions();

        return $this->createdInstance;
    }

    protected function saveModel($model)
    {
        $model->save();
    }

    protected function saveModelWithoutEvents($model)
    {
        $model::withoutEvents(
            function () use ($model) {
                $this->saveModel($model);
            }
        );
    }

    /**
     * Builds and returns the model instances, but does not persist them to the database.
     * Often, you will want the create() method instead, as this will not handle model relationships.
     *
     * @param array $attributes An associative array of column names and values, which will be applied to the model/s
     *                          when it/they are made.
     *
     * @return Collection|\Illuminate\Database\Eloquent\Collection|Model[]|Model
     */
    public function make(...$attributes)
    {
        $models = collect([]);

        for ($i = 0; $i < $this->count; $i++) {
            $attributeMethodData = $this->getDesiredAttributeData($this->attributes, $i);
            $providedOverrideAttributes = $this->getDesiredAttributeData($attributes, $i);

            $model = $this->factory->states($this->states)->make(
                array_merge($attributeMethodData, $providedOverrideAttributes)
            );

            $models->push($model);
        }

        return $models->count() > 1 ? $models : $models->first();
    }

    /**
     * Provide a closure that will be called after the factory has created the record(s)
     *
     * @param Closure $closure
     *
     * @return $this
     */
    public function afterCreating(Closure $closure)
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
     * @param string $functionName
     * @param array  $arguments
     * @return bool True if the relationship was handled, or false if it couldn't be handled.
     */
    protected function handleRelationship(string $functionName, array $arguments)
    {
        return $this->handleWithRelationship($functionName, $arguments) ||
            $this->handleForRelationship($functionName, $arguments);
    }

    /**
     * Prepares a `with[RelationshipName]` by parsing it and storing it until the factory calls `create()`.
     *
     * @param string $functionName The name of the function that was called by the user.
     * @param array  $arguments    The arguments that were passed to the function.
     * @return bool True if the relationship was handled, else false.
     */
    protected function handleWithRelationship(string $functionName, array $arguments)
    {
        return Str::startsWith($functionName, ['with', 'has']) ?
            (bool)$this->addRelationship($this->withRelationships, $functionName, $arguments) :
            false;
    }

    /**
     * Prepares a `for[RelationshipName]` by parsing it and storing it until the factory calls `create()`.
     *
     * @param string $functionName The name of the function that was called by the user.
     * @param array  $arguments    The arguments that were passed to the function.
     * @return bool True if the relationship was handled, else false.
     */
    protected function handleForRelationship(string $functionName, array $arguments)
    {
        return Str::startsWith($functionName, 'for') ?
            (bool)$this->addRelationship($this->forRelationships, $functionName, $arguments) :
            false;
    }

    protected function addRelationship(Collection $relationshipArray, string $functionName, array $arguments)
    {
        return $relationshipArray->push(
            new Relationship(
                $this->getRelationshipMethodName($functionName),
                $this->buildRelationshipData(
                    $functionName,
                    $arguments
                )
            )
        );
    }

    protected function handleDefaultRelationships()
    {
        $this->getDefaultMethods()->each(
            function ($defaultMethod) {
                if (!$this->mayHandleDefaultRelationship($defaultMethod)) {
                    return;
                }

                $this->handleRelationship(
                    $this->stripDefaultFromMethodName($defaultMethod),
                    [call_user_func([$this, $defaultMethod])]
                );
            }
        );
    }

    protected function mayHandleDefaultRelationship($defaultMethodName)
    {
        $relationshipMethodName = $this->getRelationshipMethodName(
            $this->stripDefaultFromMethodName($defaultMethodName)
        );

        return !$this->isInIgnoreList($relationshipMethodName) &&
            collect([$this->withRelationships, $this->forRelationships])
                ->filter(
                    function ($relationships) use ($relationshipMethodName) {
                        return $relationships->filter(
                            function (Relationship $withRelationship) use ($relationshipMethodName) {
                                return $withRelationship->getFunctionName() == $relationshipMethodName;
                            }
                        )->isNotEmpty();
                    }
                )->isEmpty();
    }

    protected function isInIgnoreList(string $relationshipMethodName)
    {
        return $this->defaultsToIgnore->contains("*") || $this->defaultsToIgnore->contains($relationshipMethodName);
    }

    /**
     * @throws ReflectionException
     * @return Collection
     */
    protected function getDefaultMethods(): Collection
    {
        $mirror = new ReflectionClass($this);

        return collect($mirror->getMethods(ReflectionMethod::IS_PUBLIC))
            ->map(
                function ($method) {
                    return $method->name;
                }
            )->filter(
                function ($method) {
                    return Str::startsWith($method, 'default');
                }
            );
    }

    /**
     * @param string $methodName The method name to strip the default keyword from.
     * @return string The method name without the 'default' prefix, converted to camel case.
     */
    public function stripDefaultFromMethodName(string $methodName)
    {
        return Str::camel(Str::after($methodName, 'default'));
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

        $factory->withAttributes(
            ...collect($arguments)->filter(
            function ($argument) {
                return is_array($argument);
            }
        )->toArray()
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
            function (Relationship $relationship) use ($model) {
                $models = $relationship->getData() instanceof Factory ? $relationship->getData()->make(
                ) : $relationship->getData();

                if ($models instanceof Model) {
                    $models = collect([$models]);
                }

                $models->each(
                    function ($relatedModel, $index) use ($model, $relationship) {
                        $model->{$relationship->getFunctionName()}()->save(
                            $relatedModel,
                            $this->getDesiredAttributeData(
                                isset($relationship->getData()->pivotAttributes) ? $relationship->getData(
                                )->pivotAttributes : [],
                                $index
                            )
                        );

                        if ($relationship->getData() instanceof Factory) {
                            $relationship->getData()->buildAllWithRelationships($relatedModel);
                        }
                    }
                );

                if (($factory = $relationship->getData()) instanceof Factory) {
                    $factory->createdInstance = $models->count() == 1 ? $models->first() : $models;
                    $factory->processAfterCreating($models, $model);
                    $factory->processAssertions();
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
            function (Relationship $relationship) use ($model) {
                $cachedLocation = "PoserForRelationship_" . $relationship->getFunctionName();
                if (!isset($this->$cachedLocation)) {
                    $this->$cachedLocation = $relationship->getData() instanceof Factory ? $relationship->getData()
                                                                                                        ->create(
                                                                                                        ) : $relationship->getData(
                    );
                }
                $model->{$relationship->getFunctionName()}()->associate($this->$cachedLocation);
            }
        );

        return $this;
    }

    /**
     * Process any closures added to the afterCreating collection
     *
     * The created model will be passed into the closure as the first param
     *
     * @param Collection $result
     * @param Model|null $model
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

    protected function handleAssertion(string $name, array $arguments)
    {
        $this->assertions->push(new Assertion($name, $arguments));

        return $this;
    }

    protected function processAssertions()
    {
        $this->assertions->each(
            function (Assertion $assertion) {
                if (!($this->phpUnit) instanceof TestCase) {
                    // TODO: Throw exception
                }

                $compare = $assertion->getArguments()[0];
                $check = $assertion->getArguments()[1] ?? $assertion->getArguments()[0];

                if (is_callable($check)) {
                    $mirror = new \ReflectionFunction($check);

                    if ($mirror->getNumberOfParameters() > 0) {
                        $type = (optional($mirror->getParameters()[0]->getType())->getName() ?? Model::class);

                        if (is_subclass_of($type, Model::class) && !$this->createdInstance instanceof Model) {
                            $this->createdInstance->each(
                                function (Model $model) use ($assertion, $compare, $check) {
                                    $this->callPhpUnitMethodOnCallable($assertion, $compare, $check, $model);
                                }
                            );
                        } else {
                            $this->callPhpUnitMethodOnCallable($assertion, $compare, $check, $this->createdInstance);
                        }
                    }
                } elseif (is_string($check)) {
                    if ($this->createdInstance instanceof Model) {
                        $this->callPhpUnitMethodOnModel(
                            $assertion,
                            $compare,
                            $check,
                            $this->createdInstance
                        );
                    } else {
                        $this->createdInstance->each(
                            function (Model $model) use ($assertion, $compare, $check) {
                                $this->callPhpUnitMethodOnModel(
                                    $assertion,
                                    $compare,
                                    $check,
                                    $model
                                );
                            }
                        );
                    }
                }
            }
        );
    }

    protected function callPhpUnitMethodOnCallable(Assertion $assertion, $compare, $check, $model)
    {
        if (count($assertion->getArguments()) > 1) {
            $this->phpUnit->{$assertion->getAssertionName()}(
                $compare,
                $check($model)
            );
        } else {
            $this->phpUnit->{$assertion->getAssertionName()}(
                $check($model)
            );
        }
    }

    protected function callPhpUnitMethodOnModel(Assertion $assertion, $compare, $check, Model $model)
    {
        if (count($assertion->getArguments()) > 1) {
            $this->phpUnit->{$assertion->getAssertionName()}(
                $compare,
                $model->{$check}
            );
        } else {
            $this->phpUnit->{$assertion->getAssertionName()}(
                $model->{$check}
            );
        }
    }

    protected function getPhpUnit()
    {
        $phpUnit = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT))
            ->first(
                function ($trace) {
                    return ($trace['object'] ?? null) instanceof TestCase;
                }
            );

        return $phpUnit['object'] ?? null;
    }
}
