<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\cache;

use Error;
use Exception;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCache;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class PlayerCacheForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        (new CacheManagerForm())->execute($player);
                        return;
                    }
                    elseif (!isset($data[2])) {
                        $player->sendMessage("§a[システム] キーは入力必須項目です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    }

                    $playerCache = null;
                    if ($data[1] === 0) {
                        $playerCache = PlayerCacheManager::getInstance()->getName(strtolower($data[2]));
                    }
                    elseif ($data[1] === 1) {
                        $playerCache = PlayerCacheManager::getInstance()->getXuid($data[2]);
                    }

                    if (!$playerCache) {
                        $player->sendMessage("§a[システム] プレイヤーデータが見つかりません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    }
                    $this->viewPlayerCache($player, $playerCache);
                }
                catch (Error|Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤーキャッシュ管理");
            $form->addToggle("キャンセルして戻る");
            $form->addDropdown("検索キー", ["プレイヤー名", "プレイヤーXUID"]);
            $form->addInput("検索キー", "key");
            $player->sendForm($form);
        }
        catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewPlayerCache(Player $player, PlayerCache $playerCache): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($playerCache) {
                try {
                    if ($data === true) {
                        $this->execute($player);
                    }
                    elseif ($data === false) {
                        $this->editPlayerCache($player, $playerCache);
                    }
                }
                catch (Error|Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤーキャッシュ {$playerCache->getName()}");
            $form->setContent("XUID: {$playerCache->getXuid()}\nプレイヤー名: {$playerCache->getName()}\nおうちウォッチがロックされてるか: " . ($playerCache->isLockOutiWatch() ? "ロックされている" : "ロックされていない") . "\n土地保護のワールド名: {$playerCache->getLandConfigWorldName()}\n土地保護の開始X座標: {$playerCache->getLandConfigStartX()}\n土地保護の開始Z座標: {$playerCache->getLandConfigStartZ()}");
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function editPlayerCache(Player $player, PlayerCache $playerCache): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($playerCache) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewPlayerCache($player, $playerCache);
                        return;
                    }
                    elseif ($data[1]) {
                        PlayerCacheManager::getInstance()->deleteXuid($playerCache->getXuid());
                        $player->sendMessage("§a[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    }
                    elseif (!isset($data[2]) or ($data[5] and !is_numeric($data[5])) or ($data[6] and !is_numeric($data[6]))) {
                        $player->sendMessage("§a[システム] プレイヤー名は入力必須項目で土地保護の開始X座標、Z座標は数値入力です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editPlayerCache"], [$player, $playerCache]), 20);
                        return;
                    }

                    $playerCache->setName($data[2]);
                    $playerCache->setLockOutiWatch($data[3]);
                    $playerCache->setLandConfigWorldName($data[4] ?? null);
                    $playerCache->setLandConfigStartX($data[5] ? (int)$data[5] : null);
                    $playerCache->setLandConfigStartZ($data[6] ? (int)$data[6] : null);

                    $player->sendMessage("変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                }
                catch (Error|Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤーキャッシュ {$playerCache->getName()} 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addInput("プレイヤー名§e(基本書き換え禁止)", "playerName", $playerCache->getName());
            $form->addToggle("おうちウォッチをロック", $playerCache->isLockOutiWatch());
            $form->addInput("土地保護のワールド名", "landConfig_WorldName", $playerCache->getLandConfigWorldName());
            $form->addInput("土地保護の開始X座標", "landConfig_StartX", $playerCache->getLandConfigStartX());
            $form->addInput("土地保護の開始Z座標", "landConfig_StartZ", $playerCache->getLandConfigStartZ());
            $player->sendForm($form);
        }
        catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}