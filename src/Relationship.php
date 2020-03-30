<?php


namespace Lukeraymonddowning\Poser;

use Illuminate\Database\Eloquent\Model;

class Relationship
{

    protected $functionName, $data, $models;

    public function __construct(string $functionName, object $data)
    {
        $this->functionName = $functionName;
        $this->data = $data;
    }

    public function getFunctionName(): string
    {
        return $this->functionName;
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
