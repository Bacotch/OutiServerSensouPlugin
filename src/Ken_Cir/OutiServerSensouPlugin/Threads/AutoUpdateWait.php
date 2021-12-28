<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use function count;

class AutoUpdateWait extends Task
{
    private int $seconds;

    public function __construct()
    {
        $this->seconds = 600;
    }

    public function onRun(): void
    {
        $this->seconds--;

        if ($this->seconds < 1) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $player->kick("サーバー再起動");
            }

            Server::getInstance()->shutdown();
        }
        elseif ($this->seconds < 5) {
            Server::getInstance()->broadcastMessage("§a[システム] §e[警告] §fサーバーアップデートの準備が整いました！あと{$this->seconds}秒でサーバーは再起動されます");
        }
        elseif ($this->seconds % 60 === 0) {
            Server::getInstance()->broadcastMessage("§a[システム] §e[警告] §fサーバーアップデートの準備が整いました！あと" . $this->seconds / 60 . "分でサーバーは再起動されます");
        }
        elseif (count(Server::getInstance()->getOnlinePlayers()) < 1) {
            Server::getInstance()->shutdown();
        }
    }
}