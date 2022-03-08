<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\money;

use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\forms\faction\FactionForm;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class FactionMoneyOperationForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, FactionData $factionData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        (new FactionMoneyManagerForm())->execute($player);
                        return;
                    }
                    elseif (!is_numeric($data[2])) {
                        $player->sendMessage("§a[システム] 金庫から引き出す・資金から預け入れる金額は入力必須項目で数値である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $factionData]), 20);
                        return;
                    }

                    $money = (int)$data[2];

                    if ($data[1] === 0) {
                        if ($money > $factionData->getSafe()) {
                            $player->sendMessage("§a[システム] 金庫にあるお金以上を引き出すことはできません");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $factionData]), 20);
                            return;
                        }

                        $factionData->setSafe($factionData->getSafe() - $money);
                        $factionData->setMoney($factionData->getMoney() + $money);
                        $player->sendMessage("§a[システム] 金庫から{$money}円引き出しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new FactionForm(), "execute"], [$player]), 20);
                    }
                    elseif ($data[1] === 1) {
                        if ($money > $factionData->getMoney()) {
                            $player->sendMessage("§a[システム] 資金にあるお金以上を預け入れることはできません");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player, $factionData]), 20);
                            return;
                        }

                        $factionData->setSafe($factionData->getSafe() + $money);
                        $factionData->setMoney($factionData->getMoney() - $money);
                        $player->sendMessage("§a[システム] 金庫に{$money}円預け入れました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new FactionForm(), "execute"], [$player]), 20);
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("派閥金庫の操作");
            $form->addToggle("キャンセルして戻る");
            $form->addDropdown("モード", ["引き出し", "預け入れ"]);
            $form->addInput("金庫から引き出す・資金から預け入れる金額", "money");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}