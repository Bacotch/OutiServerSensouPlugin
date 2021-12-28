<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoUpdateCheck extends Task
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        Server::getInstance()->getUpdater()->doCheck();
    }
}