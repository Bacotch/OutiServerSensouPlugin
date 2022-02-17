<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\player;

use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use function is_numeric;
use function strtolower;

class PlayerSetMoneyForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data === null) return;
            elseif ($data[0]) {
                (new PlayerForm())->execute($player);
                return;
            } elseif (!isset($data[2], $data[3]) or (isset($data[2], $data[3]) and !is_numeric($data[3]))) {
                $player->sendMessage("§a[システム] プレイヤー名と金額は入力必須項目で、金額は数値入力です");
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                return;
            }

            $mode = $data[1];
            $playerData = PlayerDataManager::getInstance()->getName(strtolower($data[2]));
            $money = (int)$data[3];
            $successMsg = "";
            if (!$playerData) {
                $player->sendMessage("§a[システム] プレイヤー名 $data[2] のデータは存在しません");
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                return;
            }

            if ($mode === 0) {
                $playerData->setMoney($playerData->getMoney() + $money);
                $successMsg = "{$playerData->getName()}に{$money}増額しました";
            } elseif ($mode === 1) {
                $playerData->setMoney($playerData->getMoney() - $money);
                $successMsg = "{$playerData->getName()}から{$money}減額しました";
            } elseif ($mode === 2) {
                $playerData->setMoney($money);
                $successMsg = "{$playerData->getName()}の所持金を{$money}にしました";
            }

            $player->sendMessage("§a[システム] $successMsg");
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
        });

        $form->setTitle("プレイヤーの所持金設定");
        $form->addToggle("キャンセルして戻る");
        $form->addDropdown("モード", ["増額", "減額", "設定"], 2);
        $form->addInput("プレイヤー名", "playerName");
        $form->addInput("金額", "setMoney");
        $player->sendForm($form);
    }
}