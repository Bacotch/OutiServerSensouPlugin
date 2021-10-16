<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Tasks;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

/**
 * 秒実行Task
 */
class PlayerInfoScoreBoard extends Task
{
    /**
     * @var Main
     */
    private Main $plugin;

    /**
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        try {
            // ---スコアボード処理---
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $player_data = $this->plugin->database->getPlayer($player->getName());
                if (!$player_data) continue;
                $this->RemoveData($player);
                if (!(bool)$player_data["drawscoreboard"]) continue;
                $this->setupData($player);
                $this->sendData($player, "§b座標: " . $player->getfloorX() . "," . $player->getfloorY() . "," . $player->getfloorZ(), 1);
                $this->sendData($player, "§bワールド: " . $player->getLevel()->getFolderName(), 2);
                $this->sendData($player, "§c現在時刻: " . date("G時i分s秒"), 3);
                $this->sendData($player, "§6持ってるアイテムid: " . $player->getInventory()->getItemInHand()->getId() . ":" . $player->getInventory()->getItemInHand()->getDamage(), 4);
                $this->sendData($player, "§dPing: " . $player->getPing() . "ms", 5);
                if ($player_data["faction"]) {
                    $faction = $this->plugin->database->getFactionById($player_data["faction"]);
                    $this->sendData($player, "§a所属派閥: " . $faction["name"], 6);
                } else {
                    $this->sendData($player, "§a所属派閥: 無所属", 6);
                }
            }
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    private function setupData(Player $player)
    {
        try {
            $pk = new SetDisplayObjectivePacket();
            $pk->displaySlot = "sidebar";
            $pk->objectiveName = "sidebar";
            $pk->displayName = "§a" . $player->getName();
            $pk->criteriaName = "dummy";
            $pk->sortOrder = 0;
            $player->sendDataPacket($pk);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    private function sendData(Player $player, string $data, int $id)
    {
        try {
            $entry = new ScorePacketEntry();
            $entry->objectiveName = "sidebar";
            $entry->type = $entry::TYPE_FAKE_PLAYER;
            $entry->customName = $data;
            $entry->score = $id;
            $entry->scoreboardId = $id + 11;
            $pk = new SetScorePacket();
            $pk->type = $pk::TYPE_CHANGE;
            $pk->entries[] = $entry;
            $player->sendDataPacket($pk);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    private function RemoveData(Player $player)
    {
        try {
            $pk = new RemoveObjectivePacket();
            $pk->objectiveName = "sidebar";
            $player->sendDataPacket($pk);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }
}