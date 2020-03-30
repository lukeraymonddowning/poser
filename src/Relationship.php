<?php


namespace Lukeraymonddowning\Poser;


class Relationship
{

    protected $functionName, $arguments;

    public function __construct(string $functionName, array $arguments)
    {
        $this->functionName = $functionName;
        $this->arguments = $arguments;
    }

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

}
