<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\chestshop;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopDataManager;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Vecnavium\FormsUI\CustomForm;

final class CreateChestShopForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, BaseSign $sign, Position $signPostion, Position $chestPosition): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($sign, $signPostion, $chestPosition): void{
                try {
                    if ($data === null) return;
                    elseif (!isset($data[0], $data[1], $data[2], $data[3]) or (isset($data[0], $data[1], $data[2], $data[3]) and (!is_numeric($data[0]) or !is_numeric($data[1]) or !is_numeric($data[2]) or !is_numeric($data[3])))) {
                        $this->execute($player, $sign, $signPostion, $chestPosition);
                        return;
                    }

                    $item = ItemFactory::getInstance()->get((int)$data[0], (int)$data[1]);
                    if (!$item) {
                        $player->sendMessage("§a[システム] アイテムが見つからないか、登録されていません");
                        return;
                    }

                    $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                    $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());
                    ChestShopDataManager::getInstance()->create($playerData->getFaction(), $player->getWorld()->getFolderName(), $chestPosition->getFloorX(), $chestPosition->getFloorY(), $chestPosition->getFloorZ(), $signPostion->getFloorX(), $signPostion->getFloorY(), $signPostion->getFloorZ(), $item->getId(), $item->getMeta(), (int)$data[2], (int)$data[3]);
                    $player->getWorld()->setBlock($signPostion, $sign->setText(new SignText([
                        "{$factionData->getName()}の貿易所",
                        "販売物: {$item->getName()}",
                        "値段: $data[2]",
                        "関税: $data[3]%"
                    ])));

                    $player->sendMessage("§a[システム] チェストショップ(貿易所)を作成しました");
                }
                catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("チェストショップ作成");
            $form->addInput("販売するアイテムID", "itemid");
            $form->addInput("販売するアイテムmeta値", "itemmeta");
            $form->addInput("値段", "price");
            $form->addInput("関税(%)", "duty");
            $player->sendForm($form);
        }
        catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}