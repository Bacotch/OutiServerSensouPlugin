<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\player;

use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\forms\admin\AdminForm;
use pocketmine\player\Player;

class PlayerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;
            elseif ($data === 0) {
                (new AdminForm())->execute($player);
            } elseif ($data === 1) {

            }
        });

        $form->setTitle("プレイヤー系管理");
        $form->addButton("戻る");
        $form->addButton("プレイヤーの所持金設定");
        $player->sendForm($form);
    }
}