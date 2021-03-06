<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\land;


use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\forms\faction\FactionForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

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
                    } elseif ($data === 1) {
                        $form = new LandExtendForm();
                        $form->execute($player);
                    } elseif ($data === 2 and LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                        $form = new LandAbandonedForm();
                        $form->execute($player);
                    } elseif ($data === 3 and LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                        $form = new LandConfigForm();
                        $form->execute($player);
                    } elseif ($data === 3 and LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                        $form = new LandAbandonedForm();
                        $form->execute($player);
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("??????????????????????????????");
            $form->addButton("??????");
            $form->addButton("???????????????");
            if (LandDataManager::getInstance()->hasChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName())) {
                $form->addButton("??????????????????????????????????????????");
                $form->addButton("????????????????????????????????????????????????");
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }
}
