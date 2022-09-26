<?php

namespace SymplifyConversion\SSTSDK\Audience;


use Exception;
use Psr\Log\LoggerInterface;

class SymplifyAudience
{
    /** @var array<int,mixed> $rules */
    public array $rules;
    private string $errorMessage;
    /** @var LoggerInterface a logger to collect messages from the SDK */
    public LoggerInterface $logger;

    /**
     * @param array<string,mixed>|string $rules
     * @param LoggerInterface $logger
     */
    public function __construct($rules, LoggerInterface $logger) {

        $this->logger = $logger;

        $result = array();

        try{
            $result = (is_string($rules)) ? RulesEngine::parseString($rules) : RulesEngine::parse($rules);
        } catch( Exception $exception){
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
        if(!empty($this->errorMessage)){
            return $this->errorMessage;
        }


        try {
            $result = RulesEngine::evaluate($this->rules, $environment);
        } catch(Exception $exception){
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
        if(!empty($this->errorMessage)){
            return $this->errorMessage;
        }

        try {
            $result = $this->traceEval($this->rules, $environment);
        } catch(Exception $exception){
            $this->logger->warning($exception->getMessage());
           return $exception->getMessage();
        }

        return $result;
    }

    /**
     * @param mixed $ast
     * @param mixed[] $environment
     * @return mixed
     * @throws Exception
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
            $traceEval = array_map(function($arg) use ($environment, $isTrace) { return $this->traceEval($arg, $environment, $isTrace);} , $cdr);

            return array_merge($returnTrace, $traceEval);
        }

        return RulesEngine::evaluate($astCopy, $environment, $isTrace);
    }
}