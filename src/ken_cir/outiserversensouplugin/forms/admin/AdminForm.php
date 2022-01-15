<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin;

use ken_cir\outiserversensouplugin\forms\admin\schedulemessage\ScheduleMessageManagerForm;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

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
            }
            elseif ($data === 1) {
                $form = new ScheduleMessageManagerForm();
                $form->execute($player);
            }

            return true;
        });

        $form->setTitle("管理者");
        $form->addButton("戻る");
        $form->addButton("定期メッセージの管理");
        $player->sendForm($form);
    }
}