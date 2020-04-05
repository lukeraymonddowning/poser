<?php


namespace Lukeraymonddowning\Poser;


use ArrayAccess;

class Relationship
{

    protected $functionName, $data;

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

}
