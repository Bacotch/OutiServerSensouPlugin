<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Role;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\CustomForm;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\SimpleForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerData;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleData;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use pocketmine\Player;
use function count;
use function array_values;

class EditMemberRole
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $factionPlayers = array_values(PlayerDataManager::getInstance()->getFactionPlayers($player_data->getFaction()));
            $form = new SimpleForm(function (Player $player, $data) use ($factionPlayers) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new RoleManagerForm();
                        $form->execute($player);
                        return true;
                    }
                    $this->edit_A($player, $factionPlayers[$data - 1]);
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("§b派閥メンバー役職操作フォーム");
            $form->addButton("戻る");
            foreach ($factionPlayers as $factionPlayer) {
                $form->addButton($factionPlayer->getName());
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
        }
    }

    private function edit_A(Player $player, PlayerData $editPlayerData): void
    {
        try {
            $roles = array_values(RoleDataManager::getInstance()->getFactionRoles($editPlayerData->getFaction()));
            $form = new CustomForm(function (Player $player, $data) use ($editPlayerData, $roles) {
                try {
                    if ($data === null) return true;
                    elseif ($data[1] === true) {
                        $this->execute($player);
                        return true;
                    }
                    $msg = "[システム] {$editPlayerData->getName()}から以下の通りに役職付与・剥奪を行いました\n";
                    for ($i = 2; $i < count($data); $i++) {
                        if ($data[$i] === true and !$editPlayerData->hasRole($roles[$i - 2]->getId())) {
                            $editPlayerData->addRole($roles[$i - 2]->getId());
                            $msg .= "{$roles[$i - 2]->getName()}を付与しました\n";
                        }
                        elseif ($data[$i] === false and $editPlayerData->hasRole($roles[$i - 2]->getId())) {
                            $editPlayerData->removeRole($roles[$i - 2]->getId());
                            $msg .= "{$roles[$i - 2]->getName()}を剥奪しました\n";
                        }
                    }

                    $player->sendMessage($msg);
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("派閥メンバー役職操作フォーム {$editPlayerData->getName()}");
            $form->addLabel("役職を操作するメンバー名: {$editPlayerData->getName()}");
            $form->addToggle("キャンセルして戻る");
            $editPlayerRoles = $editPlayerData->getRoles();
            foreach ($roles as $role) {
                if (in_array($role->getId(), $editPlayerRoles, true)) {
                    $form->addToggle($role->getName(), true);
                }
                else {
                    $form->addToggle($role->getName());
                }
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
        }
    }
}