<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Audience;

final class RulesEngine
{

    /**
     * Checks that the given rules AST is valid.
     *
     * Throws an exception if the rules are invalid.
     *
     * @param array<mixed> $ast
     * @return array<mixed>|null
     * @throws \Exception
     */
    public static function parse(array $ast): ?array {

        $checkSyntax = self::checkSyntax($ast);

        if($checkSyntax)

            return $ast;

        // this can't happen, but the compiler doesn't know that
        return null;
    }

    /**
     * Parses the given JSON string into an AST.
     *
     * Throws an exception if the JSON or rules syntax is invalid.
     *
     * @param string $ruleString;
     * @return array<mixed>|null
     * @throws \Exception
     */
    public static function parseString(string $ruleString): ?array {
        try {
            $ast = json_decode($ruleString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e){
            throw new \Exception('rules syntax error', 0, $e);
        }

        if(!is_array($ast)){
            throw new \Exception('AST root must be a list');
        }

        return self::parse($ast);
    }

    /**
     * @param mixed $ast
     * @param array<mixed> $environment
     * @throws \Exception
     * @return mixed
     */
    public static function evaluate($ast, array $environment, bool $isTrace = false)
    {
        switch(gettype($ast)){
            case 'integer':
            case 'string':
            case 'boolean':
                return $ast;

            case 'array':
                if(is_string($ast[0])){
                    $car = array_shift($ast);
                    $cdr = $ast;

                    return self::evalApply($car, $cdr, $environment, $isTrace);
                }
        }

        throw new \Exception(sprintf('cannot evaluate %s', json_encode($ast)));
    }

    /**
     * Trace the evaluation of the given rules expression.
     *
     * The syntax tree is evaluated node for node, and function calls get
     * annotated in-place with their result.
     *
     * This can be used for debugging or testing expressions in SST development.
     *
     * @param mixed $ast
     * @param array<mixed> $environment
     * @return mixed
     * @throws \Exception
     */
    public static function traceEvaluate($ast, array $environment, bool $isTrace = true)
    {
        $astCopy = $ast;
        $returnTrace = [];

        if(is_array($ast) && is_string($ast[0])){
            $car = array_shift($ast);

            $cdr = $ast;
            $value = self::evalApply($car, $cdr, $environment, $isTrace);
            $returnTrace[] = ['call' => $car, 'result' => $value];
            $traceEval = array_map(
                static function($arg) use ($environment, $isTrace) {
                    return self::traceEvaluate($arg, $environment, $isTrace);
                } ,
                $cdr
            );

            return array_merge($returnTrace, $traceEval);
        }

        return self::evaluate($astCopy, $environment, $isTrace);
    }

    /**
     * @param array<mixed> $cdr
     * @param array<mixed> $environment
     * @return bool|float|int|string|array<string>
     * @throws \Exception
     */
    static function evalApply(string $car, array $cdr, array $environment, bool $isTrace = false)
    {
        if(!in_array($car,Primitives::PRIMITIVES, true)){
            throw new \Exception(sprintf('%s is not a primitive', $car));
        }

        $evaledArgs = [];

        foreach($cdr as $arg){
            $evaledArgs[] = self::evaluate($arg, $environment, $isTrace);
        }

        return Primitives::PrimitiveFunction($car, $evaledArgs, $environment, $isTrace);
    }

    /**
     * Check AST syntax, throws exception if invalid.
     *
     * @param mixed $ast
     * @throws \Exception
     */
    private static function checkSyntax($ast): bool
    {
        self::checkSyntaxInner($ast);

        // Since there can be different reasons for the AST being invalid we use
        // exceptions instead of proper true | false branches. This lets us capture
        // messages in the caller.
        return true;
    }

    /**
     * @param mixed $ast
     * @throws \Exception
     */
    private static function checkSyntaxInner($ast): void
    {
        switch(gettype($ast)){
            case 'integer':
            case 'string':
            case 'boolean':
                return;
        }

        if(is_array($ast)){
            $car = array_shift($ast);
            $cdr = $ast;

            if(!is_string($car)){
                throw new \Exception(sprintf('can only apply strings, %s is not a string', $car));
            }

            if(!in_array($car, Primitives::PRIMITIVES, true)){
                throw new \Exception(sprintf("'%s' is not a primitive", $car));
            }

            foreach($cdr as $elem){
                self::checkSyntaxInner($elem);
            }

            return;
        }

        throw new \Exception(sprintf("rules syntax error at %s", json_encode($ast)));
    }

}
