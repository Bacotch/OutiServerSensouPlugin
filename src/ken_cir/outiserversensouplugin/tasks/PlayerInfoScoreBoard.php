<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use jackmd\scorefactory\ScoreFactory;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use function date;
use function array_shift;

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
        try {
            // ---サーバーにいるプレイヤーを全員取得---
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                // ---スコアボードの描写---
                $player_data = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                $factionLandData = LandDataManager::getInstance()->getChunk((int)$player->getPosition()->getX() >> 4, (int)$player->getPosition()->getZ() >> 4, $player->getWorld()->getFolderName());
                if ($player_data) {
                    ScoreFactory::removeObjective($player);
                    if ($player_data->getDrawscoreboard() === 0) continue;
                    ScoreFactory::setObjective($player, "おうち鯖");
                    ScoreFactory::sendObjective($player);
                    ScoreFactory::setScoreLine($player, 0, "§b座標: {$player->getPosition()->getFloorX()}:{$player->getPosition()->getFloorY()}:{$player->getPosition()->getFloorZ()}");
                    ScoreFactory::setScoreLine($player, 1, "§b今いるワールド: {$player->getWorld()->getFolderName()}");
                    ScoreFactory::setScoreLine($player, 2, "§c現在時刻: " . date("G時i分s秒"));
                    ScoreFactory::setScoreLine($player, 3, "§6持ってるアイテムid: {$player->getInventory()->getItemInHand()->getId()}:{$player->getInventory()->getItemInHand()->getMeta()}");
                    ScoreFactory::setScoreLine($player, 4, "§dPing: {$player->getNetworkSession()->getPing()}ms");
                    if ($player_data->getFaction() === -1) {
                        ScoreFactory::setScoreLine($player, 5, "§a所属派閥: 無所属");
                    } else {
                        $factionData = FactionDataManager::getInstance()->get($player_data->getFaction());
                        ScoreFactory::setScoreLine($player, 5, "§a所属派閥: {$factionData->getName()}");
                        ScoreFactory::setScoreLine($player, 7, "§a派閥資金: {$factionData->getMoney()}");
                    }
                    if (!$factionLandData) {
                        ScoreFactory::setScoreLine($player, 6, "チャンク所有: なし");
                    } else {
                        $landFaction = FactionDataManager::getInstance()->get($factionLandData->getFactionId());
                        ScoreFactory::setScoreLine($player, 6, "チャンク所有: {$landFaction->getName()}");
                    }

                    $nextWarData = WarDataManager::getInstance()->getAll();
                    $nextWarData = array_shift($nextWarData);
                    if (!$nextWarData or !$nextWarData->getStartDay()) {
                        ScoreFactory::setScoreLine($player, 7, "次の戦争はありません");
                    }
                    else {
                        ScoreFactory::setScoreLine($player, 7, "次の戦争開始は{$nextWarData->getStartDay()}日{$nextWarData->getStartHour()}時{$nextWarData->getStartMinutes()}分から " . FactionDataManager::getInstance()->get($nextWarData->getDeclarationFactionId())->getName() . " vs " . FactionDataManager::getInstance()->get($nextWarData->getEnemyFactionId())->getName());
                    }

                    ScoreFactory::sendLines($player);
                }
            }
        } catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }
}
