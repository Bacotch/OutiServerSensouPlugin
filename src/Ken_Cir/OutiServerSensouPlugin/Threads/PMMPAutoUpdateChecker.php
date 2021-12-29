<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use pocketmine\scheduler\Task;
use pocketmine\Server;

/**
 * PMMPのアップデートを確認するTask
 */
class PMMPAutoUpdateChecker extends Task
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        Server::getInstance()->getUpdater()->doCheck();
    }
}