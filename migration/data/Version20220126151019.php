<?php

declare(strict_types = 1);

namespace Es\NetsEasy\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Api\NetsLog;

final class Version20220126151019 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        
        $this->addSql("
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
				");



        //extend the oxuser table
            $this->executeModifications();
        
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE oxnets');
    }

    /**
     * Function to check and execute db modifications.
     * @return null
     */
    public function executeModifications()
    {
        try {
            $payment_types = NetsPaymentTypes::$nets_payment_types;
            foreach ($payment_types as $payment_type) {
                $payment_id = $payment_type['payment_id'];
            }
            //check if nets payment is completed
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            $payment_id_exists = $oDB->getOne("SELECT oxid FROM oxpayments WHERE oxid = ?", [
                $payment_id
            ]);
            if (!$payment_id_exists) {
                //create payment
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
                }
            }
            //activate payment
            $active = \oxRegistry::getSession()->getVariable('activeStatus');
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

}
