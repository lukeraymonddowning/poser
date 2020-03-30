<?php


namespace Lukeraymonddowning\Poser;

use Illuminate\Database\Eloquent\Model;

class Relationship
{

    protected $methodName, $data, $models;

    public function __construct(string $methodName, object $data)
    {
        $this->methodName = $methodName;
        $this->data = $data;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getData(): object
    {
        return $this->data;
    }

    public function dataIsFactory()
    {
        return $this->data instanceof Factory;
    }

    public function buildModels()
    {
        $builtModels = $this->dataIsFactory() ? $this->getData()->make() : $this->getData();

        return $builtModels instanceof Model ? collect([$builtModels]) : $builtModels;
    }

    public function createModels()
    {
        return $this->dataIsFactory() ?
            $this->getData()->create() :
            $this->getData();
    }

}
