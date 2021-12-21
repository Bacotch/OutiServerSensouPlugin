<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Forms\Faction\FactionForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\LandData\LandDataManager;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

class LandManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    if ($data === 0) {
                        $form = new FactionForm();
                        $form->execute($player);
                    }
                    elseif ($data === 1) {
                        $form = new LandExtendForm();
                        $form->execute($player);
                    }
                    elseif ($data === 2 and LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                        $form = new LandAbandonedForm();
                        $form->execute($player);
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("派閥土地管理フォーム");
            $form->addButton("戻る");
            $form->addButton("土地の拡張");
            if (LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                $form->addButton("現在立っているチャンクの放棄");
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
        }
    }
}