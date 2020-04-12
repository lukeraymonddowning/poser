<?php


namespace Lukeraymonddowning\Poser;


class Assertion
{

    protected $assertionName, $arguments;

    public function __construct(string $assertionName, array $arguments)
    {
        $this->assertionName = $assertionName;
        $this->arguments = $arguments;
    }

    public function getAssertionName()
    {
        return $this->assertionName;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

}
