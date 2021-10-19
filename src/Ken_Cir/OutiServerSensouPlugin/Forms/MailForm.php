<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\ModalForm;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\SimpleForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\MailData\MailData;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerData;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;

use pocketmine\Player;

final class MailForm
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
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $mails = [];
            foreach ($player_data->getMailManager()->getAll() as $mail) {
                array_unshift($mails, $mail);
            }
            $form = new SimpleForm(function (Player $player, $data) use ($player_data) {
                try {
                    if ($data === null) return true;
                    $count = 0;
                    foreach ($player_data->getMailManager()->getAll() as $mail) {
                        if ($count === $data) {
                            $this->info($player, $mail, $player_data);
                            break;
                        }

                        $count++;
                    }
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e);
                }

                return true;
            });

            $form->setTitle("§bメールフォーム");
            foreach ($player_data->getMailManager()->getAll() as $mail) {
                if ($mail->isRead()) {
                    $form->addButton("§0[{$mail->getDate()}] {$mail->getTitle()}");
                }
                else {
                    $form->addButton("§dNEW §0[{$mail->getDate()}] {$mail->getTitle()}");
                }
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e);
        }
    }

    /**
     * @param Player $player
     * @param MailData $mailData
     */
    private function info(Player $player, MailData $mailData, PlayerData $playerData)
    {
        try {
            $form = new ModalForm(function(Player $player, $data) use ($playerData, $mailData) {
                if ($data === true) {
                    $playerData->getMailManager()->delete($mailData->getTitle());
                }
                else {
                    $mailData->setRead(true);
                }
                $playerData->save();
            });

            $form->setTitle("メール {$mailData->getTitle()}");
            $form->setContent("§6件名: {$mailData->getTitle()}\n§b送信者: {$mailData->getAuthor()}\n§eメール送信時刻: {$mailData->getDate()}\n\n{$mailData->getContent()}");
            $form->setButton1("§c削除");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}