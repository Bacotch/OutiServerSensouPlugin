<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

/**
 * 要望フォーム
 */
final class ReportForm
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
                    }
                    else {
                        $player->sendMessage("§a[システム] レポートを送信しました");
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("レポートフォーム");
            $form->addInput("§cレポートするプレイヤー名", "player_name");
            $form->addInput("§d内容", "content");
            $form->addLabel("§e[注意] 嘘のレポートは処罰される可能性があります\n処罰等が決定次第、内部メールで連絡致します");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error,true, $player);
        }
    }
}