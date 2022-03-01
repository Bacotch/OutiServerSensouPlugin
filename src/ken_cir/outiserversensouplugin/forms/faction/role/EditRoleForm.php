<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\role;


use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleData;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use ken_cir\outiserversensouplugin\utilitys\OutiServerUtilitys;
use pocketmine\player\Player;
use function array_values;
use function count;
use function is_numeric;

class EditRoleForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionRoles = array_values(RoleDataManager::getInstance()->getFactionRoles($playerData->getFaction()));
            $form = new SimpleForm(function (Player $player, $data) use ($factionRoles) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new RoleManagerForm();
                        $form->execute($player);
                    } else {
                        $this->edit($player, $factionRoles[$data - 1]);
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職編集・削除フォーム");
            $form->setContent("編集・削除する役職を選択してください");
            $form->addButton("戻る");
            foreach ($factionRoles as $factionRole) {
                $form->addButton(OutiServerUtilitys::getChatColor($factionRole->getColor()) . $factionRole->getName());
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }

    private function edit(Player $player, RoleData $editRoleData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($editRoleData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->execute($player);
                    } elseif ($data === 1) {
                        $this->editRole($player, $editRoleData);
                    } elseif ($data === 2) {
                        RoleDataManager::getInstance()->delete($editRoleData->getId());
                        $player->sendMessage("[システム]役職 {$editRoleData->getName()} を削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職編集・削除フォーム");
            $form->addButton("戻る");
            $form->addButton("役職を編集する");
            $form->addButton("役職を削除する");
            $player->sendForm($form);
        } catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }

    private function editRole(Player $player, RoleData $editRoleData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($editRoleData) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $this->execute($player);
                        return true;
                    } elseif (!isset($data[1], $data[3]) or !is_numeric($data[3])) return true;

                    $position = (int)$data[3];
                    if ($position < 1) $position = 1;
                    elseif ($position > count(RoleDataManager::getInstance()->getFactionRoles($editRoleData->getFactionId()))) $position = count(RoleDataManager::getInstance()->getFactionRoles($editRoleData->getFactionId()));
                    $oldRolePos = $editRoleData->getPosition();
                    $editRoleData->setName($data[1]);
                    $editRoleData->setColor($data[2]);
                    $editRoleData->setPosition($position);
                    $editRoleData->setSensenHukoku($data[4]);
                    $editRoleData->setInvitePlayer($data[5]);
                    $editRoleData->setSendmailAllFactionPlayer($data[6]);
                    $editRoleData->setFreandFactionManager($data[7]);
                    $editRoleData->setKickFactionPlayer($data[8]);
                    $editRoleData->setLandManager($data[9]);
                    $editRoleData->setBankManager($data[10]);
                    $editRoleData->setRoleManager($data[11]);
                    if ($oldRolePos !== $position) {
                        foreach (RoleDataManager::getInstance()->getFactionRoles($editRoleData->getFactionId(), false) as $factionRole) {
                            // 下がる式
                            if ($oldRolePos <= $position and $factionRole->getPosition() <= $position and $factionRole->getId() !== $editRoleData->getId()) {
                                $factionRole->setPosition($factionRole->getPosition() - 1);
                            } // 上がる式
                            elseif ($oldRolePos >= $position and $factionRole->getPosition() <= $oldRolePos and $factionRole->getId() !== $editRoleData->getId()) {
                                if ($factionRole->getPosition() < 1) {
                                    $factionRole->setPosition($factionRole->getPosition() + 2);
                                } else {
                                    $factionRole->setPosition($factionRole->getPosition() + 1);
                                }
                            }
                        }
                    }

                    $player->sendMessage("[システム]役職 {$editRoleData->getName()}の設定を編集しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("§d派閥役職編集フォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§a役職名§c", "rolename", $editRoleData->getName());
            $form->addDropdown("§e役職カラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"], $editRoleData->getColor());
            $form->addInput("役職位置 1から" . count(RoleDataManager::getInstance()->getFactionRoles($editRoleData->getFactionId())) . "まで", "position", (string)$editRoleData->getPosition());
            $form->addToggle("宣戦布告権限", $editRoleData->isSensenHukoku());
            $form->addToggle("派閥にプレイヤー招待権限", $editRoleData->isInvitePlayer());
            $form->addToggle("派閥プレイヤー全員に一括でメール送信権限", $editRoleData->isSendmailAllFactionPlayer());
            $form->addToggle("敵対派閥と友好派閥（制限あり）の設定権限", $editRoleData->isFreandFactionManager());
            $form->addToggle("派閥からプレイヤーを追放権限", $editRoleData->isKickFactionPlayer());
            $form->addToggle("派閥の土地管理権限", $editRoleData->isLandManager());
            $form->addToggle("派閥銀行管理権限", $editRoleData->isBankManager());
            $form->addToggle("派閥ロール管理権限", $editRoleData->isRoleManager());
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }
}
