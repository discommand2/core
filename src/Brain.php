<?php

namespace Discommand2\Core;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Brain
 * 
 * This class represents the "brain" of the application.
 */
class Brain
{
    /**
     * Brain constructor.
     *
     * @param Logger|null $logger
     * @param string|null $myName
     */
    public function __construct(private ?Logger $logger = null, private ?string $myName = 'testBrain')
    {
        if (is_null($logger)) $this->logger = LogFactory::create($this->myName);
        $this->logger->info($this->myName . ' is alive.');
    }

    /**
     * This method is the main method of the application.
     * It is responsible for the main loop of the application.
     */
    public function think(): bool
    {
        $this->logger->info('I am thinking., but not for long.');
        return true;
    }

    /**
     * This method is responsible for the shutdown of the application.
     */
    public function __destruct()
    {
        $this->logger->info($this->myName . ' died.');
    }
}
