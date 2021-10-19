<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\CustomForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Utils\PluginUtils;
use pocketmine\Player;

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
    public function execute(Player $player)
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif (!isset($data[0]) or !isset($data[1])) return true;
                    PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Report_Request_Webhook", ""), "**REPORT**\n{$player->getName()} からのレポート\nレポート対象のプレイヤー名: $data[0]\nレポート内容: $data[1]");
                    $player->sendMessage("§a[システム] レポートを送信しました");
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
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
            Main::getInstance()->getPluginLogger()->error($error, $player);
        }
    }
}