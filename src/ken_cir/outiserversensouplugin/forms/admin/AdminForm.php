<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin;

use Error;
use Exception;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\forms\admin\database\DatabaseManagerForm;
use ken_cir\outiserversensouplugin\forms\admin\schedulemessage\ScheduleMessageManagerForm;
use ken_cir\outiserversensouplugin\forms\admin\worldbackup\WorldBackupManager;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

class AdminForm
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
                        $form = new OutiWatchForm();
                        $form->execute($player);
                    } elseif ($data === 1) {
                        $form = new ScheduleMessageManagerForm();
                        $form->execute($player);
                    } elseif ($data === 2) {
                        $form = new WorldBackupManager();
                        $form->execute($player);
                    } elseif ($data === 3) {
                        (new DatabaseManagerForm())->execute($player);
                    }
                }
                catch (Error|Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("管理者");
            $form->addButton("戻る");
            $form->addButton("定期メッセージの管理");
            $form->addButton("チャンクバックアップの管理");
            $form->addButton("データベース管理");
            $form->addButton("キャッシュ管理");
            $player->sendForm($form);
        }
        catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}