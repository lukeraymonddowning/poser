<?php

namespace Lukeraymonddowning\Poser\Exceptions;

use Exception;
use Throwable;

class ArgumentsNotSatisfiableException extends Exception
{

    public function __construct($callingClassName, $functionName, $relationshipMethodName, $factoryNamesChecked = [])
    {
        $message = "The relationship '" . $relationshipMethodName . "' could not be converted into a Poser Factory. You called '" . $callingClassName . "->" . $functionName . "()'. Are you sure there is a corresponding factory for " . $relationshipMethodName . "?";

        if (!empty($factoryNamesChecked)) {
            $message .= " We checked for factories with the names '" . collect($factoryNamesChecked)->join("', '",
                    "' and '") . "'.";
        }
        parent::__construct($message, 0, null);
    }

}
