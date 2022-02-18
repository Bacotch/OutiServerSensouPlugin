<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use DateTime;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use ken_cir\outiserversensouplugin\utilitys\OutiServerUtilitys;
use ken_cir\pmmpoutiserverbot\PMMPOutiServerBot;
use pocketmine\player\Player;
use pocketmine\Server;
use function is_numeric;

class FactionDatabaseForm
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
                        (new DatabaseManagerForm())->execute($player);
                        return;
                    }

                    $factionData = FactionDataManager::getInstance()->getAll(true)[$data - 1];
                    $this->viewFactionData($player, $factionData);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("派閥データ管理");
            $form->addButton("キャンセルして戻る");
            foreach (FactionDataManager::getInstance()->getAll() as $factionData) {
                $form->addButton("#{$factionData->getId()} {$factionData->getName()}");
            }
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewFactionData(Player $player, FactionData $factionData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                    elseif ($data === false) {
                        $this->editFactionData($player, $factionData);
                    }
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("派閥データ ID{$factionData->getId()}");
            $form->setContent("派閥ID: {$factionData->getId()}\n派閥名: {$factionData->getName()}\n派閥所有者: " . PlayerDataManager::getInstance()->getXuid($factionData->getOwnerXuid())->getName() . "(XUID: {$factionData->getOwnerXuid()})\n派閥カラー: " . OutiServerUtilitys::getChatString($factionData->getColor()) .  "\n派閥所持金: {$factionData->getMoney()}");
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function editFactionData(Player $player, FactionData $factionData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($factionData) {
                try {
                    $factionMembers = PlayerDataManager::getInstance()->getFactionPlayers($factionData->getId());

                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewFactionData($player, $factionData);
                    }
                    elseif ($data[1]) {
                        $factionName = $factionData->getName();
                        foreach ($factionMembers as $factionMember) {
                            $time = new DateTime('now');
                            MailDataManager::getInstance()->create(
                                $factionMember->getXuid(),
                                "派閥崩壊通知",
                                "所属派閥 {$factionData->getName()} が {$time->format("Y年m月d日 H時i分")} に崩壊しました",
                                "システム",
                                $time->format("Y年m月d日 H時i分")
                            );
                            $factionMember->setFaction(-1);
                        }
                        LandDataManager::getInstance()->deleteFaction($factionData->getId());
                        FactionDataManager::getInstance()->delete($factionData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                        Server::getInstance()->broadcastMessage("§a[システム] 派閥 $factionName が崩壊しました");
                        PMMPOutiServerBot::getInstance()->getDiscordBotThread()->sendChatMessage("[システム] 派閥 $factionName が崩壊しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    }
                    elseif (!($data[2] and isset($data[5])) or !is_numeric($data[5])) {
                        $player->sendMessage("§a[システム] 派閥名と派閥所持金は入力必須項目で、派閥所持金は数値入力である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editFactionData"], [$player, $factionData]), 20);
                        return;
                    }

                    $factionData->setName($data[2]);
                    $factionData->setOwnerXuid($factionMembers[$data[3]]->getXuid());
                    $factionData->setColor($data[4]);
                    $factionData->setMoney((int)$data[5]);

                    $player->sendMessage("§a[システム]　変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $factionOwnerDefault = 0;
            $factionMembers = PlayerDataManager::getInstance()->getFactionPlayers($factionData->getId());
            foreach ($factionMembers as $key => $factionMember) {
                if ($factionMember->getXuid() === $factionData->getOwnerXuid()) {
                    $factionOwnerDefault = $key;
                    break;
                }
            }
            $factionMembers = array_map(function (PlayerData $playerData) {
                return $playerData->getName();
            }, $factionMembers);

            $form->setTitle("派閥データ ID{$factionData->getId()} 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addInput("派閥名","factionName", $factionData->getName());
            $form->addDropdown("派閥所有者", $factionMembers, $factionOwnerDefault);
            $form->addDropdown("派閥カラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"], $factionData->getColor());
            $form->addInput("派閥所持金", "factionMoney", (string)$factionData->getMoney());
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}