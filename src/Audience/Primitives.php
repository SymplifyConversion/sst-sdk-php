<?php

namespace SymplifyConversion\SSTSDK\Audience;

use Exception;

class Primitives
{
    const PRIMITIVES = [
        'not',
        'all',
        'any',
        'equals',
        'contains',
        'matches',
        '==',
        '!=',
        '<',
        '<=',
        '>',
        '>=',
        'number-attribute',
        'string-attribute',
        'bool-attribute',
    ];

    /**
     * @param string $primitive
     * @param mixed[] $args
     * @param mixed[] $environment
     * @param bool $isTrace
     * @return string|boolean|numeric|string[]
     * @throws Exception
     */
    public static function PrimitiveFunction(string $primitive, array $args, array $environment, bool $isTrace = false){

        foreach($args as $arg){
            if(isset($arg['message']) && $isTrace){
                return $arg;
            }
        }

        if(!in_array($primitive, self::PRIMITIVES)){
            return self::isError(sprintf('Primitive %s is not a valid primitive. Available primitives are: %s', $primitive, implode(',',self::PRIMITIVES)),$isTrace);
        }

        switch($primitive){
            case 'not':
                if(is_bool($args[0])){
                    return !$args[0];
                }
                return self::isError($args[0] . ' is not a boolean', $isTrace);
            case 'any':
                foreach($args as $arg){
                    if(!is_bool($arg)){
                       return self::isError($arg . ' is not a boolean', $isTrace);
                    }

                    if($arg === true){
                        return true;
                    }
                }
                return false;
            case 'all':
                foreach($args as $arg){
                    if(!is_bool($arg)){
                        return self::isError($arg . ' is not a boolean',$isTrace);
                    }
                    if(!$arg){
                        return false;
                    }
                }
                return true;
            case 'equals':
                return self::stringFun($args[0], $args[1], function($a, $b){ return $a === $b; },$isTrace);
            case 'contains':
                return self::stringFun($args[0], $args[1], function($a, $b){ return (strstr($a, $b)) !== false; },$isTrace);
            case 'matches':
                return self::stringFun($args[0], $args[1], function($a, $b){ return preg_match($b,$a) === 1; },$isTrace);
            case '==':
                return self::numberFun($args[0], $args[1], function($a, $b){ return $a === $b; },$isTrace);
            case '!=':
                return self::numberFun($args[0], $args[1], function($a, $b){ return $a != $b; },$isTrace);
            case '<':
                return self::numberFun($args[0], $args[1], function($a, $b){ return $a < $b; },$isTrace);
            case '<=':
                return self::numberFun($args[0], $args[1], function($a, $b){ return $a <= $b; },$isTrace);
            case '>':
                return self::numberFun($args[0], $args[1], function($a, $b){ return $a > $b; },$isTrace);
            case '>=':
                return self::numberFun($args[0], $args[1], function($a, $b){ return $a >= $b; },$isTrace);
            case 'number-attribute':
                return self::getInEnvNumber($args[0], $environment,$isTrace);
            case 'string-attribute':
                return self::getInEnvString($args[0], $environment,$isTrace);
            case 'bool-attribute':
                return self::getInEnvBool($args[0], $environment,$isTrace);
            default:
                return self::isError(sprintf('Primitive %s is not an implemented primitive.', $primitive),$isTrace);
        }
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param callable $function
     * @param bool $isTrace
     * @return boolean|string[]
     * @throws Exception
     */
    private static function stringFun($a, $b, callable $function, bool $isTrace = false) {
        if(!is_string($a) || !is_string($b))
            return self::isError('expected string arguments', $isTrace);

        return $function($a,$b);
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param callable $function
     * @param bool $isTrace
     * @return boolean|string[]
     * @throws Exception
     */
    private static function numberFun($a, $b, callable $function, bool $isTrace = false) {
        if(!is_numeric($a) || !is_numeric($b))
            return self::isError('expected number arguments',$isTrace);

        return $function((float)$a,(float)$b);
    }

    /**
     * @param mixed $name
     * @param mixed[] $environment
     * @return numeric|string[]
     * @throws Exception
     */
    private static function getInEnvNumber($name, array $environment, bool $isTrace = false)
    {
        if(!is_string($name)) {
            return self::isError('can only look up string names',$isTrace);
        }

        if(!isset($environment[$name]) || !is_numeric($environment[$name])){
            return self::isError(sprintf("'%s' is not a number", $name),$isTrace);
        }

        return $environment[$name];
    }

    /**
     * @param mixed $name
     * @param mixed[] $environment
     * @return string|string[]
     * @throws Exception
     */
    private static function getInEnvString($name, array $environment, bool $isTrace = false)
    {
        if(!is_string($name)) {
            return self::isError('can only look up string names', $isTrace);
        }

        if(!isset($environment[$name]) || !is_string($environment[$name])){
            return self::isError(sprintf("'%s' is not a string", $name), $isTrace);
        }

        return $environment[$name];
    }

    /**
     * @param mixed $name
     * @param mixed[] $environment
     * @return bool|string[]
     * @throws Exception
     */
    private static function getInEnvBool($name, array $environment, bool $isTrace = false)
    {
        if(!is_string($name)) {
            return self::isError('can only look up string names', $isTrace);
        }

        if(!isset($environment[$name]) || !is_bool($environment[$name])){
            return self::isError(sprintf("'%s' is not a boolean", $name), $isTrace);
        }

        return $environment[$name];
    }

    /**
     * If we get an issue with the calculation, the event should propagate to the top and be printed.
     * This is not the case if the call is from trace() or traceEval()
     * @param string $message
     * @param bool $isTrace
     * @return string[]
     * @throws Exception
     */
    private static function isError(string $message, bool $isTrace = false): array {
        if($isTrace){
            return ['message' => $message];
        } else {
            throw new Exception($message);
        }
    }
}