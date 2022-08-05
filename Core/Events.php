<?php

/**
 * This file is part of OXID NETS module.
 * 
 */

namespace Es\NetsEasy\Core;

use Exception;
use Makaira\OxidConnectEssential\Utils\ModuleSettingsProvider;
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class defines what module does on Shop events.
 */
class Events
{

    static $NetsLog = true;

    /**
     * Function to execute action on activate event
     * @return null
     */
    static function onActivate()
    {
        // execute module migrations
        \oxRegistry::getSession()->setVariable('activeStatus', 1);
        if (empty(\oxRegistry::getSession()->getVariable('isEventUnitTest'))) {
            self::executeModuleMigrations();
        }
    }

    /**
     * Function to execute action on deactivate event
     * @return null
     */
    static function onDeactivate()
    {
        \oxRegistry::getSession()->setVariable('activeStatus', 0);
        if (empty(\oxRegistry::getSession()->getVariable('isEventUnitTest'))) {
            self::executeModuleMigrations();
        }
    }

    /**
     * Execute necessary module migrations on activate event
     */
    static function executeModuleMigrations()
    {
        $migrations = (new MigrationsBuilder())->build();
        $output = new BufferedOutput();
        $migrations->setOutput($output);
        if (empty(\oxRegistry::getSession()->getVariable('isEventUnitTest'))) {
            $neeedsUpdate = $migrations->execute('migrations:up-to-date', 'esnetseasy');
            if ($neeedsUpdate) {
                $migrations->execute('migrations:migrate', 'esnetseasy');
            }
        }
    }

}
