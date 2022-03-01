<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\SimpleForm;
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
                    } elseif ($data === 1) {
                        (new PlayerDatabaseForm())->execute($player);
                    } elseif ($data === 2) {
                        (new FactionDatabaseForm())->execute($player);
                    } elseif ($data === 3) {
                        (new MailDatabaseForm())->execute($player);
                    } elseif ($data === 4) {
                        (new LandDatabaseForm())->execute($player);
                    } elseif ($data === 5) {
                        (new RoleDatabaseForm())->execute($player);
                    } elseif ($data === 6) {
                        (new PlayerRoleDatabaseForm())->execute($player);
                    } elseif ($data === 7) {
                        (new LandConfigDatabaseForm())->execute($player);
                    }
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("データベース管理");
            $form->addButton("キャンセルして戻る");
            $form->addButton("プレイヤーデータ");
            $form->addButton("派閥データ");
            $form->addButton("メールデータ");
            $form->addButton("土地データ");
            $form->addButton("役職データ");
            $form->addButton("プレイヤー役職データ");
            $form->addButton("土地保護データ");
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}