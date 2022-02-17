<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

/**
 * プレイヤーの詳細を表示するスコアボード
 */
class PlayerInfoScoreBoard extends Task
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        // ---サーバーにいるプレイヤーを全員取得---
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            // ---スコアボードの描写---
            $player_data = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionLandData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
            if ($player_data) {
                $this->deleteInfo($player);
                if ($player_data->getDrawscoreboard() === 0) continue;
                $this->setupInfo($player, "おうち鯖");
                $this->addInfo($player, "§b座標: " . $player->getPosition()->getFloorX() . "," . $player->getPosition()->getFloorY() . "," . $player->getPosition()->getFloorZ(), 1, 1);
                $this->addInfo($player, "§bワールド: " . $player->getWorld()->getFolderName(), 2, 2);
                $this->addInfo($player, "§c現在時刻: " . date("G時i分s秒"), 3, 3);
                $this->addInfo($player, "§6持ってるアイテムid: " . $player->getInventory()->getItemInHand()->getId() . ":" . $player->getInventory()->getItemInHand()->getMeta(), 4, 4);
                $this->addInfo($player, "§dPing: " . $player->getNetworkSession()->getPing() . "ms", 5, 5);
                $this->addInfo($player, "所持金: {$player_data->getMoney()}円", 6, 6);
                if ($player_data->getFaction() === -1) {
                    $this->addInfo($player, "§a所属派閥: 無所属", 7, 7);
                } else {
                    $faction_data = FactionDataManager::getInstance()->get($player_data->getFaction());
                    $this->addInfo($player, "§a所属派閥: {$faction_data->getName()}", 7, 7);
                }
                if (!$factionLandData) {
                    $this->addInfo($player, "チャンク所有: なし", 8, 8);
                } else {
                    $landFaction = FactionDataManager::getInstance()->get($factionLandData->getFactionId());
                    $this->addInfo($player, "チャンク所有: {$landFaction->getName()}", 8, 8);
                }
            }
        }
    }

    // ここから
    // ---スコアボード系用の処理---
    /**
     * スコアボードをセットアップする
     *
     * @param Player $player
     * @param string $displayName
     * @return void
     */
    private function setupInfo(Player $player, string $displayName)
    {
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = "sidebar";
        $pk->displayName = $displayName;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * 詳細を追加する
     *
     * @param Player $player
     * @param string $info
     * @param int $scoreId
     * @param int $scoreboardId
     * @return void
     */
    private function addInfo(Player $player, string $info, int $scoreId, int $scoreboardId)
    {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = "sidebar";
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = $info;
        $entry->score = $scoreId;
        $entry->scoreboardId = $scoreboardId;

        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * プレイヤーの詳細を表示するスコアボードを消す
     *
     * @param Player $player
     * @return void
     */
    private function deleteInfo(Player $player)
    {
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = "sidebar";
        $player->getNetworkSession()->sendDataPacket($pk);
    }
}
