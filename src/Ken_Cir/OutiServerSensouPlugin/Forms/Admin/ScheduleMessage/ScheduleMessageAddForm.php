<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Admin\ScheduleMessage;

use Ken_Cir\OutiServerSensouPlugin\Database\ScheduleMessageData\ScheduleMessageDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

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
            }
            elseif (isset($data[1])) {
                ScheduleMessageDataManager::getInstance()->create($data[1]);
                $player->sendMessage("§a[システム] 定期メッセージを追加しました");
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
            }
            else {
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