<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use JetBrains\PhpStorm\Pure;
use ken_cir\outiserversensouplugin\database\wardata\WarData;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use function date;

/**
 *
 */
class WarCheckerTask extends Task
{
    public function onRun(): void
    {
        foreach (WarDataManager::getInstance()->getAll() as $warData) {
            if ($warData->getStartDay() >= (int)date("d") and $warData->getStartHour() >= (int)date("H") and $warData->getStartMinutes() >= (int)date("i")) {
                Server::getInstance()->broadcastMessage("§a[システム] 戦争が開始しました！");
            }
        }
    }
}