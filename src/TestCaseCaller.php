<?php


namespace Lukeraymonddowning\Poser;


use ReflectionFunction;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;

class TestCaseCaller
{

    protected $assertions, $phpUnit;

    public function __construct()
    {
        $this->phpUnit = $this->findPhpUnitInstance();
        $this->assertions = collect([]);
    }

    public function addAssertion(string $name, array $arguments)
    {
        $this->assertions->push(new Assertion($name, $arguments));
    }

    public function callPhpUnitMethod(Assertion $assertion, $compare, $check)
    {
        if (count($assertion->getArguments()) > 1) {
            $this->phpUnit->{$assertion->getAssertionName()}(
                $compare,
                $check
            );
        } else {
            $this->phpUnit->{$assertion->getAssertionName()}(
                $check
            );
        }
    }

    public function processAssertions($createdInstance)
    {
        $this->assertions->each(
            function (Assertion $assertion) use ($createdInstance) {
                if (!($this->phpUnit) instanceof TestCase) {
                    // TODO: Throw exception
                }

                $compare = $assertion->getArguments()[0];
                $check = $assertion->getArguments()[1] ?? $assertion->getArguments()[0];

                if (is_callable($check)) {
                    $mirror = new ReflectionFunction($check);

                    if ($mirror->getNumberOfParameters() > 0) {
                        $type = (optional($mirror->getParameters()[0]->getType())->getName() ?? Model::class);

                        if (is_subclass_of($type, Model::class) && !$createdInstance instanceof Model) {
                            $createdInstance->each(
                                function (Model $model) use ($assertion, $compare, $check) {
                                    $this->callPhpUnitMethod($assertion, $compare, $check($model));
                                }
                            );
                        } else {
                            $this->callPhpUnitMethod($assertion, $compare, $check($createdInstance));
                        }
                    }
                } elseif (is_string($check)) {
                    collectUp($createdInstance)->each(
                        function (Model $model) use ($assertion, $compare, $check) {
                            $this->callPhpUnitMethod($assertion, $compare, $model->$check);
                        }
                    );
                }
            }
        );
    }

    protected function findPhpUnitInstance()
    {
        $phpUnit = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT))
            ->first(
                function ($trace) {
                    return ($trace['object'] ?? null) instanceof TestCase;
                }
            );

        return $phpUnit['object'] ?? null;
    }

}
