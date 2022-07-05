<?php

/**
 * This file is part of OXID NETS module.
 * 
 */

namespace Es\NetsEasy\Core;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Api\NetsPaymentTypes;

/**
 * Class defines what module does on Shop events.
 */
class Events
{

    static $NetsLog = true;
    static $nets_table_names = Array(
        'oxnets'
    );
    static $nets_oxnets_coulmn_names = "
		'oxnets_id',
		'req_data',
		'ret_data',
		'payment_method',
		'transaction_id',
		'charge_id',
                'product_ref',
                'charge_qty',   
                'charge_left_qty',
		'oxordernr',
		'oxorder_id',
		'partial_amount',
		'amount',
		'updated',
		'payment_status',
		'hash',
		'created',
		'timestamp'
	";
    static $nets_payment_types_active = array();

    /**
     * Function to execute action on activate event
     * @return null
     */
    static function onActivate()
    {
        NetsLog::log(self::$NetsLog, "nets_eventsonActivate");
        $payment_types = NetsPaymentTypes::$nets_payment_types;
        foreach ($payment_types as $payment_type) {
            self::checkPayment($payment_type['payment_id']);
            self::activatePayment($payment_type['payment_id'], 1);
        }
        self::checkTableStructure();
    }

    /**
     * Function to execute action on deactivate event
     * @return null
     */
    static function onDeactivate()
    {
        $payment_types = NetsPaymentTypes::$nets_payment_types;
        foreach ($payment_types as $payment_type) {
            self::activatePayment($payment_type['payment_id'], 0);
        }
    }

