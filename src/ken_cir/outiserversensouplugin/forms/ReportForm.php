<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms;

use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

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
                    elseif ($data[0]) {
                        (new OutiWatchForm())->execute($player);
                    }
                    elseif (!$data[1] or !$data[2]) {
                        $player->sendMessage("§a[システム] プレイヤー名と内容部分を空にすることはできません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                    }
                    else {
                        if (($webhookURL = (string)Main::getInstance()->getPluginConfig()->get("Report_Request_Webhook", "")) !== "") {
                            $webhook = new Webhook($webhookURL);
                            $msg = new Message();
                            $msg->setContent("**レポート**\n\n{$player->getName()}(XUID: {$player->getXuid()})のレポート\n```\nレポート対象のプレイヤー名: $data[1]\nレポート内容: $data[2]```");
                            $webhook->send($msg);
                            $player->sendMessage("§a[システム] レポートを送信しました");
                        }
                        else {
                            $player->sendMessage("§a[システム] レポートを送信できませんでした、レポート先のWebhookURLが設定されていません");
                        }
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("レポートフォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§cレポートするプレイヤー名", "player_name");
            $form->addInput("§d内容", "content");
            $form->addLabel("§e[注意] 嘘のレポートは処罰される可能性があります\n処罰等が決定次第、内部メールで連絡致します");
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}