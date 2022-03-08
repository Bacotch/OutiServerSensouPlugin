<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\land;


use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\forms\faction\money\FactionMoneyManagerForm;
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
            }
            else {
                $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());
                $price = Main::getInstance()->getConfig()->get("land_price", 10000);

                if ($price > $factionData->getMoney()) {
                    $player->sendMessage("§a[システム] 資金があと" . $price - $factionData->getMoney() . "円足りていません");
                    return;
                }

                $factionData->setMoney($factionData->getMoney() - $price);
                LandDataManager::getInstance()->create($playerData->getFaction(), (int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                $player->sendMessage("§a[システム] 現在いるチャンクを購入しました");
            }
        } catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }
}
