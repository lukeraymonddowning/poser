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
use Lukeraymonddowning\Poser\Exceptions\ArgumentsNotSatisfiableException;

abstract class Factory {

    protected static $modelName = null;
    private static $relationshipPrefixes = ['with', 'for'];

    protected
        $count = 1,
        $saveMethodRelationships,
        $belongsToRelationships,
        $attributes = [],
        $states = [];

    public static function new()
    {
        return new static();
    }

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

    public function __invoke($attributes = [])
    {
        return $this->create($attributes);
    }

    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, 'with')) {
            $this->handleSaveMethodRelationships($name, $arguments);
        }

        if (Str::startsWith($name, 'for')) {
            $this->handleBelongsToRelationships($name, $arguments);
        }

        return $this;
    }

    protected function handleSaveMethodRelationships($functionName, $arguments)
    {
        $this->saveMethodRelationships[$this->getRelationshipMethodName($functionName)] = $this->getModelDataFromFunctionArguments($functionName, $arguments);
    }

    protected function handleBelongsToRelationships($functionName, $arguments)
    {
        $this->belongsToRelationships[$this->getRelationshipMethodName($functionName)] = $this->getModelDataFromFunctionArguments($functionName, $arguments);
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
        if (isset($arguments[0]) && !is_int($arguments[0]) && !is_array($arguments[0]))
            return $arguments[0];

        $factory = $this->getFactoryFor($this->getRelationshipMethodName($functionName));

        if (empty($factory))
            throw new ArgumentsNotSatisfiableException();


        $factory = isset($arguments[0]) && is_int($arguments[0]) ? call_user_func($factory .'::times', $arguments[0]) : call_user_func($factory .'::new');

        if (isset($arguments[0]) && is_array($arguments[0]))
            $factory->withAttributes($arguments[0]);

        if (isset($arguments[1]) && is_array($arguments[1]))
            $factory->withAttributes($arguments[1]);

        return $factory;
    }

    protected function getFactoryFor($relationshipMethodName)
    {
        $factoryLocation = config('poser.factories_directory', "Tests\\Factories\\");

        $singularRelationship = Str::singular($relationshipMethodName);

        if (class_exists($factoryLocation . Str::title($singularRelationship))) {
            return $factoryLocation . Str::title($singularRelationship);
        }

        if (class_exists($factoryLocation . Str::title($singularRelationship . "Factory"))) {
            return $factoryLocation . Str::title($singularRelationship . "Factory");
        }

        return null;
    }

    public function withAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

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
        } else if ($result instanceof Model) {
            $result->save();
        }

        if ($result instanceof Collection) {
            $result->each(function ($model) {
                $this->addSaveMethodRelationships($model);
            });
        } else if ($result instanceof Model) {
            $this->addSaveMethodRelationships($result);
        }

        return $result;
    }

    private function performActionToModels($models, $closure)
    {
        if ($models instanceof Collection) {
            $models->each(function ($model) use ($closure) {
                $closure($model);
            });
        } else if ($models instanceof Model) {
            $this->addBelongsToRelationships($models);
        }
    }

    protected function addSaveMethodRelationships($model)
    {
        $this->saveMethodRelationships->each(function ($relatedModels, $relationshipName) use ($model) {
            $models = $relatedModels instanceof Factory ? $relatedModels->make() : $relatedModels;

            if ($models instanceof Model)
                $models = collect([$models]);

            $models->each(function ($relatedModel) use ($model, $relationshipName) {
                $model->{$relationshipName}()->save($relatedModel);
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

    public function make($attributes = [])
    {
        $factory = factory($this->getModelName())
            ->states($this->states);

        if ($this->count > 1)
            $factory->times($this->count);

        $result = $factory->make(array_merge($this->attributes, $attributes));

        return $result;
    }

    protected function getModelName()
    {
        if (!empty(static::$modelName))
            return static::$modelName;

        return config('poser.models_directory', "App\\") . Str::beforeLast(class_basename($this), "Factory");
    }

    public function as($state)
    {
        return $this->state($state);
    }

    public function state($state)
    {
        $this->states[] = $state;

        return $this;
    }

    public function states(...$states)
    {
        collect($states)->flatten()->each(function($state) {
           $this->states[] = $state;
        });

        return $this;
    }

}
