<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\adminshop;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\adminshopdata\AdminShopData;
use ken_cir\outiserversensouplugin\database\adminshopdata\AdminShopDataManager;
use ken_cir\outiserversensouplugin\forms\admin\AdminForm;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use function is_numeric;

class AdminShopManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        (new AdminForm())->execute($player);
                        return;
                    }
                    elseif ($data === 1) {
                        $this->addAdminShop($player);
                        return;
                    }

                    $this->viewAdminShop($player, AdminShopDataManager::getInstance()->getAll(true)[$data - 2]);
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("アドミンショップの管理");
            $form->addButton("戻る");
            $form->addButton("アイテムの追加");
            foreach (AdminShopDataManager::getInstance()->getAll() as $adminShopData) {
                $form->addButton("#{$adminShopData->getId()} " . ItemFactory::getInstance()->get($adminShopData->getItemId(), $adminShopData->getItemMeta())->getName() . "({$adminShopData->getItemId()}:{$adminShopData->getItemMeta()})");
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function addAdminShop(Player $player): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->execute($player);
                        return;
                    }
                    elseif (!is_numeric($data[1]) or !is_numeric($data[2]) or !is_numeric($data[3]) or !is_numeric($data[4]) or !is_numeric($data[5]) or !is_numeric($data[6]) or !is_numeric($data[7])) {
                        $player->sendMessage("§a[システム] 全項目は入力必須項目で数値である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "addAdminShop"], [$player]), 20);
                        return;
                    }

                    $item = ItemFactory::getInstance()->get((int)$data[1], (int)$data[2]);
                    if (!$item) {
                        $player->sendMessage("§a[システム] 不明なアイテムID、アイテムMeta値です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "addAdminShop"], [$player]), 20);
                        return;
                    }

                    AdminShopDataManager::getInstance()->create($item->getId(), $item->getMeta(), (int)$data[3], (int)$data[4], (int)$data[5], (int)$data[6] , (int)$data[7]);
                    $player->sendMessage("§a[システム] アドミンショップに{$item->getName()}を追加しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "addAdminShop"], [$player]), 20);
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("アドミンショップ アイテムの追加");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("アイテムID", "itemId");
            $form->addInput("アイテムMeta", "itemmeta", "0");
            $form->addInput("最小買取価格", "minPrice");
            $form->addInput("最大買取価格", "maxPrice");
            $form->addInput("現在の買取価格", "price");
            $form->addInput("値段変動する個数", "rateCount");
            $form->addInput("値段変動パーセント", "rateFluctuation");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewAdminShop(Player $player, AdminShopData $adminShopData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($adminShopData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                    elseif ($data === false) {
                        $this->editAdminShop($player, $adminShopData);
                    }
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("アドミンショップ #{$adminShopData->getId()}");
            $form->setContent("ID: {$adminShopData->getId()}\n買取アイテム: " . ItemFactory::getInstance()->get($adminShopData->getItemId(), $adminShopData->getItemMeta())->getName() . "({$adminShopData->getItemId()}:{$adminShopData->getItemMeta()})\n買取価格下限: {$adminShopData->getMinPrice()}\n買取価格上限: {$adminShopData->getMaxPrice()}\n現在の買取価格: {$adminShopData->getPrice()}\n買取価格変動個数: {$adminShopData->getRateCount()}個\n買取価格変動パーセント: {$adminShopData->getRateFluctuation()}\n買取価格調整用値: {$adminShopData->getSellCount()}");
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function editAdminShop(Player $player, AdminShopData $adminShopData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($adminShopData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewAdminShop($player, $adminShopData);
                        return;
                    }
                    elseif ($data[1]) {
                        AdminShopDataManager::getInstance()->delete($adminShopData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    }
                    elseif (!is_numeric($data[2]) or !is_numeric($data[3]) or !is_numeric($data[4]) or !is_numeric($data[5]) or !is_numeric($data[6]) or !is_numeric($data[7])) {
                        $player->sendMessage("§a[システム] デフォルト買取値段と買取値段と買取価格調整用値は入力必須項目で数値である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editAdminShop"], [$player, $adminShopData]), 20);
                        return;
                    }

                    $adminShopData->setMinPrice((int)$data[2]);
                    $adminShopData->setMaxPrice((int)$data[3]);
                    $adminShopData->setPrice((int)$data[4]);
                    $adminShopData->setRateCount((int)$data[5]);
                    $adminShopData->setRateFluctuation((int)$data[6]);
                    $adminShopData->setSellCount((int)$data[7]);
                    $player->sendMessage("§a[システム] 変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $adminShopData]), 20);
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("アドミンショップ #{$adminShopData->getId()} 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addInput("最小買取価格", "minPrice", (string)$adminShopData->getMinPrice());
            $form->addInput("最大買取価格", "minPrice", (string)$adminShopData->getMaxPrice());
            $form->addInput("現在の買取価格", "price", (string)$adminShopData->getPrice());
            $form->addInput("値段変動する個数", "rateCount", (string)$adminShopData->getRateCount());
            $form->addInput("値段変動パーセント", "rateFluctuation", (string)$adminShopData->getRateFluctuation());
            $form->addInput("買取価格調整用値", "sellCount", (string)$adminShopData->getSellCount());
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}