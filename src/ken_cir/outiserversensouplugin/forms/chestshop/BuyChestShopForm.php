<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\chestshop;

use Error;
use Exception;
use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;

class BuyChestShopForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, ChestShopData $chestShopData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($chestShopData) {
                try {
                    if ($data === null) return;
                    elseif (!isset($data[1]) or !is_numeric($data[1])) {
                        $this->execute($player, $chestShopData);
                    } else {
                        $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                        // 関税
                        $duty = ($chestShopData->getPrice() * (int)$data[1]) * ($chestShopData->getDuty() * 0.01);
                        // 価格
                        $price = $duty + ((int)$data[1] * $chestShopData->getPrice());
                        if ($price > $playerData->getMoney()) {
                            $player->sendMessage("§a[システム] 所持金があと" . $price - $playerData->getMoney() . "円足りていません");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $chestShopData]), 10);
                            return;
                        }

                        $item = ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta(), (int)$data[1]);
                        $factionData = FactionDataManager::getInstance()->get($chestShopData->getFactionId());
                        if (!$player->getInventory()->canAddItem($item)) {
                            $player->sendMessage("§a[システム] インベントリの空きが足りていません");
                            return;
                        }

                        $player->getInventory()->addItem($item);
                        $player->getWorld()->getTileAt($chestShopData->getChestX(), $chestShopData->getChestY(), $chestShopData->getChestZ())->getInventory()->removeItem($item);
                        $playerData->setMoney($price - $playerData->getMoney());
                        $factionData->setMoney($factionData->getMoney() + $price);
                        $player->sendMessage("§a[システム] {$item->getName()}を$data[1]個、{$price}円で{$factionData->getName()}から購入しました");
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $item = ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta());
            $form->setTitle("チェストショップ(貿易所) 購入");
            $form->addLabel("販売物: {$item->getVanillaName()}\n1個" . $chestShopData->getPrice() + ($chestShopData->getPrice() * ($chestShopData->getDuty() * 0.01)) . "円\n関税{$chestShopData->getDuty()}パーセント");
            $form->addInput("購入個数", "buycount");
            $player->sendForm($form);
        } catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}