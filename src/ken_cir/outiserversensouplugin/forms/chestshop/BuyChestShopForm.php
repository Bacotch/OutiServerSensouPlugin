<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\chestshop;

use DateTime;
use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\block\tile\Chest;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use function floor;


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
                        $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());

                        // 関税
                        $duty = ($chestShopData->getPrice() * (int)$data[1]) * ($chestShopData->getDuty() * 0.01);
                        // 価格
                        $price = $duty + ((int)$data[1] * $chestShopData->getPrice());
                        $duty = (int)floor($duty);
                        $price = (int)floor($price);

                        if ($price > $factionData->getMoney()) {
                            $player->sendMessage("§a[システム] 派閥資金があと" . $price - $factionData->getMoney() . "円足りていません");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $chestShopData]), 10);
                            return;
                        }

                        $item = ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta(), (int)$data[1]);
                        $chestFactionData = FactionDataManager::getInstance()->get($chestShopData->getFactionId());
                        $chestTile = $player->getWorld()->getTileAt($chestShopData->getChestX(), $chestShopData->getChestY(), $chestShopData->getChestZ());
                        if (!$chestTile instanceof Chest) {
                            $player->sendMessage("§a[システム] チェストの検知に失敗しました");
                            return;
                        } elseif (!$player->getInventory()->canAddItem($item)) {
                            $player->sendMessage("§a[システム] インベントリの空きが足りていません");
                            return;
                        } elseif (!$chestTile->getInventory()->contains($item)) {
                            $player->sendMessage("§a[システム] 在庫が足りていません");
                            return;
                        }

                        $ownerPlayerData = PlayerDataManager::getInstance()->getXuid($chestShopData->getOwnerXuid());
                        $player->getInventory()->addItem($item);
                        $chestTile->getInventory()->removeItem($item);
                        $factionData->setMoney($factionData->getMoney() - $price);
                        $chestFactionData->setSafe($factionData->getSafe() + $duty);
                        $chestFactionData->setMoney($chestFactionData->getMoney() + ((int)$data[1] * $chestShopData->getPrice()));
                        $player->sendMessage("§a[システム] {$item->getName()}を$data[1]個、{$price}円で{$factionData->getName()}から購入しました");
                        $time = new DateTime('now');
                        MailDataManager::getInstance()->create($ownerPlayerData->getXuid(),
                            "チェストショップ購入通知",
                            "{$player->getName()}があなたのチェストショップで{$item->getName()}を{$item->getCount()}個購入しました\n利益として" . ((int)$data[1] * $chestShopData->getPrice()) . "円受け取りました",
                            "システム",
                            $time->format("Y年m月d日 H時i分")
                        );
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $item = ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta());
            $form->setTitle("チェストショップ(貿易所) 購入");
            $form->addLabel("販売物: {$item->getVanillaName()}\n1個" . $chestShopData->getPrice() + ($chestShopData->getPrice() * ($chestShopData->getDuty() * 0.01)) . "円\n関税{$chestShopData->getDuty()}パーセント");
            $form->addInput("購入個数", "buycount");
            $player->sendForm($form);
        } catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}