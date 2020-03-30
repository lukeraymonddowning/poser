<?php


namespace Lukeraymonddowning\Poser;


use ArrayAccess;

class Relationship implements ArrayAccess
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

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return in_array($offset, [0, 1]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 0:
                return $this->getFunctionName();
            case 1:
                return $this->getData();
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        switch ($offset) {
            case 0:
                $this->functionName = $value;
                break;
            case 1:
                $this->data = $value;
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        switch ($offset) {
            case 0:
                $this->functionName = null;
                break;
            case 1:
                $this->data = null;
                break;
        }
    }
}
