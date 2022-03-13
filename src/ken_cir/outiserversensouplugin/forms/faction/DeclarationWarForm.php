<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use DateTime;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\wardata\WarData;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use function array_filter;
use function array_map;
use function time;
use function date;
use function array_values;

class DeclarationWarForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            // 宣戦布告済み -> 宣戦布告取り消し
            // 宣戦布告を受けた -> 宣戦布告を承諾するか選択
            // 何もしてない -> ↓
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());
            $warDeclarationData = WarDataManager::getInstance()->getDeclarationFaction($factionData->getId());
            $warEnemyData = WarDataManager::getInstance()->getEnemyFaction($factionData->getId());

            // 宣戦布告済みで、開始してなければそういうこと
            if ($warDeclarationData and !$warDeclarationData->isStarted()) {
                $this->activeDeclaration($player ,$factionData, $warDeclarationData);
            }
            // 宣戦布告を受けて、最後のが開始してなければ
            elseif ($warEnemyData and !$warEnemyData->isStarted()) {
                $this->receivedDeclaration($player, $factionData, $warEnemyData);
            }
            // それ以外(Normal)
            else {
                $enemyFactions = array_values(array_filter(FactionDataManager::getInstance()->getAll(), function (FactionData $enemyFactionData) use ($factionData) {
                    return $enemyFactionData->getId() !== $factionData->getId();
                }));
                if (count($enemyFactions) < 1) {
                    $player->sendMessage("§a[システム] 宣戦布告できる国が1つもないようです");
                    return;
                }

                $form = new CustomForm(function (Player $player, $data) use ($enemyFactions, $factionData) {
                    try {
                        if ($data === null) return;
                        elseif ($data[0]) {
                            (new FactionForm())->execute($player);
                            return;
                        }

                        $enemyFaction = $enemyFactions[$data[1]];
                        WarDataManager::getInstance()->create($factionData->getId(),
                            $enemyFaction->getId());

                        MailDataManager::getInstance()->create($enemyFaction->getOwnerXuid(),
                            "宣戦布告されました",
                            "あなたの派閥は{$factionData->getName()}から宣戦布告を受けました\n\n宣戦布告国からのメッセージ\n$data[2]",
                            "システム",
                            (new DateTime('now'))->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] {$enemyFaction->getName()}に宣戦布告をしました、相手の反応を待ちます。");
                    }
                    catch (\Error|\Exception $e) {
                        Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                    }
                });

                $enemyFactionNames = array_map(function (FactionData $factionData) {
                    return $factionData->getName();
                }, $enemyFactions);
                $form->setTitle("宣戦布告");
                $form->addToggle("キャンセルして戻る");
                $form->addDropdown("相手国", $enemyFactionNames);
                $form->addInput("宣戦布告のメッセージ(空でも可)", "msg");
                $player->sendForm($form);
            }
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    /**
     * 宣戦布告済みの場合
     *
     * @param Player $player
     * @param FactionData $factionData
     * @param WarData $warDeclarationData
     * @return void
     */
    public function activeDeclaration(Player $player, FactionData $factionData, WarData $warDeclarationData): void
    {
        try {
            $enemyFaction = FactionDataManager::getInstance()->get($warDeclarationData->getEnemyFactionId());

            $form = new ModalForm(function (Player $player, $data) use ($factionData, $enemyFaction, $warDeclarationData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        (new FactionForm())->execute($player);
                    }
                    elseif ($data === false) {
                        WarDataManager::getInstance()->delete($warDeclarationData->getId());
                        MailDataManager::getInstance()->create($enemyFaction->getOwnerXuid(),
                            "宣戦布告が取り消されました",
                            "{$factionData->getName()}からの宣戦布告が取り消されました",
                            "システム",
                            (new DateTime('now'))->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] 宣戦布告を取り消しました");
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("宣戦布告");
            $form->setContent("アクティブな宣戦布告があります！\n\n相手国: {$enemyFaction->getName()}");
            $form->setButton1("戻る");
            $form->setButton2("宣戦布告を取り消し");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    /**
     * 宣戦布告を受け取った場合
     *
     * @param Player $player
     * @param FactionData $factionData
     * @param WarData $warEnemyData
     * @return void
     */
    public function receivedDeclaration(Player $player, FactionData $factionData, WarData $warEnemyData): void
    {
        try {
            $enemyFaction = FactionDataManager::getInstance()->get($warEnemyData->getDeclarationFactionId());

            $form = new SimpleForm(function (Player $player, $data) use ($enemyFaction, $warEnemyData, $factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        (new FactionForm())->execute($player);
                    }
                    elseif ($data === 1) {
                        $this->startCheckWar($player, $factionData, $enemyFaction, $warEnemyData);
                    }
                    elseif ($data === 2) {
                        WarDataManager::getInstance()->delete($warEnemyData->getId());
                        MailDataManager::getInstance()->create($enemyFaction->getOwnerXuid(),
                            "宣戦布告が拒否されました",
                            "{$factionData->getName()}に対する宣戦布告は拒否されました",
                            "システム",
                            (new DateTime('now'))->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] {$enemyFaction->getName()}の宣戦布告を拒否しました");
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("宣戦布告");
            $form->setContent("宣戦布告を受けています、承諾しますか？\n\n相手国: {$enemyFaction->getName()}");
            $form->addButton("戻る");
            $form->addButton("承諾");
            $form->addButton("拒否");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    /**
     * 宣戦布告を了承する場合の設定など
     *
     * @param Player $player
     * @return void
     */
    public function startCheckWar(Player $player, FactionData $factionData, FactionData $enemyFaction, WarData $warData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($warData, $factionData, $enemyFaction) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->receivedDeclaration($player, $factionData, $warData);
                        return;
                    }
                    elseif (!is_numeric($data[3]) or !is_numeric($data[4])) {
                        $player->sendMessage("§a[システム] 開戦時間と開戦分は入力必須項目で、数値である必要があります");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "startCheckWar"], [$player, $factionData, $enemyFaction, $warData]), 10);
                    }

                    $warData->setWarType($data[1]);
                    $warData->setStartDay((int)date("d", (($data[2] + 1) * 86400) + time()));
                    $warData->setStartHour((int)$data[3]);
                    $warData->setStartMinutes((int)$data[4]);
                    Main::getInstance()->getOutiServerLogger()->plugin("戦争情報: {$factionData->getName()} vs {$enemyFaction->getName()}\n開始時刻: " . date("d", (($data[2] + 1) * 86400) + time()) . "日$data[3]時$data[4]分");
                    MailDataManager::getInstance()->create($enemyFaction->getOwnerXuid(),
                    "宣戦布告が承認されました",
                    "{$factionData->getName()}に対する宣戦布告が承認されました\n開始時刻: " . date("d", (($data[2] + 1) * 86400) + time()) . "日$data[3]時$data[4]分\n\n敵派閥よりメッセージ: $data[5]",
                    "システム",
                        (new DateTime('now'))->format("Y年m月d日 H時i分"));
                    $player->sendMessage("§a[システム] 宣戦布告を承認しました、開始時刻は" . "日$data[3]時$data[4]分です");
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("宣戦布告 承諾");
            $form->addToggle("キャンセルして戻る");
            $form->addDropdown("戦争タイプ", ["壊滅戦"]);
            $form->addDropdown("準備期間(日)", ["1日", "2日", "3日", "4日", "5日", "61日", "7日"]);
            $form->addInput("開戦時間", "hour");
            $form->addInput("開戦分", "minutes");
            $form->addInput("相手の派閥に対してのメッセージなど(空でも可)", "factionMsg");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}