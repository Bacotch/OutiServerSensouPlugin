<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\forms\chestshop\BuyChestShopForm;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;

class ChestShopDatabaseForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, ChestShopData $chestShopData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($chestShopData) {
                try {
                    if ($data === null or $data === 0) return;
                    elseif ($data === 1) {
                        (new BuyChestShopForm())->execute($player, $chestShopData);
                    } elseif ($data === 2) {
                        $this->viewChestShopData($player, $chestShopData);
                    }
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->addButton("閉じる");
            $form->addButton("チェストショップ購入");
            $form->addButton("チェストショップ編集");
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewChestShopData(Player $player, ChestShopData $chestShopData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($chestShopData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->execute($player, $chestShopData);
                    } elseif ($data === false) {
                        $this->editChestShopData($player, $chestShopData);
                    }
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("チェストショップデータ #{$chestShopData->getId()}");
            $form->setContent("チェストショップID: {$chestShopData->getId()}\nオーナー: " . PlayerDataManager::getInstance()->getXuid($chestShopData->getOwnerXuid())->getName() . "(XUID: {$chestShopData->getOwnerXuid()})\n派閥: " . FactionDataManager::getInstance()->get($chestShopData->getFactionId())->getName() . "(ID: {$chestShopData->getFactionId()})\nチェスト・看板が設置してあるワールド名: {$chestShopData->getWorldName()}\nチェストX座標: {$chestShopData->getChestX()}\nチェストY座標: {$chestShopData->getChestY()}\nチェストZ座標: {$chestShopData->getChestZ()}\n看板X座標: {$chestShopData->getSignboardX()}\n看板Y座標: {$chestShopData->getSignboardY()}\n看板Z座標: {$chestShopData->getSignboardZ()}\n販売アイテム名: " . ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta())->getName() . "({$chestShopData->getItemId()}:{$chestShopData->getItemMeta()})\n1個あたりの値段: {$chestShopData->getPrice()}\n関税: {$chestShopData->getDuty()}パーセント");
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function editChestShopData(Player $player, ChestShopData $chestShopData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($chestShopData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewChestShopData($player, $chestShopData);
                        return;
                    } elseif (!is_numeric($data[2]) or !is_numeric($data[3]) or !is_numeric($data[4]) or !is_numeric($data[5])) {
                        $player->sendMessage("§a[システム] アイテムID・アイテムMeta・1個あたりの値段・関税は入力必須項目で数値である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editChestShopData"], [$player, $chestShopData]), 20);
                        return;
                    }

                    $chestShopData->setOwnerXuid(PlayerDataManager::getInstance()->getAll(true)[(int)$data[1]]->getXuid());
                    $chestShopData->setItemId((int)$data[2]);
                    $chestShopData->setItemMeta((int)$data[3]);
                    $chestShopData->setPrice((int)$data[4]);
                    $chestShopData->setDuty((int)$data[5]);
                    $player->sendMessage("§a[システム] 変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $chestShopData]), 20);
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });


            $playerDefault = 0;
            $playerDatas = array_filter(PlayerDataManager::getInstance()->getAll(true), function (PlayerData $playerData) use ($chestShopData) {
                return $chestShopData->getFactionId() === $playerData->getFaction();
            });
            foreach ($playerDatas as $key => $playerData) {
                if ($playerData->getXuid() === $chestShopData->getOwnerXuid()) {
                    $playerDefault = $key;
                    break;
                }
            }
            $playerDatas = array_map(function (PlayerData $playerData) {
                return $playerData->getName();
            }, $playerDatas);

            $form->setTitle("チェストショップデータ #{$chestShopData->getId()} 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addDropdown("オーナー", $playerDatas, $playerDefault);
            $form->addInput("アイテムID", "itemID", (string)$chestShopData->getItemId());
            $form->addInput("アイテムMeta", "itemMeta", (string)$chestShopData->getItemMeta());
            $form->addInput("1個あたりの値段", "price", (string)$chestShopData->getPrice());
            $form->addInput("関税", "duty", (string)$chestShopData->getDuty());
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}