<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\mail;

use DateTime;
use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use pocketmine\Server;


/**
 * メール作成フォーム
 */
class CreateMailForm
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
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());

            $form = new CustomForm(function (Player $player, $data) use ($playerData) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0]) {
                        (new MailForm())->execute($player);
                    }
                    elseif (!$data[1] or !$data[2] or !$data[3]) {
                        $player->sendMessage("§a[システム] メールタイトルと内容と送信相手部分を空にすることはできません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                    } else {
                        $author = $player->getXuid();
                        $time = new DateTime('now');

                        // 送信者名義を「運営」に
                        if (($playerData->isSendmailAllFactionPlayer() and $data[6]) or (Server::getInstance()->isOp($player->getName()) and $data[5])) {
                            $author = "運営";
                        }

                        // 権限餅
                        if ($playerData->isSendmailAllFactionPlayer() and $data[4]) {
                            foreach (PlayerDataManager::getInstance()->getFactionPlayers($playerData->getFaction()) as $factionPlayer) {
                                MailDataManager::getInstance()->create($factionPlayer->getXuid(), $data[1], $data[2], $author, $time->format("Y年m月d日 H時i分"));
                            }
                        }

                        if (($playerData->isSendmailAllFactionPlayer() and $data[5]) or (!$playerData->isSendmailAllFactionPlayer() and $data[4])) {
                            foreach (PlayerDataManager::getInstance()->getAll() as $playerData) {
                                MailDataManager::getInstance()->create($playerData->getXuid(), $data[1], $data[2], $author, $time->format("Y年m月d日 H時i分"));
                            }
                            $player->sendMessage("§a[システム] プレイヤー全員にメールを送信しました");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                            return true;
                        }

                        if (!$sendToPlayerData = PlayerDataManager::getInstance()->getName($data[3])) {
                            $player->sendMessage("§a[システム] プレイヤーが見つかりませんでした");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                            return true;
                        }

                        MailDataManager::getInstance()->create($sendToPlayerData->getXuid(), $data[1], $data[2], $author, $time->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] プレイヤー $data[3] にメールを送信しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("§aメール送信フォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§cメールタイトル", "title", "");
            $form->addInput("§d内容", "content", "");
            $form->addInput("§6送信相手", "send_to", "");
            // メール
            if ($playerData->isSendmailAllFactionPlayer()) {
                $form->addToggle("[権限餅専用] 派閥メンバー全員にメールを送信する");
            }
            if (Main::getInstance()->getServer()->isOp($player->getName())) {
                $form->addToggle("§3[運営専用] プレイヤー全員にメールを送信する");
                $form->addToggle("§3[運営専用] 送信者名義を「運営」にして送信する");
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
