<?php

namespace Lukeraymonddowning\Poser\Exceptions;

use Exception;

class ArgumentsNotSatisfiableException extends Exception {

    protected $message = "The relationship could not be satisfied by the given arguments.";

}
