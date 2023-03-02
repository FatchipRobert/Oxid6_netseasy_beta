<?php

/**
 * This file is part of OXID NETS module.
 * 
 */

namespace Es\NetsEasy\Core;

use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class defines what module does on Shop events.
 */
class Events
{

    /**
     * Function to execute action on activate event
     * @return void
     */
    static function onActivate()
    {
        self::executeModuleMigrations();
    }

    /**
     * Function to execute action on deactivate event
     * @return void
     */
    static function onDeactivate()
    {
        self::executeModuleMigrations();
    }

    /**
     * Execute necessary module migrations on activate event
     * @return void
     */
    static function executeModuleMigrations()
    {
        $migrations = (new MigrationsBuilder())->build();
        $output = new BufferedOutput();
        $migrations->setOutput($output);
        $needsUpdate = $migrations->execute('migrations:up-to-date', 'esnetseasy');
        if ($needsUpdate) {
            $migrations->execute('migrations:migrate', 'esnetseasy');
        }
    }

}
