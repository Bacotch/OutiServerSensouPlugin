<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\SimpleForm;
use function strtolower;

class LandConfigForm
{
    private array $landConfigCache;
    private array $landPermsCache;

    public function __construct()
    {
        $this->landConfigCache = [];
        $this->landPermsCache = [];
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        unset($this->landConfigCache[strtolower($player->getName())]);
                        $form = new LandManagerForm();
                        $form->execute($player);
                    }
                    elseif ($data === 1 and !isset($this->landConfigCache[strtolower($player->getName())])) {
                        $this->landConfigCache[strtolower($player->getName())] = array(
                            "startx" => (int)$player->getPosition()->getX(),
                            "startz" => (int)$player->getPosition()->getZ()
                        );
                        $player->sendMessage("§a[システム] 開始X座標を" . (int)$player->getPosition()->getX() . "\n開始Z座標を" . (int)$player->getPosition()->getZ() . "に設定しました");
                    }
                    elseif ($data === 1 and isset($this->landConfigCache[strtolower($player->getName())])) {
                        $this->landConfigCache[strtolower($player->getName())]["endx"] = (int)$player->getPosition()->getX();
                        $this->landConfigCache[strtolower($player->getName())]["endz"] = (int)$player->getPosition()->getX();
                    }
                    elseif ($data === 2 and isset($this->landConfigCache[strtolower($player->getName())])) {
                        unset($this->landConfigCache[strtolower($player->getName())]);
                        $player->sendMessage("§a[システム] 開始座標をリセットしました");
                    }
                }
                catch (Error|Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            if (!isset($this->landConfigCache[strtolower($player->getName())])) {
                $form->addButton("開始座標の設定");
            }
            else {
                $form->addButton("終了座標の設定");
                $form->addButton("開始座標のリセット");
            }

        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
        }
    }

    private function checkLandConfig(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                if ($data === null) return true;
                elseif ($data === 0) {
                    $this->execute($player);
                }
                elseif ($data === 1) {
                    unset($this->landConfigCache[strtolower($player->getName())]);
                    $this->execute($player);
                }
                elseif ($data === 2) {
                    $landData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                    $landPerms = array(
                        "default" => $this->landPermsCache[strtolower($player->getName())]["default"] ?? array(
                                "blockTap" => true,
                                "blockPlace" => true,
                                "blockBreak" => true,
                            ),
                        "roles" => $this->landPermsCache[strtolower($player->getName())]["roles"] ?? array(),
                        "members" => $this->landPermsCache[strtolower($player->getName())]["members"] ?? array()
                    );
                    LandConfigDataManager::getInstance()->create(
                        $landData->getId(),
                        $this->landConfigCache[strtolower($player->getName())]["startx"],
                        $this->landConfigCache[strtolower($player->getName())]["startz"],
                        $this->landConfigCache[strtolower($player->getName())]["endx"],
                        $this->landConfigCache[strtolower($player->getName())]["endz"],
                        $landPerms
                    );
                    unset($this->landConfigCache[strtolower($player->getName())]["startx"]);
                    unset($this->landPermsCache[strtolower($player->getName())]);
                    $player->sendMessage("§a[システム] 保存しました");
                }
                elseif ($data === 3) {
                    $this->editDefaultPerms($player);
                }

                return true;
            });

            try {
                $form->setTitle("土地の詳細設定");
                $form->addButton("キャンセルして戻る");
                $form->addButton("リセットして戻る");
                $form->addButton("保存");
                $form->addButton("デフォルト権限の編集");
                $form->addButton("役職権限の追加");
                $form->addButton("役職権限の編集");
                $form->addButton("メンバー権限の追加");
                $form->addButton("メンバー権限の編集");
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
                    $this->landPermsCache[strtolower($player->getName())]["default"]["blockTap"] = $data[0];
                    $this->landPermsCache[strtolower($player->getName())]["default"]["blockPlace"] = $data[1];
                    $this->landPermsCache[strtolower($player->getName())]["default"]["blockBreak"] = $data[2];
                    $player->sendMessage("§a[システム] デフォルト権限を変更しました");
                }

                return true;
            });

            try {
                $form->setTitle("デフォルト権限の編集");
                $form->addToggle("キャンセルして戻る");
                $form->addToggle("ブロックタップ", $this->landPermsCache[strtolower($player->getName())]["default"]["blockTap"] ?? true);
                $form->addToggle("ブロック設置", $this->landPermsCache[strtolower($player->getName())]["default"]["blockPlace"] ?? true);
                $form->addToggle("ブロック設置", $this->landPermsCache[strtolower($player->getName())]["default"]["blockBreak"] ?? true);
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
     * 権限を追加する役職を選択する
     * @param Player $player
     * @return void
     */
    private function addLandPermsSelectRole(Player $player): void
    {

    }
}