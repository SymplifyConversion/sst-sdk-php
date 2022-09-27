<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Audience;

use Psr\Log\LoggerInterface;

/**
 * SymplifyAudience contains rules to be evaluated for activating projects.
 */
final class SymplifyAudience
{

    /** @var array<int,mixed> $rules */
    public array $rules;

    /** @var LoggerInterface a logger to collect messages from the SDK */
    public LoggerInterface $logger;
    private string $initializationError;

    /**
     * @param array<string,mixed>|string $rules
     */
    public function __construct($rules, LoggerInterface $logger) {

        $this->logger = $logger;
        $this->initializationError = '';
        $result = array();

        try{
            $result = is_string($rules) ? RulesEngine::parseString($rules) : RulesEngine::parse($rules);
        } catch( \Throwable $exception){
            $this->initializationError = $exception->getMessage();
            $this->logger->warning($exception->getMessage());

            return;
        }

        $this->rules = $result;
    }

    /**
     * eval interprets the rules in the given environment, and returns true if
     * the audience matches.
     *
     * @param array<string,mixed> $environment
     * @return bool|string
     */
    public function eval(array $environment = []){
        // Since the constructor can't return an error message we must have this
        // errorMessage checker and return the error message.
        if(0 !== strlen($this->initializationError)){
            return $this->initializationError;
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
     * trace interprets the rules in the given environment, and annotates each
     * sub-expression with their partial value.
     *
     * @param array<string,mixed> $environment
     * @return array<string,mixed>|string
     */
    public function trace(array $environment)
    {
        // Since the constructor can't return an error message we must have this
        // errorMessage checker and return the error message.
        if(0 !== strlen($this->initializationError)){
            return $this->initializationError;
        }

        try {
            $result = RulesEngine::traceEvaluate($this->rules, $environment);
        } catch(\Throwable $exception){
            $this->logger->warning($exception->getMessage());

           return $exception->getMessage();
        }

        return $result;
    }

}
