<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use pocketmine\scheduler\Task;
use pocketmine\Server;

/**
 * PMMPのアップデートを確認するTask
 */
final class PMMPAutoUpdateChecker extends Task
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        Server::getInstance()->getUpdater()->doCheck();
        // プラグイン自動アップデート処理タスク登録
        Server::getInstance()->getAsyncPool()->submitTask(new PluginAutoUpdateChecker());
    }
}