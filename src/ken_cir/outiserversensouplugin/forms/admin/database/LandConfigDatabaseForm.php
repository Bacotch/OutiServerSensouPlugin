<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigData;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landconfigdata\perms\MemberLandPerms;
use ken_cir\outiserversensouplugin\database\landconfigdata\perms\RoleLandPerms;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleData;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class LandConfigDatabaseForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        PlayerCacheManager::getInstance()->getXuid($player->getXuid())->resetLandConfigDatabase();
                        $form = new DatabaseManagerForm();
                        $form->execute($player);
                    } elseif ($data === 1 and PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getLandConfigDatabaseWorldName() === null and $landConfigData === null) {
                        PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setLandConfigDatabaseWorldName($player->getWorld()->getFolderName());
                        PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setLandConfigDatabaseStartX($player->getPosition()->getFloorX());
                        PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setLandConfigDatabaseStartZ($player->getPosition()->getFloorZ());
                        $player->sendMessage("§a[システム] 開始X座標を{$player->getPosition()->getFloorX()}\n開始Z座標を{$player->getPosition()->getFloorZ()}に設定しました");
                    } elseif ($data === 1 and $landConfigData !== null) {
                        $this->checkLandConfig($player, $landConfigData);
                    } elseif ($data === 1) {
                        if (PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getLandConfigDatabaseWorldName() !== $player->getWorld()->getFolderName()) {
                            $player->sendMessage("§a[システム] 開始座標ワールドと現在いるワールドが違います");
                        } else {
                            $landData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                            $startX = PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getLandConfigDatabaseStartX();
                            $endX = $player->getPosition()->getFloorX();
                            $startZ = PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getLandConfigDatabaseStartZ();
                            $endZ = $player->getPosition()->getFloorZ();
                            if ($startX > $endX) {
                                $backup = $startX;
                                $startX = $endX;
                                $endX = $backup;
                            }
                            if ($startZ > $endZ) {
                                $backup = $startZ;
                                $startZ = $endZ;
                                $endZ = $backup;
                            }

                            $landConfigData = LandConfigDataManager::getInstance()->create(
                                $landData->getId(),
                                $startX,
                                $startZ,
                                $endX,
                                $endZ,
                                array(
                                    "entry" => true,
                                    "blockTap_Place" => true,
                                    "blockBreak" => true,
                                ),
                                array(),
                                array()
                            );
                            $this->checkLandConfig($player, $landConfigData);
                        }
                    } elseif ($data === 2 and $landConfigData === null and PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getLandConfigDatabaseWorldName() !== null) {
                        PlayerCacheManager::getInstance()->getXuid($player->getXuid())->resetLandConfig();
                        $player->sendMessage("§a[システム] 開始座標をリセットしました");
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            // 0
            $form->addButton("キャンセルして戻る");
            if (PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getLandConfigDatabaseWorldName() === null and $landConfigData === null) {
                // 1
                $form->addButton("開始座標の設定");
            } elseif ($landConfigData !== null) {
                // 1
                $form->addButton("現在立っている土地の詳細設定");
            } else {
                // 1
                $form->addButton("終了座標の設定");
                // 2
                $form->addButton("開始座標リセット");
            }

            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    public function checkLandConfig(Player $player, LandConfigData $landConfigData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->execute($player);
                    } elseif ($data === 1 and $landConfigData !== null) {
                        LandConfigDataManager::getInstance()->delete($landConfigData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                    } elseif (($data === 2 and $landConfigData !== null) or ($data === 1 and $landConfigData === null)) {
                        $this->editDefaultPerms($player, $landConfigData);
                    } elseif (($data === 3 and $landConfigData !== null) or ($data === 2 and $landConfigData === null)) {
                        $this->editRolePermsSelect($player, $landConfigData);
                    } elseif (($data === 4 and $landConfigData !== null) or ($data === 3 and $landConfigData === null)) {
                        $this->editMemberPermsSelect($player, $landConfigData);
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("土地の詳細設定");
            $form->addButton("戻る");
            $form->addButton("削除");
            $form->addButton("デフォルト権限の編集");
            $form->addButton("役職権限の編集");
            $form->addButton("メンバー権限の編集");
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * デフォルト権限の編集
     * @param Player $player
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function editDefaultPerms(Player $player, LandConfigData $landConfigData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $this->checkLandConfig($player, $landConfigData);
                        return true;
                    } else {
                        $landConfigData->getLandPermsManager()->getDefalutLandPerms()->setEntry($data[1]);
                        $landConfigData->getLandPermsManager()->getDefalutLandPerms()->setBlockTap_Place($data[2]);
                        $landConfigData->getLandPermsManager()->getDefalutLandPerms()->setBlockBreak($data[3]);
                        $player->sendMessage("§a[システム] デフォルト権限を変更しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player, $landConfigData]), 10);
                    }

                    // update関数は絶対に呼び出してください、dbの方が更新されません
                    $landConfigData->update();
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("デフォルト権限の編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("立ち入り", $landConfigData->getLandPermsManager()->getDefalutLandPerms()->isEntry());
            $form->addToggle("ブロックタップ・設置", $landConfigData->getLandPermsManager()->getDefalutLandPerms()->isBlockTap_Place());
            $form->addToggle("ブロック破壊", $landConfigData->getLandPermsManager()->getDefalutLandPerms()->isBlockBreak());
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * ロール権限の編集選択
     * @param Player $player
     * @param LandConfigData $landConfigData
     * @return void
     */
    public function editRolePermsSelect(Player $player, LandConfigData $landConfigData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->checkLandConfig($player, $landConfigData);
                    } elseif ($data === 1) {
                        $this->addRolePermsRoleSelect($player, $landConfigData);
                    } else {
                        $this->editRolePerms($player, $landConfigData->getLandPermsManager()->getAllRoleLandPerms()[$data - 2], $landConfigData);
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("ロール権限の編集");
            $form->addButton("キャンセルして戻る");
            $form->addButton("ロールの追加");
            foreach ($landConfigData->getLandPermsManager()->getAllRoleLandPerms() as $landRole) {
                $role = RoleDataManager::getInstance()->get($landRole->getRoleid());
                $form->addButton($role->getName());
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * ロール権限の追加
     * 追加するロールの選択
     * @param Player $player
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function addRolePermsRoleSelect(Player $player, LandConfigData $landConfigData): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionRoleData = RoleDataManager::getInstance()->getFactionRoles($playerData->getFaction());
            $factionRoleData = array_filter($factionRoleData, function ($roleData) use ($landConfigData, $player) {
                return !$landConfigData->getLandPermsManager()->getRoleLandPerms($roleData->getId());
            });

            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData, $factionRoleData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->editRolePermsSelect($player, $landConfigData);
                    } else {
                        $this->addRolePermsSetRolePerms($player, array_values($factionRoleData)[$data - 1], $landConfigData);
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("ロール権限の追加");
            $form->addButton("キャンセルして戻る");
            foreach ($factionRoleData as $role) {
                $form->addButton($role->getName());
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * ロール権限の追加
     * 追加するロール権限の設定
     *
     * @param Player $player
     * @param RoleData $roleData
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function addRolePermsSetRolePerms(Player $player, RoleData $roleData, LandConfigData $landConfigData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($landConfigData, $roleData) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $this->addRolePermsRoleSelect($player, $landConfigData);
                    } else {
                        $landConfigData->getLandPermsManager()->createRoleLandPerms($roleData->getId(), $data[1], $data[2], $data[3]);
                        $landConfigData->update();
                        $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を追加しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player, $landConfigData]), 10);
                    }

                    // update関数は絶対に呼び出してください、dbの方が更新されません
                    $landConfigData->update();
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("ロール権限の追加-権限の設定");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("立ち入り", true);
            $form->addToggle("ブロックタップ・設置", true);
            $form->addToggle("ブロック設置", true);
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * ロール権限の編集
     *
     * @param Player $player
     * @param RoleLandPerms $rolePerms
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function editRolePerms(Player $player, RoleLandPerms $rolePerms, LandConfigData $landConfigData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($landConfigData, $rolePerms) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $this->editRolePermsSelect($player, $landConfigData);
                        return true;
                    } elseif ($data[1] === true) {
                        $landConfigData->getLandPermsManager()->deleteRoleLandPerms($rolePerms->getRoleid());
                        $landConfigData->update();
                        $roleData = RoleDataManager::getInstance()->get($rolePerms->getRoleid());
                        $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を削除しました");
                    } else {
                        $rolePerms->setEntry($data[2]);
                        $rolePerms->setBlockTap_Place($data[3]);
                        $rolePerms->setBlockBreak($data[4]);
                        $roleData = RoleDataManager::getInstance()->get($rolePerms->getRoleid());
                        $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を変更しました");
                    }

                    // update関数は絶対に呼び出してください、dbの方が更新されません
                    $landConfigData->update();
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editRolePermsSelect"], [$player, $landConfigData]), 10);
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("ロール権限の編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addToggle("立ち入り", $rolePerms->isEntry());
            $form->addToggle("ブロックタップ・設置", $rolePerms->isBlockTap_Place());
            $form->addToggle("ブロック破壊", $rolePerms->isBlockBreak());
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * メンバー権限の編集選択
     *
     * @param Player $player
     * @param LandConfigData $landConfigData
     * @return void
     */
    public function editMemberPermsSelect(Player $player, LandConfigData $landConfigData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->checkLandConfig($player, $landConfigData);
                    } elseif ($data === 1) {
                        $this->addMemberPermsMemberSelect($player, $landConfigData);
                    } elseif ($data === 2) {
                        $this->editMemberPerms($player, $landConfigData->getLandPermsManager()->getAllMemberLandPerms()[$data - 2], $landConfigData);
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("メンバー権限の編集");
            $form->addButton("キャンセルして戻る");
            $form->addButton("メンバーの追加");
            foreach ($landConfigData->getLandPermsManager()->getAllMemberLandPerms() as $landMember) {
                $form->addButton($landMember->getName());
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * メンバー権限の追加
     * 追加するメンバーの選択
     *
     * @param Player $player
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function addMemberPermsMemberSelect(Player $player, LandConfigData $landConfigData): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionMember = PlayerDataManager::getInstance()->getFactionPlayers($playerData->getFaction());
            $factionMember = array_filter($factionMember, function ($member) use ($landConfigData) {
                return !$landConfigData->getLandPermsManager()->getMemberLandPerms($member->getName());
            });

            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData, $factionMember) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->editMemberPermsSelect($player, $landConfigData);
                    } else {
                        $this->addMemberPermsSetMemberPerms($player, $factionMember[$data - 1], $landConfigData);
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("メンバー権限の追加");
            $form->addButton("キャンセルして戻る");
            foreach ($factionMember as $member) {
                $form->addButton($member->getName());
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * メンバー権限の追加
     * 追加するメンバー権限の設定
     *
     * @param Player $player
     * @param PlayerData $playerData
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function addMemberPermsSetMemberPerms(Player $player, PlayerData $playerData, LandConfigData $landConfigData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($landConfigData, $playerData) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $this->addMemberPermsMemberSelect($player, $landConfigData);
                    } else {
                        $landConfigData->getLandPermsManager()->createMemberLandPerms($playerData->getName(), $data[1], $data[2], $data[3]);
                        $player->sendMessage("§a[システム] {$playerData->getName()}のメンバー権限を追加しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player, $landConfigData]), 10);
                    }

                    // update関数は絶対に呼び出してください、dbの方が更新されません
                    $landConfigData->update();
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            $form->setTitle("メンバー権限の追加-権限の設定");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("立ち入り", true);
            $form->addToggle("ブロックタップ・設置", true);
            $form->addToggle("ブロック設置", true);
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    /**
     * メンバー権限の編集
     *
     * @param Player $player
     * @param MemberLandPerms $memberPerms
     * @param LandConfigData $landConfigData
     * @return void
     */
    private function editMemberPerms(Player $player, MemberLandPerms $memberPerms, LandConfigData $landConfigData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($landConfigData, $memberPerms) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $this->editMemberPermsSelect($player, $landConfigData);
                        return true;
                    } elseif ($data[1] === true) {
                        $landConfigData->getLandPermsManager()->deleteMemberLandPerms($memberPerms->getName());
                        $player->sendMessage("§a[システム] {$memberPerms->getName()}のメンバー権限を削除しました");
                    } else {
                        $memberPerms->setEntry($data[2]);
                        $memberPerms->setBlockTap_Place($data[3]);
                        $memberPerms->setBlockBreak($data[4]);
                        $player->sendMessage("§a[システム] {$memberPerms->getName()}のメンバー権限を変更しました");
                    }

                    // update関数は絶対に呼び出してください、dbの方が更新されません
                    $landConfigData->update();
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editMemberPermsSelect"], [$player, $landConfigData]), 10);
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            $form->setTitle("メンバー権限の編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addToggle("立ち入り", $memberPerms->isEntry());
            $form->addToggle("ブロックタップ・設置", $memberPerms->isBlockTap_Place());
            $form->addToggle("ブロック破壊", $memberPerms->isBlockBreak());
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}