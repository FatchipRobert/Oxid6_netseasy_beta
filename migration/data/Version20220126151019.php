<?php

declare(strict_types = 1);

namespace Es\NetsEasy\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220126151019 extends AbstractMigration {

    public function up(Schema $schema): void {
        $this->addSql("
					CREATE TABLE `oxnets` (
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
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE oxnets');
    }

}
