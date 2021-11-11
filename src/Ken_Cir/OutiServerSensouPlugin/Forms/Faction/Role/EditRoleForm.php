<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Role;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\CustomForm;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\SimpleForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleData;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\Player;
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
                    }
                    else {
                        $this->edit($player, $factionRoles[$data - 1]);
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
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
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
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
                    }
                    elseif ($data === 1) {
                        $this->editRole($player, $editRoleData);
                    }
                    elseif ($data === 2) {
                        RoleDataManager::getInstance()->delete($editRoleData->getId());
                        $player->sendMessage("[システム]役職 {$editRoleData->getName()} を削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職編集・削除フォーム");
            $form->addButton("戻る");
            $form->addButton("役職を編集する");
            $form->addButton("役職を削除する");
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
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
                    }
                    elseif (!isset($data[1])) return true;
                    $oldRoleData = $editRoleData;
                    $editRoleData->setName($data[1]);
                    $editRoleData->setColor($data[2]);
                    $editRoleData->setSensenHukoku($data[3]);
                    $editRoleData->setInvitePlayer($data[4]);
                    $editRoleData->setSendmailAllFactionPlayer($data[5]);
                    $editRoleData->setFreandFactionManager($data[6]);
                    $editRoleData->setKickFactionPlayer($data[7]);
                    $editRoleData->setLandManager($data[8]);
                    $editRoleData->setBankManager($data[9]);
                    $editRoleData->setRoleManager($data[10]);
                    $player->sendMessage("[システム]役職 {$oldRoleData->getName()}の設定を編集しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("§d派閥役職編集フォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§a役職名§c", "rolename", $editRoleData->getName());
            $form->addDropdown("§e役職カラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"], $editRoleData->getColor());
            $form->addToggle("宣戦布告権限", $editRoleData->isSensenHukoku());
            $form->addToggle("派閥にプレイヤー招待権限", $editRoleData->isInvitePlayer());
            $form->addToggle("派閥プレイヤー全員に一括でメール送信権限", $editRoleData->isSendmailAllFactionPlayer());
            $form->addToggle("敵対派閥と友好派閥（制限あり）の設定権限", $editRoleData->isFreandFactionManager());
            $form->addToggle("派閥からプレイヤーを追放権限", $editRoleData->isKickFactionPlayer());
            $form->addToggle("派閥の土地管理権限", $editRoleData->isLandManager());
            $form->addToggle("派閥銀行管理権限", $editRoleData->isBankManager());
            $form->addToggle("派閥ロール管理権限", $editRoleData->isRoleManager());
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}