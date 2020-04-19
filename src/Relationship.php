<?php


namespace Lukeraymonddowning\Poser;


use ArrayAccess;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Relationship
{

    protected $functionName, $data;

    public function __construct(string $functionName, object $data)
    {
        $this->functionName = $functionName;
        $this->data = $data;
    }

    public function getFunctionName(Model $model = null): string
    {
        if (!$model) {
            return $this->functionName;
        }

        if (method_exists($model, Str::snake($this->functionName))) {
            return Str::snake($this->functionName);
        }

        return $this->functionName;
    }

    public function getData(): object
    {
        return $this->data;
    }

}
