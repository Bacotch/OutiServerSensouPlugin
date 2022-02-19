<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleData;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use ken_cir\outiserversensouplugin\utilitys\OutiServerUtilitys;
use pocketmine\player\Player;
use function count;
use function array_filter;

class RoleDatabaseForm
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

                    $factionDatas = array_filter(FactionDataManager::getInstance()->getAll(true), function (FactionData $factionData) {
                        return count(RoleDataManager::getInstance()->getFactionRoles($factionData->getId())) > 0;
                    });
                    $this->selectRoleData($player, $factionDatas[$data - 1]);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("役職データ 管理");
            $form->addButton("戻る");
            foreach (FactionDataManager::getInstance()->getAll() as $factionData) {
                // 役職が1つもない派閥は飛ばします
                if (count(RoleDataManager::getInstance()->getFactionRoles($factionData->getId())) < 1) continue;
                $form->addButton($factionData->getName());
            }
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function selectRoleData(Player $player, FactionData $factionData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        $this->execute($player);
                        return;
                    }

                    $this->viewRoleData($player, $factionData, RoleDataManager::getInstance()->getFactionRoles($factionData->getId(), true)[$data]);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("役職データ {$factionData->getName()}派閥");
            $form->addButton("戻る");
            foreach (RoleDataManager::getInstance()->getFactionRoles($factionData->getId(), true) as $roleData) {
                $form->addButton(OutiServerUtilitys::getChatColor($roleData->getColor()) . "{$roleData->getName()} #{$roleData->getId()}");
            }
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewRoleData(Player $player, FactionData $factionData, RoleData $roleData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($roleData, $factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->selectRoleData($player, $factionData);
                    }
                    elseif ($data === false) {
                        $this->editRoleData($player, $factionData, $roleData);
                    }
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("役職データ {$roleData->getName()} #{$roleData->getId()} {$factionData->getName()}派閥");
            $form->setContent("役職ID: {$roleData->getId()}\n派閥: {$factionData->getName()}(ID: {$factionData->getId()})\n役職名: {$roleData->getName()}\n役職カラー: " . OutiServerUtilitys::getChatColor($roleData->getColor()) . OutiServerUtilitys::getChatString($roleData->getColor()) .  "\n§f役職位置: {$roleData->getPosition()}\n\n宣戦布告権限: " . ($roleData->isSensenHukoku() ? '§bある' : '§cない') . "\n\n§f派閥にプレイヤー招待権限: " . ($roleData->isInvitePlayer() ? '§bある' : '§cない') . "\n\n§f派閥プレイヤー全員に一括でメール送信権限: " . ($roleData->isSendmailAllFactionPlayer() ? '§bある' : '§cない') . "\n\n§f敵対派閥と友好派閥（制限あり）の設定権限: " . ($roleData->isSendmailAllFactionPlayer() ? '§bある' : '§cない') . "\n\n§f派閥からプレイヤーを追放権限: " . ($roleData->isKickFactionPlayer() ? '§bある' : '§cない') . "\n\n§f派閥の土地管理権限: " . ($roleData->isLandManager() ? '§bある' : '§cない') . "\n\n§f派閥銀行管理権限: " . ($roleData->isBankManager() ? '§bある' : '§cない') . "\n\n§f派閥ロール管理権限: " . ($roleData->isRoleManager() ? '§bある' : '§cない'));
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function editRoleData(Player $player, FactionData $factionData, RoleData $roleData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($factionData, $roleData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewRoleData($player, $factionData, $roleData);
                        return;
                    }
                    elseif ($data[1]) {
                        RoleDataManager::getInstance()->delete($roleData->getId());
                        $player->sendMessage("[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "selectRoleData"], [$player, $factionData]), 20);
                        return;
                    }
                    elseif (!$data[4]) {
                        $player->sendMessage("[システム] 役職名は入力必須項目です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editRoleData"], [$player, $factionData]), 20);
                        return;
                    }

                    $position = (int)$data[6];
                    $oldRoleFaction = $roleData->getFactionId();
                    $oldRolePos = $roleData->getPosition();
                    $roleData->setFactionId(FactionDataManager::getInstance()->getAll(true)[$data[3]]->getId());
                    $roleData->setName($data[4]);
                    $roleData->setColor($data[5]);
                    $roleData->setPosition($oldRoleFaction !== $roleData->getFactionId() ? count(RoleDataManager::getInstance()->getFactionRoles($roleData->getFactionId())) + 1 : $data[6]);
                    $roleData->setSensenHukoku($data[7]);
                    $roleData->setInvitePlayer($data[8]);
                    $roleData->setSendmailAllFactionPlayer($data[9]);
                    $roleData->setFreandFactionManager($data[10]);
                    $roleData->setKickFactionPlayer($data[11]);
                    $roleData->setLandManager($data[12]);
                    $roleData->setBankManager($data[13]);
                    $roleData->setRoleManager($data[14]);

                    if ($oldRolePos !== $position and $oldRoleFaction === $roleData->getFactionId()) {
                        foreach (RoleDataManager::getInstance()->getFactionRoles($roleData->getFactionId(), false) as $factionRole) {
                            // 下がる式
                            if ($oldRolePos <= $position and $factionRole->getPosition() <= $position and $factionRole->getId() !== $roleData->getId()) {
                                $factionRole->setPosition($factionRole->getPosition() - 1);
                            } // 上がる式
                            elseif ($oldRolePos >= $position and $factionRole->getPosition() <= $oldRolePos and $factionRole->getId() !== $roleData->getId()) {
                                if ($factionRole->getPosition() < 1) {
                                    $factionRole->setPosition($factionRole->getPosition() + 2);
                                } else {
                                    $factionRole->setPosition($factionRole->getPosition() + 1);
                                }
                            }
                        }
                    }


                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $factionDefault = 0;
            $factionDatas = FactionDataManager::getInstance()->getAll(true);
            foreach ($factionDatas as $key => $roleFactionData) {
                if ($roleFactionData->getId() === $roleData->getId()) {
                    $factionDefault = $key + 1;
                    break;
                }
            }
            $factionDatas = array_map(function (FactionData $factionData) {
                return $factionData->getName();
            }, FactionDataManager::getInstance()->getAll(true));

            $form->setTitle("役職データ {$roleData->getName()} #{$roleData->getId()} 編集 {$factionData->getName()}派閥");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addLabel("派閥の項目を変更すると、位置変更は無視されます(一番下に自動的に移動するため)");
            $form->addDropdown("派閥", $factionDatas, $factionDefault);
            $form->addInput("役職名", "roleName", $roleData->getName());
            $form->addDropdown("役職カラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"], $roleData->getColor());
            $form->addSlider("役職位置", 1, count(RoleDataManager::getInstance()->getFactionRoles($factionData->getId())), $roleData->getPosition());
            $form->addToggle("宣戦布告権限", $roleData->isSensenHukoku());
            $form->addToggle("派閥にプレイヤー招待権限", $roleData->isInvitePlayer());
            $form->addToggle("派閥プレイヤー全員に一括でメール送信権限", $roleData->isSendmailAllFactionPlayer());
            $form->addToggle("敵対派閥と友好派閥（制限あり）の設定権限", $roleData->isFreandFactionManager());
            $form->addToggle("派閥からプレイヤーを追放権限", $roleData->isKickFactionPlayer());
            $form->addToggle("派閥の土地管理権限", $roleData->isLandManager());
            $form->addToggle("派閥銀行管理権限", $roleData->isBankManager());
            $form->addToggle("派閥ロール管理権限", $roleData->isRoleManager());
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}