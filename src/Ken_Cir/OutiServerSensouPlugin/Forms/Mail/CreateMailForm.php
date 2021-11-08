<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Mail;

use DateTime;
use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\CustomForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use pocketmine\Player;

/**
 * メール作成フォーム
 */
final class CreateMailForm
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
                    elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2])) return true;
                    $author = $player->getName();
                    $time = new DateTime('now');
                    // 送信者名義を「運営」に
                    if ($data[4]) {
                        $author = "運営";
                    }
                    // 全員に送信する
                    if ($data[3]) {
                        foreach (PlayerDataManager::getInstance()->getPlayerDatas() as $playerData) {
                            MailManager::getInstance()->create($playerData->getName(), $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                        }
                        $player->sendMessage("§a[システム] プレイヤー全員にメールを送信しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return true;
                    }

                    MailManager::getInstance()->create($data[2], $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                    $player->sendMessage("§a[システム] プレイヤー $data[2] にメールを送信しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e);
                }

                return true;
            });

            $form->setTitle("§aメール送信フォーム");
            $form->addInput("§cメールタイトル", "title", "");
            $form->addInput("§d内容", "content", "");
            $form->addInput("§6送信相手", "send_to", "");
            if ($player->isOp()) {
                $form->addToggle("§3[運営専用] プレイヤーにメールを送信する");
                $form->addToggle("§3[運営専用] 送信者名義を「運営」にして送信する");
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}