<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\adminshopdata\AdminShopData;
use ken_cir\outiserversensouplugin\database\adminshopdata\AdminShopDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use function is_numeric;

class AdminShopForm
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
                        (new OutiWatchForm())->execute($player);
                        return;
                    }

                    $this->sellAdminShop($player, AdminShopDataManager::getInstance()->getAll(true)[$data - 1]);
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("アドミンショップ");
            $form->addButton("戻る");
            foreach (AdminShopDataManager::getInstance()->getAll() as $adminShopData) {
                $form->addButton(ItemFactory::getInstance()->get($adminShopData->getItemId(), $adminShopData->getItemMeta())->getName());
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function sellAdminShop(Player $player, AdminShopData $adminShopData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($adminShopData) {
                try {
                    if ($data === null) return;
                    elseif ($data[1]) {
                        $this->execute($player);
                        return;
                    }
                    elseif (!is_numeric($data[2])) {
                        $player->sendMessage("§a[システム] 購入個数は入力必須項目で数値である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "sellAdminShop"], [$player, $adminShopData]), 20);
                        return;
                    }

                    $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                    $item = ItemFactory::getInstance()->get($adminShopData->getItemId(), $adminShopData->getItemMeta(), (int)$data[2]);
                    if (!$player->getInventory()->contains($item)) {
                        $player->sendMessage("§a[システム] 所持している個数以上売却できません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "sellAdminShop"], [$player, $adminShopData]), 20);
                        return;
                    }

                    $playerData->setMoney($playerData->getMoney() + ($adminShopData->getPrice() * $item->getCount()));
                    $player->getInventory()->removeItem($item);
                    $adminShopData->setSellCount($adminShopData->getSellCount() + $item->getCount());
                    $player->sendMessage("§a[システム] {$item->getName()}を{$item->getCount()}個売却しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("アドミンショップ 買取");
            $form->addLabel("アイテム: " . ItemFactory::getInstance()->get($adminShopData->getItemId(), $adminShopData->getItemMeta())->getName() . "\n1個当たりの買取額: {$adminShopData->getPrice()}");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("買取個数", "sellCount");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}