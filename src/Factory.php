<?php


namespace Lukeraymonddowning\Poser;

use Closure;
use App\User;
use Illuminate\Support\Str;
use Tests\Factories\UserFactory;
use Illuminate\Support\Collection;
use Tests\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Model;

abstract class Factory {

    protected static $modelName = null;

    protected
        $count = 1,
        $saveMethodRelationships,
        $belongsToRelationships,
        $attributes = [];

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
        $relationshipMethodName = Str::camel(Str::after($functionName, 'with'));

        $modelData = $arguments[0];

        $this->saveMethodRelationships[$relationshipMethodName] = $modelData;
    }

    protected function handleBelongsToRelationships($functionName, $arguments)
    {
        $relationshipMethodName = Str::camel(Str::after($functionName, 'for'));

        $owningModel = $arguments[0];

        $this->belongsToRelationships[$relationshipMethodName] = $owningModel;
    }

    public function withAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function create($attributes = [])
    {
        $result = $this->buildBy(function ($factory) use ($attributes) {
            return $factory->make(array_merge($this->attributes, $attributes));
        });

        if ($result instanceof Collection) {
            $result->each(function ($model) {
                $this->addBelongsToRelationships($model);
            });
        } else if ($result instanceof Model) {
            $this->addBelongsToRelationships($result);
        }

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

    protected function addSaveMethodRelationships($model)
    {
        $this->saveMethodRelationships->each(function ($relatedModels, $relationshipName) use ($model) {
            $models = $relatedModels instanceof Factory ? $relatedModels->make() : $relatedModels;

            if ($models instanceof Collection) {
                $model->{$relationshipName}()->saveMany($models);
            } else if ($models instanceof Model) {
                $model->{$relationshipName}()->save($models);
            }

            if ($relatedModels instanceof Factory) {
                if ($models instanceof Collection) {
                    $models->each(function ($model) use ($relatedModels) {
                        $relatedModels->addSaveMethodRelationships($model);
                    });
                } else if ($models instanceof Model) {
                    $relatedModels->addSaveMethodRelationships($models);
                }
            }
        });

        return $this;
    }

    protected function addBelongsToRelationships($model)
    {
        $this->belongsToRelationships->each(function ($owningModel, $relationshipName) use ($model) {
            if ($owningModel instanceof Factory) {
                $owningModel = $owningModel->create();
            }

            $model->{$relationshipName}()->associate($owningModel);
        });

        return $this;
    }

    public function make($attributes = [])
    {
        return $this->buildBy(function ($factory) use ($attributes) {
            return $factory->make(array_merge($this->attributes, $attributes));
        });
    }

    private function buildBy(Closure $closure)
    {
        $factory = factory($this->getModelName());

        if ($this->count > 1)
            $factory->times($this->count);

        $result = $closure($factory);

        return $result;
    }

    protected function getModelName()
    {
        if (!empty(static::$modelName))
            return static::$modelName;

        return config('poser.models_directory', "App\\") . Str::beforeLast(class_basename($this), "Factory");
    }

}
