<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigDataManager;
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
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;
use function strtolower;
use function array_filter;
use function array_values;
use function floor;

class LandConfigForm
{
    /**
     * 土地保護詳細設定キャッシュ
     * @var array
     */
    private static array $landConfigCache;

    /**
     * 土地保護デフォルト権限キャッシュ
     * @var array
     */
    private static array $landDefaultPermsCache;

    /**
     * 土地保護ロール権限キャッシュ
     * @var array
     */
    private static array $landRolePermsCache;

    /**
     * 土地保護メンバー権限キャッシュ
     * @var array
     */
    private static array $landMemberPermsCache;

    public function __construct()
    {
        if (!isset(self::$landConfigCache)) self::$landConfigCache = [];
        if (!isset(self::$landDefaultPermsCache)) self::$landDefaultPermsCache = [];
        if (!isset(self::$landRolePermsCache)) self::$landRolePermsCache = [];
        if (!isset(self::$landMemberPermsCache)) self::$landMemberPermsCache = [];
    }

    public function execute(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        unset(self::$landConfigCache[strtolower($player->getName())], self::$landDefaultPermsCache[strtolower($player->getName())], self::$landRolePermsCache[strtolower($player->getName())], self::$landMemberPermsCache[strtolower($player->getName())]);
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
                        $permsManager = $landConfigData->getLandPermsManager();

                        self::$landDefaultPermsCache[strtolower($player->getName())] = array(
                            "blockTap" => $permsManager->getDefalutLandPerms()->isBlockTap(),
                            "blockPlace" => $permsManager->getDefalutLandPerms()->isBlockPlace(),
                            "blockBreak" => $permsManager->getDefalutLandPerms()->isBlockBreak()
                        );

                        foreach ($permsManager->getAllRoleLandPerms() as $roleLandPerms) {
                            self::$landRolePermsCache[strtolower($player->getName())][$roleLandPerms->getRoleid()] = array(
                                "id" => $roleLandPerms->getRoleid(),
                                "blockTap" => $roleLandPerms->isBlockTap(),
                                "blockPlace" => $roleLandPerms->isBlockPlace(),
                                "blockBreak" => $roleLandPerms->isBlockBreak()
                            );
                        }

                        foreach ($permsManager->getAllMemberLandPerms() as $memberLandPerms) {
                            self::$landMemberPermsCache[strtolower($player->getName())][$memberLandPerms->getName()] = array(
                                "name" => $memberLandPerms->getName(),
                                "blockTap" => $memberLandPerms->isBlockTap(),
                                "blockPlace" => $memberLandPerms->isBlockPlace(),
                                "blockBreak" => $memberLandPerms->isBlockBreak()
                            );
                        }

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
                    elseif ($data === 2) {
                        $this->checkReset($player);
                    }
                }
                catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });

            try {
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
                }

                // 2
                $form->addButton("強制リセット");

                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * 配列の値全てリセットする
     * @param string $name
     * @return void
     */
    private function resetAll(string $name): void
    {
        unset(self::$landConfigCache[strtolower($name)], self::$landDefaultPermsCache[strtolower($name)], self::$landRolePermsCache[strtolower($name)], self::$landMemberPermsCache[strtolower($name)]);
    }

    private function checkLandConfig(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    unset(self::$landConfigCache[strtolower($player->getName())], self::$landDefaultPermsCache[strtolower($player->getName())], self::$landRolePermsCache[strtolower($player->getName())], self::$landMemberPermsCache[strtolower($player->getName())]);
                    $this->execute($player);
                }
                elseif ($data === 1 and $landConfigData !== null) {
                    LandConfigDataManager::getInstance()->delete($landConfigData->getId());
                    $player->sendMessage("§a[システム] 削除しました");
                }
                elseif (($data === 2 and $landConfigData !== null) or ($data === 1 and $landConfigData === null)) {
                    $landData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                    LandConfigDataManager::getInstance()->create(
                        $landData->getId(),
                        self::$landConfigCache[strtolower($player->getName())]["startx"],
                        self::$landConfigCache[strtolower($player->getName())]["startz"],
                        self::$landConfigCache[strtolower($player->getName())]["endx"],
                        self::$landConfigCache[strtolower($player->getName())]["endz"],
                        self::$landDefaultPermsCache[strtolower($player->getName())] ?? array(
                            "blockTap" => true,
                            "blockPlace" => true,
                            "blockBreak" => true,
                        ),
                        self::$landRolePermsCache[strtolower($player->getName())] ?? array(),
                        self::$landMemberPermsCache[strtolower($player->getName())] ?? array()
                    );
                    $this->resetAll($player->getName());
                    $player->sendMessage("§a[システム] 保存しました");
                }
                elseif (($data === 3 and $landConfigData !== null) or ($data === 2 and $landConfigData === null)) {
                    $this->editDefaultPerms($player);
                }
                elseif (($data === 4 and $landConfigData !== null) or ($data === 3 and $landConfigData === null)) {
                    $this->editRolePermsSelect($player);
                }

                return true;
            });

            try {
                $form->setTitle("土地の詳細設定");
                $form->addButton("リセットして戻る");
                if ($landConfigData !== null) {
                    $form->addButton("削除");
                }
                $form->addButton("保存");
                $form->addButton("デフォルト権限の編集");
                $form->addButton("役職権限の編集");
                $form->addButton("メンバー権限の編集");
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * デフォルト権限の編集
     * @param Player $player
     * @return void
     */
    private function editDefaultPerms(Player $player): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                if ($data === null) return true;
                elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2]) or !isset($data[3])) {
                    $this->editDefaultPerms($player);
                }
                elseif ($data[0] === true) {
                    $this->checkLandConfig($player);
                }
                else {
                    self::$landDefaultPermsCache[strtolower($player->getName())]["blockTap"] = $data[1];
                    self::$landDefaultPermsCache[strtolower($player->getName())]["blockPlace"] = $data[2];
                    self::$landDefaultPermsCache[strtolower($player->getName())]["blockBreak"] = $data[3];
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player]), 10);
                    $player->sendMessage("§a[システム] デフォルト権限を変更しました");
                }

                return true;
            });

            try {
                $form->setTitle("デフォルト権限の編集");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("ブロックタップ", self::$landDefaultPermsCache[strtolower($player->getName())]["blockTap"] ?? true);
                $form->addToggle("ブロック設置", self::$landDefaultPermsCache[strtolower($player->getName())]["blockPlace"] ?? true);
                $form->addToggle("ブロック設置", self::$landDefaultPermsCache[strtolower($player->getName())]["blockBreak"] ?? true);
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * ロール権限の編集選択
     * @param Player $player
     * @return void
     */
    private function editRolePermsSelect(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    $this->checkLandConfig($player);
                }
                elseif ($data === 1) {
                    $this->addRolePermsRoleSelect($player);
                }
                else {
                    $this->editRolePerms($player,self::$landRolePermsCache[strtolower($player->getName())][$data - 2]);
                }

                return true;
            });

            try {
                $form->setTitle("ロール権限の編集");
                $form->addButton("キャンセルして戻る");
                $form->addButton("ロールの追加");
                foreach (self::$landRolePermsCache[strtolower($player->getName())] ?? array() as $landRole) {
                    $role = RoleDataManager::getInstance()->get($landRole["id"]);
                    $form->addButton($role->getName());
                }
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * ロール権限の追加
     * 追加するロールの選択
     * @param Player $player
     * @return void
     */
    private function addRolePermsRoleSelect(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $factionRoleData = RoleDataManager::getInstance()->getFactionRoles($playerData->getFaction());
            $factionRoleData = array_filter($factionRoleData, function ($roleData) use ($player) {
                return !isset(self::$landRolePermsCache[strtolower($player->getName())][$roleData->getId()]);
            });

            $form = new SimpleForm(function (Player $player, $data) use ($factionRoleData) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    $this->editRolePermsSelect($player);
                }
                else {
                    $this->addRolePermsSetRolePerms($player, $factionRoleData[$data - 1]);
                }

                return true;
            });

            try {
                $form->setTitle("ロール権限の追加");
                $form->addButton("キャンセルして戻る");

                foreach ($factionRoleData as $role) {
                    $form->addButton($role->getName());
                }
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * ロール権限の追加
     * 追加するロール権限の設定
     *
     * @param Player $player
     * @param RoleData $roleData
     * @return void
     */
    private function addRolePermsSetRolePerms(Player $player, RoleData $roleData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($roleData) {
                if ($data === null) return true;
                elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2]) or !isset($data[3])) {
                    $this->addRolePermsSetRolePerms($player, $roleData);
                }
                elseif ($data[0] === true) {
                    $this->addRolePermsRoleSelect($player);
                }
                else {
                    self::$landRolePermsCache[strtolower($player->getName())][$roleData->getId()] = array(
                        "id" =>$roleData->getId(),
                        "blockTap" => $data[1],
                        "blockPlace" => $data[2],
                        "blockBreak" => $data[3]
                    );
                    $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を追加しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player]), 10);
                }

                return true;
            });

            try {
                $form->setTitle("ロール権限の追加-権限の設定");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("ブロックタップ", true);
                $form->addToggle("ブロック設置", true);
                $form->addToggle("ブロック設置", true);
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }

    /**
     * ロール権限の編集
     *
     * @param Player $player
     * @param array $rolePerms
     * @return void
     */
    private function editRolePerms(Player $player, array $rolePerms): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($rolePerms) {
                if ($data === null) return true;
                elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2]) or !isset($data[3]) or !isset($data[4])) {
                    $this->editRolePerms($player, $rolePerms);
                }
                elseif ($data[0] === true) {
                    $this->editRolePermsSelect($player);
                    return true;
                }
                elseif ($data[1] === true) {
                    unset(self::$landRolePermsCache[strtolower($player->getName())][$rolePerms["id"]]);
                    $roleData = RoleDataManager::getInstance()->get($rolePerms["id"]);
                    $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を削除しました");
                }
                else {
                    self::$landRolePermsCache[strtolower($player->getName())][$rolePerms["id"]] = array(
                        "id" => $rolePerms["id"],
                        "blockTap" => $data[2],
                        "blockPlace" => $data[3],
                        "blockBreak" => $data[4]
                    );
                    $roleData = RoleDataManager::getInstance()->get($rolePerms["id"]);
                    $player->sendMessage("§a[システム] {$roleData->getName()}のロール権限を変更しました");
                }

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editRolePermsSelect"], [$player]), 10);

                return true;
            });

            try {
                $form->setTitle("ロール権限の編集");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("削除して戻る");
                $form->addToggle("ブロックタップ", $rolePerms["blockTap"]);
                $form->addToggle("ブロック設置", $rolePerms["blockPlace"]);
                $form->addToggle("ブロック設置", $rolePerms["blockBreak"]);
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }

    /**
     * メンバー権限の編集選択
     *
     * @param Player $player
     * @return void
     */
    private function editMemberPermsSelect(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    $this->checkLandConfig($player);
                }
                elseif ($data === 1) {
                    $this->addMemberPermsMemberSelect($player);
                }
                elseif ($data === 2) {
                    $this->editMemberPerms($player, array_values(self::$landMemberPermsCache[strtolower($player->getName())])[$data - 2]);
                }

                return true;
            });

            try {
                $form->setTitle("メンバー権限の編集");
                $form->addButton("キャンセルして戻る");
                $form->addButton("メンバーの追加");
                foreach (self::$landMemberPermsCache[strtolower($player->getName())] ?? array() as $landMember) {
                    $form->addButton($landMember["name"]);
                }
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * メンバー権限の追加
     * 追加するメンバーの選択
     *
     * @param Player $player
     * @return void
     */
    private function addMemberPermsMemberSelect(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $factionMember = PlayerDataManager::getInstance()->getFactionPlayers($playerData->getFaction());
            $factionMember = array_filter($factionMember, function ($member) use ($player) {
                return !isset(self::$landMemberPermsCache[strtolower($player->getName())][$member->getName()]);
            });

            $form = new SimpleForm(function (Player $player, $data) use ($factionMember) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    $this->editMemberPermsSelect($player);
                }
                else {
                    $this->addMemberPermsSetMemberPerms($player, $factionMember[$data - 1]);
                }

                return true;
            });

            try {
                $form->setTitle("メンバー権限の追加");
                $form->addButton("キャンセルして戻る");

                foreach ($factionMember as $member) {
                    $form->addButton($member->getName());
                }
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }

    /**
     * メンバー権限の追加
     * 追加するメンバー権限の設定
     *
     * @param Player $player
     * @param PlayerData $playerData
     * @return void
     */
    private function addMemberPermsSetMemberPerms(Player $player, PlayerData $playerData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($playerData) {
                if ($data === null) return true;
                elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2]) or !isset($data[3])) {
                    $this->addMemberPermsSetMemberPerms($player, $playerData);
                }
                elseif ($data[0] === true) {
                    $this->addMemberPermsMemberSelect($player);
                }
                else {
                    self::$landRolePermsCache[strtolower($player->getName())][$playerData->getName()] = array(
                        "name" => $playerData->getName(),
                        "blockTap" => $data[1],
                        "blockPlace" => $data[2],
                        "blockBreak" => $data[3]
                    );

                    $player->sendMessage("§a[システム] {$playerData->getName()}のメンバー権限を追加しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "checkLandConfig"], [$player]), 10);
                }

                return true;
            });

            try {
                $form->setTitle("メンバー権限の追加-権限の設定");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("ブロックタップ", true);
                $form->addToggle("ブロック設置", true);
                $form->addToggle("ブロック設置", true);
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }

    /**
     * メンバー権限の編集
     *
     * @param Player $player
     * @param array $memberPerms
     * @return void
     */
    private function editMemberPerms(Player $player, array $memberPerms): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($memberPerms) {
                if ($data === null) return true;
                elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2]) or !isset($data[3]) or !isset($data[4])) {
                    $this->editMemberPerms($player, $memberPerms);
                }
                elseif ($data[0] === true) {
                    $this->editMemberPermsSelect($player);
                    return true;
                }
                elseif ($data[1] === true) {
                    unset(self::$landMemberPermsCache[strtolower($player->getName())][$memberPerms["name"]]);
                    $player->sendMessage("§a[システム] ロール権限を削除しました");
                }
                else {
                    self::$landMemberPermsCache[strtolower($player->getName())][$memberPerms["name"]] = array(
                        "name" => $memberPerms["name"],
                        "blockTap" => $data[2],
                        "blockPlace" => $data[3],
                        "blockBreak" => $data[4]
                    );
                    $player->sendMessage("§a[システム] ロール権限を変更しました");
                }

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editMemberPermsSelect"], [$player]), 10);

                return true;
            });

            try {
                $form->setTitle("メンバー権限の編集");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("削除して戻る");
                $form->addToggle("ブロックタップ", $memberPerms["blockTap"]);
                $form->addToggle("ブロック設置", $memberPerms["blockPlace"]);
                $form->addToggle("ブロック設置", $memberPerms["blockBreak"]);
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }

    /**
     * 強制リセットしていいか確認
     *
     * @param Player $player
     * @return void
     */
    private function checkReset(Player $player): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) {
                if ($data === true) {
                    $this->resetAll($player->getName());
                    $player->sendMessage("§a[システム] 土地保護キャッシュを強制リセットしました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                }
                elseif ($data === false) {
                    $this->execute($player);
                }
            });

            try {
                $form->setTitle("強制リセット確認");
                $form->setContent("現在の土地保護キャッシュを強制リセットします？よろしいですか？");
                $form->setButton1("はい");
                $form->setButton1("いいえ");
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getOutiServerLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $player);
        }
    }
}