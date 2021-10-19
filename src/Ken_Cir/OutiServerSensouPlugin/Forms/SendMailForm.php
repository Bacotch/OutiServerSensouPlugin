<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use DateTime;
use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\CustomForm;

use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use pocketmine\Player;

final class SendMailForm
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
                       // $this->plugin->database->addAllPlayerMail( $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] プレイヤー全員にメールを送信しました");
                        return true;
                    }

                    $player_data = PlayerDataManager::getInstance()->get($data[2]);
                    if (!$player_data) {
                        $player->sendMessage("§a[システム] §c$data[2] のプレイヤーデータが見つかりません");
                        return true;
                    }
                    $player_data->getMailManager()->create($data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                    $player_data->save();
                    $player->sendMessage("§a[システム] プレイヤー $data[2] にメールを送信しました");
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
        } catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}