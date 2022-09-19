<?php

namespace Es\NetsEasy\Api;

/*
 * Class defines nets payment type mapping to oxid payment ids
 *
 */

class NetsPaymentTypes
{

    public $nets_payment_types = [
        [
            'payment_id' => 'nets_easy',
            'payment_type' => 'netseasy',
            'payment_option_name' => 'nets_easy_active',
            'payment_desc' => 'Nets Easy',
            'payment_shortdesc' => 'Nets Easy'
        ]
    ];

    /**
     * Function to get Nets Payment Type
     * @param  string $payment_id The payment id
     * @return bool
     */
    public function getNetsPaymentType($payment_id)
    {
        foreach ($this->nets_payment_types as $type) {
            if ($type['payment_id'] == $payment_id) {
                return $type['payment_type'];
            }
        }
        return false;
    }

    /**
     * Function to get Nets Payment Description
     * @param  string $payment_id The payment id
     * @return bool
     */
    public function getNetsPaymentDesc($payment_id)
    {
        foreach ($this->nets_payment_types as $type) {
            if ($type['payment_id'] == $payment_id) {
                return $type['payment_desc'];
            }
        }
        return false;
    }

    /**
     * Function to get Nets Payment Short Description
     * @param  string $payment_id The payment id
     * @return bool
     */
    public function getNetsPaymentShortDesc($payment_id)
    {
        foreach ($this->nets_payment_types as $type) {
            if ($type['payment_id'] == $payment_id) {
                return $type['payment_shortdesc'];
            }
        }
        return false;
    }

}
