<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Exception;
use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigData;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms\MemberLandPerms;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms\RoleLandPerms;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerData;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleData;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\SimpleForm;
use function strtolower;
use function array_filter;
use function floor;

class LandConfigForm
{
    /**
     * 土地保護詳細設定キャッシュ
     * @var array
     */
    private static array $landConfigCache;

    public function __construct()
    {
        if (!isset(self::$landConfigCache)) self::$landConfigCache = [];
    }

    public function execute(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        unset(self::$landConfigCache[strtolower($player->getName())]);
                        $form = new LandManagerForm();
                        $form->execute($player);
                    }
                    elseif ($data === 1 and !isset(self::$landConfigCache[strtolower($player->getName())]) and $landConfigData === null) {
                        self::$landConfigCache[strtolower($player->getName())] = array(
                            "world" => $player->getWorld()->getFolderName(),
                            "startx" => (int)$player->getPosition()->getX(),
                            "startz" => (int)$player->getPosition()->getZ()
                        );
                        $player->sendMessage("§a[システム] 開始X座標を" . (int)$player->getPosition()->getX() . "\n開始Z座標を" . (int)$player->getPosition()->getZ() . "に設定しました");
                    }
                    elseif ($data === 1 and $landConfigData !== null) {
                        $this->checkLandConfig($player);
                    }
                    elseif ($data === 1) {
                        if (self::$landConfigCache[strtolower($player->getName())]["world"] !== $player->getWorld()->getFolderName()) {
                            $player->sendMessage("§a[システム] 開始座標ワールドと現在いるワールドが違います");
                        }
                        self::$landConfigCache[strtolower($player->getName())]["endx"] = (int)$player->getPosition()->getX();
                        self::$landConfigCache[strtolower($player->getName())]["endz"] = (int)$player->getPosition()->getZ();
                        $landData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                        $startX = (int)floor(self::$landConfigCache[strtolower($player->getName())]["startx"]);
                        $endX = (int)floor(self::$landConfigCache[strtolower($player->getName())]["endx"]);
                        $startZ = (int)floor(self::$landConfigCache[strtolower($player->getName())]["startz"]);
                        $endZ = (int)floor(self::$landConfigCache[strtolower($player->getName())]["endz"]);
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

                            LandConfigDataManager::getInstance()->create(
                            $landData->getId(),
                            $startX,
                            $startZ,
                            $endX,
                            $endZ,
                            array(
                                "blockTap" => true,
                                "blockPlace" => true,
                                "blockBreak" => true,
                            ),
                            array(),
                            array()
                        );
                        $this->checkLandConfig($player);
                    }
                    elseif ($data === 2 and $landConfigData === null and isset(self::$landConfigCache[strtolower($player->getName())])) {
                        unset(self::$landConfigCache[strtolower($player->getName())]);
                        $player->sendMessage("§a[システム] 開始座標をリセットしました");
                    }
                }
                catch (Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            // 0
            $form->addButton("キャンセルして戻る");
            if (!isset(self::$landConfigCache[strtolower($player->getName())]) and $landConfigData === null) {
                // 1
                $form->addButton("開始座標の設定");
            }
            elseif ($landConfigData !== null) {
                // 1
                $form->addButton("現在立っている土地の詳細設定");
            }
            else {
                // 1
                $form->addButton("終了座標の設定");
                // 2
                $form->addButton("開始座標リセット");
            }

            $player->sendForm($form);
        }
        catch (Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error,true, $player);
        }
    }

    public function checkLandConfig(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->execute($player);
                    }
                    elseif ($data === 1 and $landConfigData !== null) {
                        LandConfigDataManager::getInstance()->delete($landConfigData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                    }
                    elseif (($data === 2 and $landConfigData !== null) or ($data === 1 and $landConfigData === null)) {
                        $this->editDefaultPerms($player, $landConfigData);
                    }
                    elseif (($data === 3 and $landConfigData !== null) or ($data === 2 and $landConfigData === null)) {
                        $this->editRolePermsSelect($player, $landConfigData);
                    }
                    elseif (($data === 4 and $landConfigData !== null) or ($data === 3 and $landConfigData === null)) {
                        $this->editMemberPermsSelect($player, $landConfigData);
                    }
                }
                catch (Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("土地の詳細設定");
            $form->addButton("戻る");
            if ($landConfigData !== null) {
                $form->addButton("削除");
            }
            $form->addButton("デフォルト権限の編集");
            $form->addButton("役職権限の編集");
            $form->addButton("メンバー権限の編集");
            $player->sendForm($form);
        }
        catch (Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error,true, $player);
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
                        $this->checkLandConfig($player);
                    }
                    else {
                        $landConfigData->getLandPermsManager()->getDefalutLandPerms()->setBlockTap($data[1]);
                        $landConfigData->getLandPermsManager()->getDefalutLandPerms()->setBlockPlace($data[2]);
                        $landConfigData->getLandPermsManager()->getDefalutLandPerms()->setBlockBreak($data[3]);
                        $landConfigData->update();
                        $player->sendMessage("§a[システム] デフォルト権限を変更しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player]), 10);
                    }
                }
                catch (Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("デフォルト権限の編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("ブロックタップ", $landConfigData->getLandPermsManager()->getDefalutLandPerms()->isBlockTap());
            $form->addToggle("ブロック設置", $landConfigData->getLandPermsManager()->getDefalutLandPerms()->isBlockPlace());
            $form->addToggle("ブロック破壊", $landConfigData->getLandPermsManager()->getDefalutLandPerms()->isBlockBreak());
            $player->sendForm($form);
        }
        catch (Exception $error) {
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
                        $this->checkLandConfig($player);
                    }
                    elseif ($data === 1) {
                        $this->addRolePermsRoleSelect($player, $landConfigData);
                    }
                    else {
                        $this->editRolePerms($player, $landConfigData->getLandPermsManager()->getAllRoleLandPerms()[$data], $landConfigData);
                    }
                }
                catch (Exception $error) {
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
        }
        catch (Exception $error) {
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
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $factionRoleData = RoleDataManager::getInstance()->getFactionRoles($playerData->getFaction());
            $factionRoleData = array_filter($factionRoleData, function ($roleData) use ($landConfigData, $player) {
                return !$landConfigData->getLandPermsManager()->getRoleLandPerms($roleData->getId());
            });

            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData, $factionRoleData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->editRolePermsSelect($player, $landConfigData);
                    }
                    else {
                        $this->addRolePermsSetRolePerms($player, $factionRoleData[$data - 1], $landConfigData);
                    }
                }
                catch (Exception $error) {
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
        }
        catch (Exception $error) {
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
                    }
                    else {
                        $landConfigData->getLandPermsManager()->createRoleLandPerms($roleData->getId(), $data[1], $data[2], $data[3]);
                        $landConfigData->update();
                        $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を追加しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player]), 10);
                    }
                }
                catch (Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("ロール権限の追加-権限の設定");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("ブロックタップ", true);
            $form->addToggle("ブロック設置", true);
            $form->addToggle("ブロック設置", true);
            $player->sendForm($form);
        }
        catch (Exception $error) {
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
                    }
                    elseif ($data[1] === true) {
                        $landConfigData->getLandPermsManager()->deleteRoleLandPerms($rolePerms->getRoleid());
                        $landConfigData->update();
                        $roleData = RoleDataManager::getInstance()->get($rolePerms->getRoleid());
                        $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を削除しました");
                    }
                    else {
                        $rolePerms->setBlockTap($data[2]);
                        $rolePerms->setBlockPlace($data[3]);
                        $rolePerms->setBlockBreak($data[4]);
                        $landConfigData->update();
                        $roleData = RoleDataManager::getInstance()->get($rolePerms["id"]);
                        $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を変更しました");
                    }

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editRolePermsSelect"], [$player]), 10);
                }
                catch (Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("ロール権限の編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addToggle("ブロックタップ", $rolePerms->isBlockTap());
            $form->addToggle("ブロック設置", $rolePerms->isBlockPlace());
            $form->addToggle("ブロック破壊", $rolePerms->isBlockBreak());
            $player->sendForm($form);
        }
        catch (Exception $error) {
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
                        $this->checkLandConfig($player);
                    }
                    elseif ($data === 1) {
                        $this->addMemberPermsMemberSelect($player, $landConfigData);
                    }
                    elseif ($data === 2) {
                        $this->editMemberPerms($player, $landConfigData->getLandPermsManager()->getAllMemberLandPerms()[$data - 2], $landConfigData);
                    }
                }
                catch (Exception $error) {
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
        }
        catch (Exception $error) {
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
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $factionMember = PlayerDataManager::getInstance()->getFactionPlayers($playerData->getFaction());
            $factionMember = array_filter($factionMember, function ($member) use ($landConfigData) {
                return !$landConfigData->getLandPermsManager()->getMemberLandPerms($member->getName());
            });

            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData, $factionMember) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $this->editMemberPermsSelect($player, $landConfigData);
                    }
                    else {
                        $this->addMemberPermsSetMemberPerms($player, $factionMember[$data - 1], $landConfigData);
                    }
                }
                catch (Exception $error) {
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
        }
        catch (Exception $error) {
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
                    }
                    else {
                        $landConfigData->getLandPermsManager()->createMemberLandPerms($playerData->getName(), $data[1], $data[2], $data[3]);
                        $landConfigData->update();
                        $player->sendMessage("§a[システム] {$playerData->getName()}のメンバー権限を追加しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player]), 10);
                    }
                }
                catch (Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            $form->setTitle("メンバー権限の追加-権限の設定");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("ブロックタップ", true);
            $form->addToggle("ブロック設置", true);
            $form->addToggle("ブロック設置", true);
            $player->sendForm($form);
        }
        catch (Exception $error) {
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
                    }
                    elseif ($data[1] === true) {
                        $landConfigData->getLandPermsManager()->deleteMemberLandPerms($memberPerms->getName());
                        $landConfigData->update();
                        $player->sendMessage("§a[システム] {$memberPerms->getName()}のメンバー権限を削除しました");
                    }
                    else {
                        $memberPerms->setBlockTap($data[2]);
                        $memberPerms->setBlockPlace($data[3]);
                        $memberPerms->setBlockBreak($data[4]);
                        $landConfigData->update();
                        $player->sendMessage("§a[システム] {$memberPerms->getName()}のメンバー権限を変更しました");
                    }

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editMemberPermsSelect"], [$player, $landConfigData]), 10);
                }
                catch (Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            try {
                $form->setTitle("メンバー権限の編集");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("削除して戻る");
                $form->addToggle("ブロックタップ", $memberPerms->isBlockTap());
                $form->addToggle("ブロック設置", $memberPerms->isBlockPlace());
                $form->addToggle("ブロック破壊", $memberPerms->isBlockBreak());
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}