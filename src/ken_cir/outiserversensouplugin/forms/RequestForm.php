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
class RequestForm
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
                    elseif (!$data[1]) {
                        $player->sendMessage("§a[システム] 要望部分を空にすることはできません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                    } else {
                        if (($webhookURL = (string)Main::getInstance()->getPluginConfig()->get("Report_Request_Webhook", "")) !== "") {
                            $webhook = new Webhook($webhookURL);
                            $msg = new Message();
                            $msg->setContent("**要望**\n\n{$player->getName()}(XUID: {$player->getXuid()})の要望\n```\n要望内容: $data[1]```");
                            $webhook->send($msg);
                            $player->sendMessage("§a[システム] 要望を送信しました");
                        }
                        else {
                            $player->sendMessage("§a[システム] 要望を送信できませんでした、要望先のWebhookURLが設定されていません");
                        }
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("要望フォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§d内容", "content");
            $form->addLabel("§e要望内容に対する返信は内部メールで行います");
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}