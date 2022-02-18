<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\forms\admin\AdminForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

class DatabaseManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === 0) {
                        (new AdminForm())->execute($player);
                    }
                    elseif ($data === 1) {
                        (new PlayerDatabaseForm())->execute($player);
                    }
                    elseif ($data === 2) {
                        (new FactionDatabaseForm())->execute($player);
                    }
                    elseif ($data === 3) {
                        (new MailDatabaseForm())->execute($player);
                    }
                    elseif ($data === 4 and ($landData = LandDataManager::getInstance()->getChunk($player->getPosition()->getFloorX() >> 4, $player->getPosition()->getFloorZ() >> 4, $player->getWorld()->getFolderName()))) {
                        (new LandDatabaseForm())->execute($player, $landData);
                    }
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("データベース管理");
            $form->addButton("キャンセルして戻る");
            $form->addButton("プレイヤーデータ");
            $form->addButton("派閥データ");
            $form->addButton("メールデータ");
            if (LandDataManager::getInstance()->getChunk($player->getPosition()->getFloorX() >> 4, $player->getPosition()->getFloorZ() >> 4, $player->getWorld()->getFolderName())) {
                $form->addButton("土地データ");
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}