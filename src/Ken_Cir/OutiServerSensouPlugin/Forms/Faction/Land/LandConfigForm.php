<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleData;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\SimpleForm;
use function strtolower;

class LandConfigForm
{
    /**
     * 土地保護詳細設定キャッシュ
     * @var array
     */
    private array $landConfigCache;

    /**
     * 土地保護デフォルト権限キャッシュ
     * @var array
     */
    private array $landDefaultPermsCache;

    /**
     * 土地保護ロール権限キャッシュ
     * @var array
     */
    private array $landRolePermsCache;

    /**
     * 土地保護メンバー権限キャッシュ
     * @var array
     */
    private array $landMemberPermsCache;

    public function __construct()
    {
        $this->landConfigCache = [];
        $this->landDefaultPermsCache = [];
        $this->landRolePermsCache = [];
        $this->landMemberPermsCache = [];
    }

    public function execute(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        unset($this->landConfigCache[strtolower($player->getName())], $this->landDefaultPermsCache[strtolower($player->getName())], $this->landRolePermsCache[strtolower($player->getName())], $this->landMemberPermsCache[strtolower($player->getName())]);
                        $form = new LandManagerForm();
                        $form->execute($player);
                    }
                    elseif ($data === 1 and !isset($this->landConfigCache[strtolower($player->getName())]) and $landConfigData === null) {
                        $this->landConfigCache[strtolower($player->getName())] = array(
                            "world" => $player->getWorld()->getFolderName(),
                            "startx" => (int)$player->getPosition()->getX(),
                            "startz" => (int)$player->getPosition()->getZ()
                        );
                        $player->sendMessage("§a[システム] 開始X座標を" . (int)$player->getPosition()->getX() . "\n開始Z座標を" . (int)$player->getPosition()->getZ() . "に設定しました");
                    }
                    elseif ($data === 1 and $landConfigData !== null) {
                        $permsManager = $landConfigData->getLandPermsManager();

                        $this->landDefaultPermsCache[strtolower($player->getName())] = array(
                            "blockTap" => $permsManager->getDefalutLandPerms()->isBlockTap(),
                            "blockPlace" => $permsManager->getDefalutLandPerms()->isBlockPlace(),
                            "blockBreak" => $permsManager->getDefalutLandPerms()->isBlockBreak()
                        );

                        foreach ($permsManager->getAllRoleLandPerms() as $roleLandPerms) {
                            $this->landRolePermsCache[strtolower($player->getName())][$roleLandPerms->getRoleid()] = array(
                                "id" => $roleLandPerms->getRoleid(),
                                "blockTap" => $roleLandPerms->isBlockTap(),
                                "blockPlace" => $roleLandPerms->isBlockPlace(),
                                "blockBreak" => $roleLandPerms->isBlockBreak()
                            );
                        }

                        foreach ($permsManager->getAllMemberLandPerms() as $memberLandPerms) {
                            $this->landMemberPermsCache[strtolower($player->getName())][$memberLandPerms->getName()] = array(
                                "name" => $memberLandPerms->getName(),
                                "blockTap" => $memberLandPerms->isBlockTap(),
                                "blockPlace" => $memberLandPerms->isBlockPlace(),
                                "blockBreak" => $memberLandPerms->isBlockBreak()
                            );
                        }

                        $this->checkLandConfig($player);
                    }
                    elseif ($data === 1 and isset($this->landConfigCache[strtolower($player->getName())])) {
                        if ($this->landConfigCache[strtolower($player->getName())]["world"] !== $player->getWorld()->getFolderName()) {
                            $player->sendMessage("§a[システム] 開始座標ワールドと現在いるワールドが違います");
                        }
                        $this->landConfigCache[strtolower($player->getName())]["endx"] = (int)$player->getPosition()->getX();
                        $this->landConfigCache[strtolower($player->getName())]["endz"] = (int)$player->getPosition()->getX();
                        $this->checkLandConfig($player);
                    }
                    elseif ($data === 2 and isset($this->landConfigCache[strtolower($player->getName())])) {
                        unset($this->landConfigCache[strtolower($player->getName())], $this->landDefaultPermsCache[strtolower($player->getName())], $this->landRolePermsCache[strtolower($player->getName())], $this->landMemberPermsCache[strtolower($player->getName())]);
                        $player->sendMessage("§a[システム] 開始座標をリセットしました");
                    }
                }
                catch (Error|Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });

            try {
                $form->addButton("キャンセルして戻る");
                if (!isset($this->landConfigCache[strtolower($player->getName())]) and $landConfigData === null) {
                    $form->addButton("開始座標の設定");
                }
                elseif ($landConfigData !== null) {
                    $form->addButton("現在立っている土地の詳細設定");
                }
                else {
                    $form->addButton("終了座標の設定");
                    $form->addButton("開始座標のリセット");
                }
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getPluginLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
        }
    }

    private function checkLandConfig(Player $player): void
    {
        try {
            $landConfigData = LandConfigDataManager::getInstance()->getPos((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ(), $player->getWorld()->getFolderName());
            $form = new SimpleForm(function (Player $player, $data) use ($landConfigData) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    unset($this->landConfigCache[strtolower($player->getName())], $this->landDefaultPermsCache[strtolower($player->getName())], $this->landRolePermsCache[strtolower($player->getName())], $this->landMemberPermsCache[strtolower($player->getName())]);
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
                        $this->landConfigCache[strtolower($player->getName())]["startx"],
                        $this->landConfigCache[strtolower($player->getName())]["startz"],
                        $this->landConfigCache[strtolower($player->getName())]["endx"],
                        $this->landConfigCache[strtolower($player->getName())]["endz"],
                        $this->landDefaultPermsCache[strtolower($player->getName())] ?? array(
                            "blockTap" => true,
                            "blockPlace" => true,
                            "blockBreak" => true,
                        ),
                        $this->landRolePermsCache[strtolower($player->getName())] ?? array(),
                        $this->landMemberPermsCache[strtolower($player->getName())] ?? array()
                    );
                    unset($this->landConfigCache[strtolower($player->getName())], $this->landDefaultPermsCache[strtolower($player->getName())], $this->landRolePermsCache[strtolower($player->getName())], $this->landMemberPermsCache[strtolower($player->getName())]);
                    $player->sendMessage("§a[システム] 保存しました");
                }
                elseif (($data === 3 and $landConfigData !== null) or ($data === 2 and $landConfigData === null)) {
                    $this->editDefaultPerms($player);
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
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getPluginLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
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
                    $this->landDefaultPermsCache[strtolower($player->getName())]["blockTap"] = $data[1];
                    $this->landDefaultPermsCache[strtolower($player->getName())]["blockPlace"] = $data[2];
                    $this->landDefaultPermsCache[strtolower($player->getName())]["blockBreak"] = $data[3];
                    $player->sendMessage("§a[システム] デフォルト権限を変更しました");
                }

                return true;
            });

            try {
                $form->setTitle("デフォルト権限の編集");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("ブロックタップ", $this->landDefaultPermsCache[strtolower($player->getName())]["blockTap"] ?? true);
                $form->addToggle("ブロック設置", $this->landDefaultPermsCache[strtolower($player->getName())]["blockPlace"] ?? true);
                $form->addToggle("ブロック設置", $this->landDefaultPermsCache[strtolower($player->getName())]["blockBreak"] ?? true);
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getPluginLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
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
                    $this->editRolePerms($player, $this->landRolePermsCache[strtolower($player->getName())][$data - 2]);
                }

                return true;
            });

            try {
                $form->setTitle("ロール権限の編集");
                $form->addButton("キャンセルして戻る");
                $form->addButton("ロールの追加");
                foreach ($this->landRolePermsCache[strtolower($player->getName())] ?? array() as $landRole) {
                    $role = RoleDataManager::getInstance()->get($landRole["id"]);
                    $form->addButton($role->getName());
                }
                $player->sendForm($form);
            }
            catch (InvalidArgumentException | FormValidationException $exception) {
                Main::getInstance()->getPluginLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
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
            $factionData = PlayerDataManager::getInstance()->get($player->getName());
            $factionRoleData = RoleDataManager::getInstance()->getFactionRoles($factionData->getFaction());
            $factionRoleData = array_filter($factionRoleData, function ($roleData) use ($player) {
                return !isset($this->landRolePermsCache[strtolower($player->getName())][$roleData->getId()]);
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
                Main::getInstance()->getPluginLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
        }
    }

    /**
     * ロール権限の追加
     * 追加するロール権限の設定
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
                    $this->landRolePermsCache[strtolower($player->getName())][$roleData->getId()]["id"] = $roleData->getId();
                    $this->landRolePermsCache[strtolower($player->getName())][$roleData->getId()]["blockTap"] = $data[1];
                    $this->landRolePermsCache[strtolower($player->getName())][$roleData->getId()]["blockPlace"] = $data[1];
                    $this->landRolePermsCache[strtolower($player->getName())][$roleData->getId()]["blockBreak"] = $data[2];
                    $player->sendMessage("§a[システム] ロール権限を追加しました");
                    $this->checkLandConfig($player);
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
                Main::getInstance()->getPluginLogger()->error($exception);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * ロール権限の編集
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
                }
                elseif ($data[1] === true) {
                    unset($this->landRolePermsCache[strtolower($player->getName())][$rolePerms["id"]]);
                    $player->sendMessage("§a[システム] ロール権限を削除しました");
                    $this->editRolePermsSelect($player);
                }
                else {
                    $this->landRolePermsCache[strtolower($player->getName())][$rolePerms["id"]]["blockTap"] = $data[2];
                    $this->landRolePermsCache[strtolower($player->getName())][$rolePerms["id"]]["blockPlace"] = $data[3];
                    $this->landRolePermsCache[strtolower($player->getName())][$rolePerms["id"]]["blockBreak"] = $data[4];
                }

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
                Main::getInstance()->getPluginLogger()->error($exception, $player);
            }
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}