<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\schedulemessage;

use ken_cir\outiserversensouplugin\database\schedulemessagedata\ScheduleMessageData;
use ken_cir\outiserversensouplugin\database\schedulemessagedata\ScheduleMessageDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

class ScheduleMessageEditForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, ScheduleMessageData $scheduleMessageData): void
    {
        $form = new CustomForm(function (Player $player, $data) use ($scheduleMessageData) {
            if ($data === null) return true;
            elseif ($data[0]) {
                $form = new ScheduleMessageManagerForm();
                $form->execute($player);
            } elseif ($data[1]) {
                ScheduleMessageDataManager::getInstance()->delete($scheduleMessageData->getId());
                $player->sendMessage("§a[システム] 定期メッセージを削除しました");
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new ScheduleMessageManagerForm(), "execute"], [$player]), 10);
            } elseif (isset($data[2])) {
                $scheduleMessageData->setContent($data[2]);
                $player->sendMessage("§a[システム] 定期メッセージの内容を変更しました");
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new ScheduleMessageManagerForm(), "execute"], [$player]), 10);
            } else {
                $this->execute($player, $scheduleMessageData);
            }

            return true;
        });

        $form->setTitle("定期メッセージの修正・削除");
        $form->addToggle("キャンセルして戻る", false);
        $form->addToggle("削除して戻る", false);
        $form->addInput("メッセージ", "content", $scheduleMessageData->getContent());
        $player->sendForm($form);
    }
}