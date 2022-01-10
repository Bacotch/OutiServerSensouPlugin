<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

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
    public function execute(Player $player)
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif (!isset($data[0]) or !isset($data[1])) return true;
                    $player->sendMessage("§a[システム] レポートを送信しました");
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("レポートフォーム");
            $form->addInput("§cレポートするプレイヤー名", "player_name");
            $form->addInput("§d内容", "content");
            $form->addLabel("§e[注意] 嘘のレポートは処罰される可能性があります\n処罰等が決定次第、内部メールで連絡致します");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }
}