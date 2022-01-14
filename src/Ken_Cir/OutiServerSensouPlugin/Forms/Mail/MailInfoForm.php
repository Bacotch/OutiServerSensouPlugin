<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Mail;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailData;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use pocketmine\player\Player;
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;
use function current;
use function array_slice;

/**
 * メール閲覧フォーム
 */
final class MailInfoForm
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
            $mail_data = MailDataManager::getInstance()->getPlayerName($player->getName());
            $form = new SimpleForm(function (Player $player, $data) use ($mail_data) {
                try {
                    if ($data === null) return true;
                    $this->info($player, current(array_slice($mail_data, $data, $data + 1)));
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("メールフォーム");
            foreach ($mail_data as $mail) {
                if ($mail->isRead()) {
                    $form->addButton("§0[{$mail->getDate()}] {$mail->getTitle()}");
                } else {
                    $form->addButton("§dNEW §0[{$mail->getDate()}] {$mail->getTitle()}");
                }
            }
            $player->sendForm($form);
        } catch (Error | Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    /**
     * @param Player $player
     * @param MailData $mailData
     */
    private function info(Player $player, MailData $mailData)
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($mailData) {
                try {
                    if ($data === true) {
                        MailDataManager::getInstance()->delete($mailData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                    }
                    else {
                        $mailData->setRead(true);
                        $this->execute($player);
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("メール {$mailData->getTitle()}");
            $form->setContent("§6件名: {$mailData->getTitle()}\n§b送信者: {$mailData->getAuthor()}\n§eメール送信時刻: {$mailData->getDate()}\n\n{$mailData->getContent()}");
            $form->setButton1("§c削除");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
