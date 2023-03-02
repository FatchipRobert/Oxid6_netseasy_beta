<?php

namespace Es\NetsEasy\Application\Helper;

class DebugLog
{
    /**
     * @var DebugLog
     */
    protected static $oInstance = null;

    /**
     * Create singleton instance of current helper class
     *
     * @return DebugLog
     */
    public static function getInstance()
    {
        if (self::$oInstance === null) {
            self::$oInstance = oxNew(self::class);
        }
        return self::$oInstance;
    }

    /**
     * Filename of the log file
     *
     * @var string
     */
    protected $sLogFilename = "Nets.log";

    /**
     * Logs to a log file in the oxid log folder
     *
     * @param  string $sMessage
     * @param  array|false $aContext
     * @return void
     */
    public function log($sMessage, $aContext = false)
    {
        if (function_exists('error_log')) {
            $sContext = "";
            if (!empty($sContext)) {
                $sContext = " ".json_encode($sContext);
            }
            error_log(date('[Y-m-d H:i:s]: ').$sMessage.$sContext.PHP_EOL, 3, getShopBasePath().DIRECTORY_SEPARATOR."log".DIRECTORY_SEPARATOR.$this->sLogFilename);
        }
    }
}
