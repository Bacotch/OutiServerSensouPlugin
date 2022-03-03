<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\mail;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\maildata\MailData;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class EditMailForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        (new MailForm())->execute($player);
                        return;
                    }

                    $this->viewMailData($player, MailDataManager::getInstance()->getPlayerAuthorXuid($player->getXuid(), true)[$data - 1]);
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("送信済みメールの編集・削除");
            $form->addButton("戻る");
            foreach (MailDataManager::getInstance()->getPlayerAuthorXuid($player->getXuid()) as $mailData) {
                $form->addButton("{$mailData->getTitle()} To " . PlayerDataManager::getInstance()->getXuid($mailData->getSendtoXuid())->getName());
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    private function viewMailData(Player $player, MailData $mailData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($mailData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                    elseif ($data === false) {
                        $this->editMailData($player, $mailData);
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("送信済みメールの編集・削除 {$mailData->getTitle()}");
            $form->setContent("送信相手: " . PlayerDataManager::getInstance()->getXuid($mailData->getSendtoXuid())->getName() . "\nメールタイトル: {$mailData->getTitle()}\nメール内容: {$mailData->getContent()}\n送信時刻: {$mailData->getDate()}\n送信者: " . PlayerDataManager::getInstance()->getXuid($mailData->getAuthorXuid())->getName() . "\n未読か既読か: " . ($mailData->isRead() ? "既読" : "未読"));
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    public function editMailData(Player $player, MailData $mailData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($mailData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewMailData($player, $mailData);
                        return;
                    }
                    elseif ($data[1]) {
                        MailDataManager::getInstance()->delete($mailData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    }
                    elseif (!$data[2] or !$data[3]) {
                        $player->sendMessage("§a[システム] メールタイトルとメール内容は入力必須項目です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editMailData"], [$player, $mailData]), 20);
                        return;
                    }

                    $mailData->setTitle($data[2]);
                    $mailData->setContent($data[3]);
                    $mailData->setRead(false);
                    $player->sendMessage("§a[システム] 変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("送信済みメールの編集・削除 {$mailData->getTitle()} 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addInput("メールタイトル", "mailTitle", $mailData->getTitle());
            $form->addInput("メール内容", "mailContent", $mailData->getContent());
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}