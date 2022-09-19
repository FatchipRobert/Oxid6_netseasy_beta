<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace Es\NetsEasy\Core;

use Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class DebugHandler
{

    /** @var Logger */
    protected $logger;

    /**
     * @param Logger $moduleLogger
     */
    public function __construct()
    {
        if (class_exists(Logger::class)) {
            $this->logger = new Logger('NetsEasy', array(
                new StreamHandler(dirname(OX_LOG_FILE) . '/Nets.log', Logger::ERROR)
            ));
        }
         
    }

    /**
     * Adds a log record.
     * This method allows for compatibility with common interfaces.
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function log($message, array $context = array())
    {
        return $this->logger->addRecord(400, $message, $context);
    }

}
