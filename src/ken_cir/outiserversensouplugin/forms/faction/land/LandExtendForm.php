<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\land;


use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

/**
 * 土地を拡張する
 */
class LandExtendForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * @return void
     */
    public function execute(Player $player): void
    {
        try {
            // もしそのチャンクが誰かに購入されていたら
            if (LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                $player->sendMessage("§a[システム] そのチャンクは既に購入されています");
            } else {
                $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                LandDataManager::getInstance()->create($playerData->getFaction(), (int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                $player->sendMessage("§a[システム] 現在いるチャンクを購入しました");
            }
        } catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }
}
