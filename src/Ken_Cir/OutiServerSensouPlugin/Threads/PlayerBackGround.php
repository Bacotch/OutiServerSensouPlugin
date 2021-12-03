<?php

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use pocketmine\item\ItemFactory;
use pocketmine\scheduler\Task;
use pocketmine\Server;

/**
 * プレイヤー裏処理タスク
 * おうちウォッチ付与・不正確認など
 * 基本表には出ない別にラグが出ても対して問題のないもの
 */
class PlayerBackGround extends Task
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        // ---サーバーにいるプレイヤーにおうちウォッチがなかったら付与する
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $item = ItemFactory::getInstance()->get(347);
            $item->setCustomName("OutiWatch");
            if (!$player->getInventory()->contains($item)) {
                $player->getInventory()->addItem($item);
            }
        }
    }
}