    /**
     * Function to check if nets payment is completed.
     * @return null
     */
    private static function checkPayment($payment_id)
    {
        try {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            $payment_id_exists = $oDB->getOne("SELECT oxid FROM oxpayments WHERE oxid = ?", [
                $payment_id
            ]);
            if (!$payment_id_exists) {
                return self::createPayment($payment_id);
            }
        } catch (Exception $e) {
            NetsLog::log(self::$NetsLog, "nets_events, Exception:", $e->getMessage());
            NetsLog::log(self::$NetsLog, "nets_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    /**
     * Function to activate nets payment.
     * @return null
     */
    private static function activatePayment($payment_id, $active = 1)
    {
        try {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            $oDB->execute("UPDATE oxpayments SET oxactive = ? WHERE oxid = ?", [
                $active,
                $payment_id
            ]);
        } catch (Exception $e) {
            NetsLog::log(self::$NetsLog, "nets_events, Exception:", $e->getMessage());
            NetsLog::log(self::$NetsLog, "nets_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    /**
     * Function to create nets payment in shop.
     * @return bool
     */
    private static function createPayment($payment_id)
    {
        try {
            $desc = NetsPaymentTypes::getNetsPaymentDesc($payment_id);
            if (isset($desc) && $desc) {
                $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
                $sSql = "
					INSERT INTO oxpayments (
						`OXID`, `OXACTIVE`, `OXDESC`, `OXADDSUM`, `OXADDSUMTYPE`, `OXFROMBONI`, `OXFROMAMOUNT`, `OXTOAMOUNT`,
						`OXVALDESC`, `OXCHECKED`, `OXDESC_1`, `OXVALDESC_1`, `OXDESC_2`, `OXVALDESC_2`,
						`OXDESC_3`, `OXVALDESC_3`, `OXLONGDESC`, `OXLONGDESC_1`, `OXLONGDESC_2`, `OXLONGDESC_3`, `OXSORT`
					) VALUES (
						?, 1, ?, 0, 'abs', 0, 0, 1000000, '', 0, ?, '', '', '', '', '', '', '', '', '', 0
					)
				";
                $oDB->execute($sSql, [
                    $payment_id,
                    $desc,
                    $desc
                ]);
                return true;
            } else {
                NetsLog::log(self::$NetsLog, "nets_events, createPayment, desc missing");
                return false;
            }
        } catch (Exception $e) {
            NetsLog::log(self::$NetsLog, "nets_events, Exception:", $e->getMessage());
            NetsLog::log(self::$NetsLog, "nets_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    /**
     * Function to check nets table structure.
     * @return null
     */
    public static function checkTableStructure()
    {
        try {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            foreach (self::$nets_table_names as $table_name) {
                $table_exists = $oDB->getOne("SHOW TABLES LIKE '" . $table_name . "'");
                if (!isset($table_exists) || !$table_exists) {
                    self::createTableStructure($table_name);
                } else {
                    switch ($table_name) {
                        case 'oxnets':
                            // check columns of table oxnets
                            $sSql_columns = 'SHOW COLUMNS FROM oxnets WHERE Field IN (' . self::$nets_oxnets_coulmn_names . ');';
                            NetsLog::log(self::$NetsLog, "nets_events, checkTableStructure, columns do not match for COUNT " . count($oDB->getAll($sSql_columns)));
                            $columns_match = (count($oDB->getAll($sSql_columns)) == 18) ? true : false;
                            NetsLog::log(self::$NetsLog, "nets_events, checkTableStructure, columns do not match for COUNT match " . $columns_match);
                            break;

                        default:
                            NetsLog::log(self::$NetsLog, "nets_events, checkTableStructure, structure unkown for table '" . $table_name . "'");
                    }

                    if (isset($columns_match) && $columns_match == false) {
                        NetsLog::log(self::$NetsLog, "nets_events, checkTableStructure, columns do not match for " . $table_name);
                        $backup_table_name = $table_name . '_backup_' . uniqid();
                        NetsLog::log(self::$NetsLog, "nets_events, checkTableStructure, rename '" . $table_name . "' to '" . $backup_table_name . "'");
                        $sSql_rename = "RENAME TABLE " . $table_name . " TO " . $backup_table_name . ";";
                        $oDB->execute($sSql_rename);
                        NetsLog::log(self::$NetsLog, "nets_events, checkTableStructure, create '" . $table_name . "'");
                        self::createTableStructure($table_name);
                    }
                }
            }
        } catch (Exception $e) {
            NetsLog::log(self::$NetsLog, "nets_events, Exception:", $e->getMessage());
            NetsLog::log(self::$NetsLog, "nets_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    /**
     * Function to create Nets payment table oxnets.
     * @return null
     */
    public static function createTableStructure($table_name = 'oxnets')
    {
        try {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            switch ($table_name) {
                case 'oxnets':
                    // table oxnets
                    $sSql = "
					CREATE TABLE IF NOT EXISTS `oxnets` (
						`oxnets_id` int(10) unsigned NOT NULL auto_increment,
						`req_data` text collate latin1_general_ci,
						`ret_data` text collate latin1_general_ci,
						`payment_method` varchar(255) collate latin1_general_ci default NULL,
						`transaction_id` varchar(50)  default NULL,
						`charge_id` varchar(50)  default NULL,
                                                `product_ref` varchar(55) collate latin1_general_ci default NULL,
                                                `charge_qty` int(11) default NULL,
                                                `charge_left_qty` int(11) default NULL,
						`oxordernr` int(11) default NULL,
						`oxorder_id` char(32) default NULL,
						`amount` varchar(255) collate latin1_general_ci default NULL,
						`partial_amount` varchar(255) collate latin1_general_ci default NULL,
						`updated` int(2) unsigned default '0',
						`payment_status` int (2) default '2' Comment '0-Failed,1-Cancelled, 2-Authorized,3-Partial Charged,4-Charged,5-Partial Refunded,6-Refunded',
						`hash` varchar(255) default NULL,
						`created` datetime NOT NULL,
						`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
						PRIMARY KEY  (`oxnets_id`)
					) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
				";
                    $oDB->execute($sSql);
                    break;
                default:
                    NetsLog::log(self::$NetsLog, "nets_events, createTableStructure, unknown tablename: " . $table_name);
            }
        } catch (Exception $e) {
            NetsLog::log(self::$NetsLog, "nets_events, Exception:", $e->getMessage());
            NetsLog::log(self::$NetsLog, "nets_events, Exception Trace:", $e->getTraceAsString());
        }
    }

}
