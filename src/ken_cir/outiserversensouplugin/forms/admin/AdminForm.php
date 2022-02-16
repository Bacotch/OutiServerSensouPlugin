<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin;

use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\forms\admin\player\PlayerForm;
use ken_cir\outiserversensouplugin\forms\admin\schedulemessage\ScheduleMessageManagerForm;
use ken_cir\outiserversensouplugin\forms\admin\worldbackup\WorldBackupManager;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use pocketmine\player\Player;

class AdminForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return true;
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
                (new PlayerForm())->execute($player);
            }

            return true;
        });

        $form->setTitle("管理者");
        $form->addButton("戻る");
        $form->addButton("定期メッセージの管理");
        $form->addButton("チャンクバックアップの管理");
        $form->addButton("プレイヤー系");
        $player->sendForm($form);
    }
}