<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Audience;

use Psr\Log\LoggerInterface;

final class SymplifyAudience
{

    /** @var array<int,mixed> $rules */
    public array $rules;

    /** @var LoggerInterface a logger to collect messages from the SDK */
    public LoggerInterface $logger;
    private string $errorMessage;

    /**
     * @param array<string,mixed>|string $rules
     */
    public function __construct($rules, LoggerInterface $logger) {

        $this->logger = $logger;
        $this->errorMessage = '';
        $result = array();

        try{
            $result = is_string($rules) ? RulesEngine::parseString($rules) : RulesEngine::parse($rules);
        } catch( \Throwable $exception){
            $this->errorMessage = $exception->getMessage();
            $this->logger->warning($exception->getMessage());

            return;
        }

        $this->rules = $result;
    }

    /**
     * @param array<string,mixed> $environment
     * @return bool|string
     */
    public function eval(array $environment = []){
        // Since the constructor can't return an error message we must have this
        // errorMessage checker and return the error message.
        if(0 !== strlen($this->errorMessage)){
            return $this->errorMessage;
        }

        try {
            $result = RulesEngine::evaluate($this->rules, $environment);
        } catch(\Throwable $exception){
            $this->logger->warning($exception->getMessage());

            return $exception->getMessage();
        }

        if(!is_bool($result)){
            $this->logger->warning(sprintf('audience result was not boolean (%s)',$result));

            return sprintf('audience result was not boolean (%s)',$result);
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $environment
     * @return array<string,mixed>|string
     */
    public function trace(array $environment)
    {
        // Since the constructor can't return an error message we must have this
        // errorMessage checker and return the error message.
        if(0 !== strlen($this->errorMessage)){
            return $this->errorMessage;
        }

        try {
            $result = $this->traceEval($this->rules, $environment);
        } catch(\Throwable $exception){
            $this->logger->warning($exception->getMessage());

           return $exception->getMessage();
        }

        return $result;
    }

    /**
     * @param mixed $ast
     * @param array<mixed> $environment
     * @return mixed
     * @throws \Exception
     */
    private function traceEval($ast, array $environment, bool $isTrace = true)
    {
        $astCopy = $ast;
        $returnTrace = [];

        if(is_array($ast) && is_string($ast[0])){
            $car = array_shift($ast);

            $cdr = $ast;
            $value = RulesEngine::evalApply($car, $cdr, $environment, $isTrace);
            $returnTrace[] = ['call' => $car, 'result' => $value];
            $traceEval = array_map(
                function($arg) use ($environment, $isTrace) {
 return $this->traceEval($arg, $environment, $isTrace);
} ,
                $cdr
            );

            return array_merge($returnTrace, $traceEval);
        }

        return RulesEngine::evaluate($astCopy, $environment, $isTrace);
    }

}