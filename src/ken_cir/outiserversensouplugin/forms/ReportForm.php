<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use jojoe77777\FormAPI\CustomForm;

/**
 * 要望フォーム
 */
class ReportForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif (!isset($data[0], $data[1])) {
                        $player->sendMessage("§a[システム] プレイヤー名と内容部分を空にすることはできません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                    } else {
                        $player->sendMessage("§a[システム] レポートを送信しました");
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("レポートフォーム");
            $form->addInput("§cレポートするプレイヤー名", "player_name");
            $form->addInput("§d内容", "content");
            $form->addLabel("§e[注意] 嘘のレポートは処罰される可能性があります\n処罰等が決定次第、内部メールで連絡致します");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}