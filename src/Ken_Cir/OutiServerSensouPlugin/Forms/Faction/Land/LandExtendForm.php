<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
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
                $player->sendMessage("そのチャンクは既に購入されています");
            } else {
                $playerData = PlayerDataManager::getInstance()->get($player->getName());
                LandDataManager::getInstance()->create($playerData->getFaction(), (int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                $player->sendMessage("現在いるチャンクを購入しました");
            }
        } catch (Error|Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
        }
    }
}
