<?php

namespace Es\NetsEasy\Application\Helper;

class Payment
{
    const METHOD_EASY = "nets_easy";

    /**
     * @var Payment
     */
    protected static $oInstance = null;

    /**
     * Create singleton instance of current helper class
     *
     * @return Payment
     */
    public static function getInstance()
    {
        if (self::$oInstance === null) {
            self::$oInstance = oxNew(self::class);
        }
        return self::$oInstance;
    }

    /**
     * Array with Nets payment method information
     *
     * @var string[][]
     */
    protected $aNetsPaymentTypes = [
        self::METHOD_EASY => [
            'option_name' => 'nets_easy_active',
            'desc' => 'Nets Easy',
            'shortdesc' => 'Nets Easy'
        ],
    ];

    /**
     * Returns nets payment methods
     *
     * @return string[][]
     */
    public function getNetsPaymentTypes()
    {
        return $this->aNetsPaymentTypes;
    }

    /**
     * Check if given payment type is a Nets payment type
     *
     * @param  string $sPaymentType
     * @return bool
     */
    public function isNetsPayment($sPaymentType)
    {
        if (isset($this->aNetsPaymentTypes[$sPaymentType])) {
            return true;
        }
        return false;
    }

    /**
     * Function to get Nets Payment Description
     *
     * @param  string $sPaymentId The payment id
     * @return bool
     */
    public function getNetsPaymentDesc($sPaymentId)
    {
        if (isset($this->aNetsPaymentTypes[$sPaymentId]['desc'])) {
            return $this->aNetsPaymentTypes[$sPaymentId]['desc'];
        }
        return false;
    }

    /**
     * Function to get Nets Payment Short Description
     *
     * @param  string $sPaymentId The payment id
     * @return bool
     */
    public function getNetsPaymentShortDesc($sPaymentId)
    {
        if (isset($this->aNetsPaymentTypes[$sPaymentId]['shortdesc'])) {
            return $this->aNetsPaymentTypes[$sPaymentId]['shortdesc'];
        }
        return false;
    }
}
