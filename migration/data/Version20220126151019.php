<?php

declare(strict_types = 1);

namespace Es\NetsEasy\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Core\DebugHandler;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

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
    }

    /**
     * Function to check and execute db modifications.
     * @return null
     */
    public function executeModifications()
    {

        $oDebugHandler = \oxNew(DebugHandler::class);
        $oNetsPaymentTypes = \oxNew(NetsPaymentTypes::class);
        $payment_types = $oNetsPaymentTypes->nets_payment_types;
        foreach ($payment_types as $payment_type) {
            $payment_id = $payment_type['payment_id'];
        }
        //check if nets payment is completed
        $queryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
                ->select('oxid')
                ->from('oxpayments')
                ->where('oxid = ?')
                ->setParameter(0, $payment_id);
        $payment_id_exists = $queryBuilder->execute()->fetchOne();
        if (!$payment_id_exists) {
            //create payment
            $desc = $oNetsPaymentTypes->getNetsPaymentDesc($payment_id);
            if (isset($desc) && $desc) {
                $queryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
                $queryBuilder->insert('oxpayments')
                        ->values(
                                array(
                                    'OXID' => '?',
                                    'OXACTIVE' => '?',
                                    'OXDESC' => '?',
                                    'OXADDSUM' => '?',
                                    'OXADDSUMTYPE' => '?',
                                    'OXFROMBONI' => '?',
                                    'OXFROMAMOUNT' => '?',
                                    'OXTOAMOUNT' => '?',
                                    'OXVALDESC' => '?',
                                    'OXCHECKED' => '?',
                                    'OXDESC_1' => '?',
                                    'OXVALDESC_1' => '?',
                                    'OXDESC_2' => '?',
                                    'OXVALDESC_2' => '?',
                                    'OXDESC_3' => '?',
                                    'OXVALDESC_3' => '?',
                                    'OXLONGDESC' => '?',
                                    'OXLONGDESC_1' => '?',
                                    'OXLONGDESC_2' => '?',
                                    'OXLONGDESC_3' => '?',
                                    'OXSORT' => '?',
                                )
                        )
                        ->setParameter(0, $payment_id)->setParameter(1, 1)->setParameter(2, $desc)->setParameter(3, 0)
                        ->setParameter(4, 'abs')->setParameter(5, 0)->setParameter(6, 0)->setParameter(7, 1000000)->setParameter(8, '')->setParameter(9, 0)
                        ->setParameter(10, $desc)->setParameter(11, '')->setParameter(12, '')->setParameter(13, '')->setParameter(14, '')->setParameter(15, '')
                        ->setParameter(16, '')->setParameter(17, '')->setParameter(18, '')->setParameter(19, '')->setParameter(20, 0)
                        ->execute();
            }
        }
        //activate payment
        $active = $this->oxSession->getVariable('activeStatus');
        $queryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
                ->update('oxpayments', 'o')
                ->set('o.oxactive', '?')
                ->where('o.oxid = ?')
                ->setParameter(0, $active)
                ->setParameter(1, $payment_id)->execute();
    }

}
