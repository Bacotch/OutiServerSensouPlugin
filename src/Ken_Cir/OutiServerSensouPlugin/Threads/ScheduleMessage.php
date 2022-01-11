<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use Ken_Cir\OutiServerSensouPlugin\Managers\ScheduleMessageData\ScheduleMessageDataManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use function count;

/**
 * 定期メッセージTASK
 */
class ScheduleMessage extends Task
{
    /**
     * メッセージ配列にアクセスする用
     * @var int
     */
    private int $next;

    public function __construct()
    {
        $this->next = 0;
    }

    public function onRun(): void
    {
        $messages = ScheduleMessageDataManager::getInstance()->getAll();
        if (count($messages) < 1) return;
        elseif (count($messages) < ($this->next + 1)) $this->next = 0;

        Server::getInstance()->broadcastMessage("§a[システム][定期] {$messages[$this->next]->getContent()}");
        $this->next++;
    }
}