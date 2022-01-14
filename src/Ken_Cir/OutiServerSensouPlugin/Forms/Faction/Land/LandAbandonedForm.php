<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\ModalForm;

final class LandAbandonedForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) {
                try {
                    if ($data === true) {
                        $landData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                        LandConfigDataManager::getInstance()->deleteLand($landData->getId());
                        LandDataManager::getInstance()->delete($landData->getId());
                        $player->sendMessage("§a[システム] 放棄しました");
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }
            });

            $form->setTitle("土地放棄確認");
            $form->setContent("現在立っているチャンクの土地を放棄してもよろしいですか？");
            $form->setButton1("放棄");
            $form->setButton2("キャンセル");
            $player->sendForm($form);
        } catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }
}
