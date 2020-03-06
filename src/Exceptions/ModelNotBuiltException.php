<?php


namespace Lukeraymonddowning\Poser\Exceptions;


use Exception;
use Throwable;

class ModelNotBuiltException extends Exception {

    public function __construct($factory, $userCalled, $modelName = null)
    {
        $message = "You tried to call '" . $userCalled . "', but it doesn't exist on " . class_basename($factory)  . ". Were you trying to call " . $modelName . "::" . $userCalled . "? If so, don't forget to call either 'create()' or 'make()' on " . class_basename($factory) . ".";
        parent::__construct($message, 0, null);
    }

}
