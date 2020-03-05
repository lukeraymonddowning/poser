<?php

namespace Lukeraymonddowning\Poser\Exceptions;

use Exception;
use Throwable;

class ArgumentsNotSatisfiableException extends Exception {

    public function __construct($callingClassName, $functionName, $relationshipMethodName)
    {
        parent::__construct("The relationship '" . $relationshipMethodName . "' could not be converted into a Poser Factory. You called '" . $callingClassName . "->" . $functionName . "()'. Are you sure there is a corresponding factory for " . $relationshipMethodName . "?", 0, null);
    }

}
