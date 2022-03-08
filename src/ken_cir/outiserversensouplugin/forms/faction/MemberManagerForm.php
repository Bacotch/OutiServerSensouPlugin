<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use DateTime;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class MemberManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());

            $form = new SimpleForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        (new FactionForm())->execute($player);
                        return;
                    }
                    elseif ($data === 1) {
                        $this->addInvitePlayer($player, $factionData);
                        return;
                    }

                    $inviteMembers = array_map(function (string $xuid) {
                        return PlayerDataManager::getInstance()->getXuid($xuid);
                    }, $factionData->getInvites());
                    $members = array_merge($inviteMembers, PlayerDataManager::getInstance()->getFactionPlayers($factionData->getId()));
                    $this->viewInvitePlayer($player, $factionData, $members[$data - 2]);
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("派閥メンバーの管理");
            $form->addButton("戻る");
            $form->addButton("メンバーを派閥に招待");
            foreach ($factionData->getInvites() as $invite) {
                $form->addButton("§e[招待中] §f" . PlayerDataManager::getInstance()->getXuid($invite)->getName());
            }
            foreach (PlayerDataManager::getInstance()->getFactionPlayers($factionData->getId()) as $factionPlayer) {
                if ($factionData->getOwnerXuid() === $factionPlayer->getXuid()) {
                    $form->addButton("§c[派閥主] §f" . PlayerDataManager::getInstance()->getXuid($factionPlayer->getXuid())->getName());
                }
                else {
                    $form->addButton("§a[メンバー] §f" . PlayerDataManager::getInstance()->getXuid($factionPlayer->getXuid())->getName());
                }
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    public function addInvitePlayer(Player $player, FactionData $factionData)
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->execute($player);
                        return;
                    }
                    elseif (!$data[1]) {
                        $player->sendMessage("§a[システム] 招待するプレイヤー名は入力必須項目です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "addInvitePlayer"], [$player, $factionData]), 20);
                        return;
                    }
                    elseif (!PlayerDataManager::getInstance()->getName($data[1]) or $factionData->hasInvite(PlayerDataManager::getInstance()->getName($data[1])->getXuid()) or PlayerDataManager::getInstance()->getName($data[1])->getFaction() !== -1) {
                        $player->sendMessage("§a[システム] そのプレイヤーは存在しないか、既に招待済みか、既に別の派閥に所属しています");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "addInvitePlayer"], [$player, $factionData]), 20);
                        return;
                    }

                    $invitePlayer = PlayerDataManager::getInstance()->getName($data[1]);
                    $factionData->addInvite($invitePlayer->getXuid());
                    MailDataManager::getInstance()->create($invitePlayer->getXuid(),
                    "派閥に招待されました",
                    "{$player->getName()}があなたを{$factionData->getName()}に招待しました",
                    $player->getXuid(),
                        (new DateTime('now'))->format("Y年m月d日 H時i分") );
                    $player->sendMessage("§a[システム] {$invitePlayer->getName()}を{$factionData->getName()}に招待しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "addInvitePlayer"], [$player, $factionData]), 20);
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("派閥メンバーの管理 派閥にプレイヤーを新規招待");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("招待するプレイヤー名", "invitePlayerName");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    public function viewInvitePlayer(Player $player, FactionData $factionData, PlayerData $factionPlayerData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($factionData, $factionPlayerData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                    elseif ($data === false) {
                        if ($factionPlayerData->getFaction() !== -1) {
                            if ($factionData->getOwnerXuid() === $factionPlayerData->getXuid()) {
                                $player->sendMessage("§a[システム] 派閥主は追放できません");
                                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "viewInvitePlayer"], [$player, $factionData, $factionPlayerData]), 20);
                                return;
                            }

                            $factionPlayerData->setFaction(-1);
                            MailDataManager::getInstance()->create($factionPlayerData->getXuid(),
                            "派閥から追放されました",
                            "あなたは{$player->getName()}から{$factionData->getName()}を追放されました",
                            "システム",
                                (new DateTime('now'))->format("Y年m月d日 H時i分"));
                            $player->sendMessage("§a[システム] {$factionPlayerData->getName()}を派閥から追放しました");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        }
                        else {
                            $factionData->removeInvite($factionPlayerData->getXuid());
                            MailDataManager::getInstance()->create($factionPlayerData->getXuid(),
                                "派閥の招待を取り消されました",
                                "あなたは{$player->getName()}から{$factionData->getName()}の招待を取り消されました",
                                "システム",
                                (new DateTime('now'))->format("Y年m月d日 H時i分"));
                            $player->sendMessage("§a[システム] {$factionPlayerData->getName()}の派閥招待を取り消しました");
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        }
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("派閥メンバーの管理 {$factionPlayerData->getName()}");
            $form->setContent("ステータス: " . ($factionPlayerData->getFaction() !== -1 ? "§aメンバー" : "§c招待中"));
            $form->setButton1("戻る");
            if ($factionPlayerData->getFaction() !== -1) {
                $form->setButton2("派閥から追放");
            }
            else {
                $form->setButton2("招待をキャンセル");
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}