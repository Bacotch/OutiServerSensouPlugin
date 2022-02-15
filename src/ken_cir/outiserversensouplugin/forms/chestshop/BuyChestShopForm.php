<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\chestshop;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopData;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use jojoe77777\FormAPI\CustomForm;

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
                    elseif (!isset($data[1]) or is_numeric($data[1])) {
                        $this->execute($player, $chestShopData);
                    } else {

                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $item = ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta());
            $form->setTitle("チェストショップ(貿易所) 購入");
            $form->addLabel("販売物: {$item->getVanillaName()}\n1個{$chestShopData->getPrice()}円\n関税{$chestShopData->getDuty()}パーセント");
            $form->addInput("購入個数", "buycount");
            $player->sendForm($form);
        } catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}