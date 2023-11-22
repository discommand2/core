<?php

namespace Discommand2\Core;

use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;

class Brain
{
    private static ?Brain $instance = null;
    private ?Logger $logger = null;

    public function __construct(public ?string $myName = null)
    {
        $this->logger = new Logger('brain');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
        $this->logger->info('Brain Online');
    }
}
