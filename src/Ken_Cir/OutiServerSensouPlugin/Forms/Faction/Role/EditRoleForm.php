<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Role;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleData;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\SimpleForm;
use function array_values;

class EditRoleForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
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
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職編集・削除フォーム");
            $form->setContent("編集・削除する役職を選択してください");
            $form->addButton("戻る");
            foreach ($factionRoles as $factionRole) {
                $form->addButton(OutiServerPluginUtils::getChatColor($factionRole->getColor()) . $factionRole->getName());
            }
            $player->sendForm($form);
        } catch (Error|Exception $e) {
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
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職編集・削除フォーム");
            $form->addButton("戻る");
            $form->addButton("役職を編集する");
            $form->addButton("役職を削除する");
            $player->sendForm($form);
        } catch (Error|Exception $e) {
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
                    } elseif (!isset($data[1])) return true;

                    $oldRolePos = $editRoleData->getPosition();
                    $editRoleData->setName($data[1]);
                    $editRoleData->setColor($data[2]);
                    $editRoleData->setPosition((int)$data[3]);
                    $editRoleData->setSensenHukoku($data[4]);
                    $editRoleData->setInvitePlayer($data[5]);
                    $editRoleData->setSendmailAllFactionPlayer($data[6]);
                    $editRoleData->setFreandFactionManager($data[7]);
                    $editRoleData->setKickFactionPlayer($data[8]);
                    $editRoleData->setLandManager($data[9]);
                    $editRoleData->setBankManager($data[10]);
                    $editRoleData->setRoleManager($data[11]);
                    if ($oldRolePos !== (int)$data[3]) {
                        foreach (RoleDataManager::getInstance()->getFactionRoles($editRoleData->getFactionId()) as $factionRole) {
                            // 下がる式
                            if ($oldRolePos <= (int)$data[3] and $factionRole->getPosition() <= (int)$data[3] and $factionRole->getId() !== $editRoleData->getId()) {
                                $factionRole->setPosition($factionRole->getPosition() - 1);
                            } // 上がる式
                            elseif ($oldRolePos >= (int)$data[3] and $factionRole->getPosition() <= $oldRolePos and $factionRole->getId() !== $editRoleData->getId()) {
                                $factionRole->setPosition($factionRole->getPosition() + 1);
                            }
                        }
                    }

                    $player->sendMessage("[システム]役職 {$editRoleData->getName()}の設定を編集しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                } catch (Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("§d派閥役職編集フォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§a役職名§c", "rolename", $editRoleData->getName());
            $form->addDropdown("§e役職カラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"], $editRoleData->getColor());
            $form->addSlider("役職位置", 1, count(RoleDataManager::getInstance()->getFactionRoles($editRoleData->getFactionId())), $editRoleData->getPosition());
            $form->addToggle("宣戦布告権限", $editRoleData->isSensenHukoku());
            $form->addToggle("派閥にプレイヤー招待権限", $editRoleData->isInvitePlayer());
            $form->addToggle("派閥プレイヤー全員に一括でメール送信権限", $editRoleData->isSendmailAllFactionPlayer());
            $form->addToggle("敵対派閥と友好派閥（制限あり）の設定権限", $editRoleData->isFreandFactionManager());
            $form->addToggle("派閥からプレイヤーを追放権限", $editRoleData->isKickFactionPlayer());
            $form->addToggle("派閥の土地管理権限", $editRoleData->isLandManager());
            $form->addToggle("派閥銀行管理権限", $editRoleData->isBankManager());
            $form->addToggle("派閥ロール管理権限", $editRoleData->isRoleManager());
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }
}
