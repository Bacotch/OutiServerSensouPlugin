<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Form;

use DateTime;
use Error;
use Exception;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;

final class SendMailForm extends FormBase
{
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
                    if (isset($data[4]) and $data[4]) {
                        $author = "運営";
                    }
                    // 全員に送信する
                    if (isset($data[3]) and $data[3]) {
                        $this->plugin->database->addAllPlayerMail( $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] プレイヤー全員にメールを送信しました");
                        return true;
                    }

                    $this->plugin->database->addPlayerMail($data[2], $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                    $player->sendMessage("§a[システム] プレイヤー $data[2] にメールを送信しました");
                    return true;
                } catch (Error | Exception $e) {
                    $this->plugin->logger->error($e, $player);
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
            $this->plugin->logger->error($error, $player);
        }
    }
}