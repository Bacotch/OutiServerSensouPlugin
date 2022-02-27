<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use function join;

class PlayerRoleDatabaseForm
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

                    $this->viewPlayerRolesData($player, PlayerDataManager::getInstance()->getAll(true)[$data - 1]);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤー役職管理");
            $form->addButton("戻る");
            foreach (PlayerDataManager::getInstance()->getAll() as $playerData) {
                if ($playerData->getFaction() === -1) continue;
                $form->addButton("[" . FactionDataManager::getInstance()->get($playerData->getFaction())->getName() . "派閥] {$playerData->getName()} ");
            }
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function viewPlayerRolesData(Player $player, PlayerData $playerData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($playerData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                    elseif ($data === false) {
                        $this->editPlayerRolesData($player, $playerData);
                    }
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $playerRoles = array_map(function (int $id) {
                $roleData = RoleDataManager::getInstance()->get($id);
                return $roleData->getName();
            }, $playerData->getRoles(true));
            $form->setTitle("プレイヤー役職管理 {$playerData->getName()}");
            $form->setContent("{$playerData->getName()}(XUID: {$playerData->getXuid()})の所持役職:\n" . join("\n", $playerRoles));
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function editPlayerRolesData(Player $player, PlayerData $playerData): void
    {
        try {
            $factionRoles = RoleDataManager::getInstance()->getFactionRoles($playerData->getFaction(), true);

            $form = new CustomForm(function (Player $player, $data) use ($factionRoles, $playerData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewPlayerRolesData($player, $playerData);
                        return;
                    }

                    for ($i = 1; $i < count($data); $i++) {
                        if ($data[$i]) {
                            $playerData->addRole($factionRoles[$i]->getId());
                        }
                        else {
                            $playerData->removeRole($factionRoles[$i]->getId());
                        }
                    }

                    $player->sendMessage("§a[システム] {$playerData->getName()}の役職を変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "viewPlayerRolesData"], [$player, $playerData]), 20);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤー役職管理 {$playerData->getName()}");
            $form->addToggle("キャンセルして戻る");
            foreach ($factionRoles as $factionRole) {
                if (in_array($factionRole->getId(), $playerData->getRoles(), true)) {
                    $form->addToggle("{$factionRole->getName()}", true);
                }
                else {
                    $form->addToggle("{$factionRole->getName()}");
                }
            }
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}