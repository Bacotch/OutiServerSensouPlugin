<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\schedulemessage;

use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\database\schedulemessagedata\ScheduleMessageDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class ScheduleMessageAddForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data === null) return true;
            elseif ($data[0]) {
                $form = new ScheduleMessageManagerForm();
                $form->execute($player);
            } elseif (isset($data[1])) {
                ScheduleMessageDataManager::getInstance()->create($data[1]);
                $player->sendMessage("§a[システム] 定期メッセージを追加しました");
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
            } else {
                $this->execute($player);
            }

            return true;
        });

        $form->setTitle("定期メッセージ追加");
        $form->addToggle("キャンセルして戻る", false);
        $form->addInput("メッセージ", "content");
        $player->sendForm($form);
    }
}