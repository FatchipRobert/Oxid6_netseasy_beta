<?php

namespace Es\NetsEasy\Api;

/**
 * Class defines how module save the logs.
 */
if (!class_exists("NetsLog")) {

    class NetsLog
    {

        /**
         * Function to save log details in nets file 
         * @return null
         */
        public static function log($log)
        {
            if (!$log) {
                return;
            }
            $date = date("r");
            $logfile = getShopBasePath() . "log/nets.log";
            $x = 0;
            foreach (func_get_args() as $val) {
                $x ++;
                if ($x == 1) {
                    continue;
                }
                if (is_string($val) || is_numeric($val)) {
                    file_put_contents($logfile, "[$date] $val\n", FILE_APPEND);
                } else {
                    file_put_contents($logfile, "[$date] " . print_r($val, true) . "\n", FILE_APPEND);
                }
            }
            return true;
        }

        /**
         * Function validate utf8 string 
         * @return bool
         */
        public static function seems_utf8($Str)
        {
            for ($i = 0; $i < strlen($Str); $i ++) {
                if (ord($Str[$i]) < 0x80)
                    continue;# 0bbbbbbb
                else if ((ord($Str[$i]) & 0xE0) == 0xC0)
                    $n = 1;# 110bbbbb
                else if ((ord($Str[$i]) & 0xF0) == 0xE0)
                    $n = 2;# 1110bbbb
                else if ((ord($Str[$i]) & 0xF8) == 0xF0)
                    $n = 3;# 11110bbb
                else if ((ord($Str[$i]) & 0xFC) == 0xF8)
                    $n = 4;# 111110bb
                else if ((ord($Str[$i]) & 0xFE) == 0xFC)
                    $n = 5;# 1111110b
                else
                    return false; // Does not match any model
                for ($j = 0; $j < $n; $j ++) {
                    // n bytes matching 10bbbbbb follow ?
                    if (( ++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80)) {
                        return false;
                    }
                }
            }
            return true;
        }

        /**
         * Function to check utf8 string 
         * @return $data array
         */
        public static function utf8_ensure($data)
        {
            if (is_string($data)) {
                return self::seems_utf8($data) ? $data : \utf8_encode($data);
            } else if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = self::utf8_ensure($value);
                }
                unset($value);
                unset($key);
            } else if (is_object($data)) {
                foreach ($data as $key => $value) {
                    $data->$key = self::utf8_ensure($value);
                }
                unset($value);
                unset($key);
            }
            return $data;
        }

        /**
         * Function to create transaction id in db
         * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
         */
        public static function createTransactionEntry($req_data, $ret_data, $hash, $payment_id, $oxorder_id, $amount)
        {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            $sSQL = "INSERT INTO oxnets (req_data, ret_data, transaction_id, oxordernr, oxorder_id, amount, created)" . " VALUES(?, ?, ?, ?, ?, ?,now())";
            $oDB->execute($sSQL, [
                $req_data,
                $ret_data,
                $payment_id,
                $oxorder_id,
                $hash,
                $amount
            ]);
        }

        /**
         * Function to set transaction id in db
         * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
         */
        public static function setTransactionId($hash, $transaction_id, $log_error = false)
        {
            if (!empty($hash) & !empty($transaction_id)) {
                $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
                $sqlQuery = "UPDATE oxnets SET transaction_id = ? WHERE ISNULL(transaction_id) AND hash = ?";
                self::log($log_error, 'nets_api, setTransactionId queries', $sqlQuery);
                $oDB->execute($sqlQuery, [
                    $transaction_id,
                    $hash
                ]);
            } else {
                self::log($log_error, 'nets_api, hash or transaction_id empty');
            }
        }

    }

}