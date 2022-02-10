<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\chestshop;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopData;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\BaseSign;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Vecnavium\FormsUI\CustomForm;

final class BuyChestShopForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, ChestShopData $chestShopData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {

                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $item = ItemFactory::getInstance()->get($chestShopData->getItemId(), $chestShopData->getItemMeta());
            $form->setTitle("チェストショップ(貿易所) 購入");
            $form->addLabel("販売物: {$item->getVanillaName()}\n1個{$chestShopData->getPrice()}\n関税{$chestShopData->getDuty()}%");
            $form->addInput("購入個数", "buycount");
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}