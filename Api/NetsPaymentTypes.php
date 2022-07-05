<?php

namespace Es\NetsEasy\Api;

/*
 * Class defines nets payment type mapping to oxid payment ids
 *
 */
if (!class_exists("NetsPaymentTypes")) {

    class NetsPaymentTypes {

        static $nets_payment_types = Array(
            Array(
                'payment_id' => 'nets_easy',
                'payment_type' => 'netseasy',
                'payment_option_name' => 'nets_easy_active',
                'payment_desc' => 'Nets Easy',
                'payment_shortdesc' => 'Nets Easy'
            )
        );

        /**
         * Function to get Nets Payment Type
         * @return bool
         */
        static function getNetsPaymentType($payment_id) {
            foreach (self::$nets_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_type'];
                }
            }
            return false;
        }

        /**
         * Function to get Nets Payment Description
         * @return bool
         */
        static function getNetsPaymentDesc($payment_id) {
            foreach (self::$nets_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_desc'];
                }
            }
            return false;
        }

        /**
         * Function to get Nets Payment Short Description
         * @return bool
         */
        static function getNetsPaymentShortDesc($payment_id) {
            foreach (self::$nets_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_shortdesc'];
                }
            }
            return false;
        }

    }

}
