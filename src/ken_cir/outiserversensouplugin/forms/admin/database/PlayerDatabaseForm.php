<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;


use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use function array_map;
use function array_unshift;
use function array_values;
use function join;

class PlayerDatabaseForm
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

                    $playerData = PlayerDataManager::getInstance()->getAll(true)[$data - 1];
                    $this->viewPlayerData($player, $playerData);
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤーデータ管理");
            $form->addButton("キャンセルして戻る");
            foreach (PlayerDataManager::getInstance()->getAll() as $playerData) {
                $form->addButton("{$playerData->getName()} {$playerData->getXuid()}");
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewPlayerData(Player $player, PlayerData $playerData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($playerData) {
                try {
                    if ($data === true) {
                        $this->execute($player);
                    } elseif ($data === false) {
                        $this->editPlayerData($player, $playerData);
                    }
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("プレイヤーデータ {$playerData->getName()}");
            $form->setContent("XUID: {$playerData->getXuid()}\nプレイヤー名: {$playerData->getName()}\nログインしたことのあるIPアドレス:\n" . join("\n", $playerData->getIp()) . "\n所属派閥: " . ($playerData->getFaction() !== -1 ? FactionDataManager::getInstance()->get($playerData->getFaction())->getName() : "所属なし") . "\nチャットモード: " . ($playerData->getChatmode() === -1 ? "全体" : "派閥") . "\nスコアボード表示: " . ($playerData->getDrawscoreboard() === 1 ? "表示" : "非表示") . "\n所持役職: \n" . join("\n", $playerData->getRoles(true)) . "\n処罰段階: {$playerData->getPunishment()}");
            $form->setButton1("戻る");
            $form->setButton2("変更");
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    public function editPlayerData(Player $player, PlayerData $playerData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($playerData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewPlayerData($player, $playerData);
                        return;
                    } elseif ($data[1]) {
                        PlayerDataManager::getInstance()->deleteXuid($playerData->getXuid());
                        $player->sendMessage("§a[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return;
                    } elseif (!isset($data[2])) {
                        $player->sendMessage("§a[システム] プレイヤー名は入力必須項目です");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "editPlayerData"], [$player, $playerData]), 20);
                        return;
                    }

                    $factionDatas = array_values(FactionDataManager::getInstance()->getAll());
                    $playerData->setName($data[2]);
                    $playerData->setFaction($data[3] === 0 ? -1 : $factionDatas[$data[3] - 1]->getId());
                    $playerData->setChatmode($data[4] === 0 ? -1 : $playerData->getFaction());
                    $playerData->setDrawscoreboard($data[5]);
                    $playerData->setPunishment($data[6]);

                    $player->sendMessage("§a[システム] 変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $factionDefault = 0;
            $factionDatas = array_values(FactionDataManager::getInstance()->getAll());
            foreach ($factionDatas as $key => $factionData) {
                if ($factionData->getId() === $playerData->getFaction()) {
                    $factionDefault = $key + 1;
                    break;
                }
            }
            $factionDatas = array_map(function (FactionData $factionData) {
                return $factionData->getName();
            }, FactionDataManager::getInstance()->getAll());
            array_unshift($factionDatas, "無所属");

            $form->setTitle("プレイヤーデータ編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addInput("プレイヤー名§e(基本書き換え禁止)", "playerName", $playerData->getName());
            $form->addDropdown("派閥", $factionDatas, $factionDefault);
            $form->addDropdown("チャットモード", ["全体", "所属派閥"], $playerData->getChatmode() === -1 ? 0 : 1);
            $form->addDropdown("スコアボード表示", ["OFF", "ON"], $playerData->getDrawscoreboard());
            $form->addDropdown("プレイヤーの処罰段階", ["なし", "注意", "警告", "一部機能制限", "被処罰プレイヤー一定期間アクセス禁止 or データ消去 or 両方", "被処罰プレイヤーアクセス永久禁止 and データ消去", "被処罰プレイヤーがログインしたことのある全IPアドレスアクセス永久禁止 and データ消去"], $playerData->getPunishment());
            $player->sendForm($form);
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}