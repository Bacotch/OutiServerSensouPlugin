<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use JetBrains\PhpStorm\Pure;
use ken_cir\outiserversensouplugin\cache\warcache\WarCacheManager;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\wardata\WarData;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use function date;

/**
 *
 */
class WarCheckerTask extends Task
{
    public function onRun(): void
    {
        foreach (WarDataManager::getInstance()->getAll() as $warData) {
            if ($warData->getStartDay() <= (int)date("d") and $warData->getStartHour() <= (int)date("H") and $warData->getStartMinutes() <= (int)date("i") and !$warData->isStarted()) {
                $declarationOnlinePlayer = array_filter(Server::getInstance()->getOnlinePlayers(), function (Player $player) use ($warData) {
                    return !!PlayerDataManager::getInstance()->getXuid($player->getXuid()) and PlayerDataManager::getInstance()->getXuid($player->getXuid())->getFaction() === $warData->getDeclarationFactionId();
                });
                $enemyOnlinePlayer = array_filter(Server::getInstance()->getOnlinePlayers(), function (Player $player) use ($warData) {
                    return !!PlayerDataManager::getInstance()->getXuid($player->getXuid()) and PlayerDataManager::getInstance()->getXuid($player->getXuid())->getFaction() === $warData->getEnemyFactionId();
                });
                WarCacheManager::getInstance()->create($warData->getId(),
                900,
                $declarationOnlinePlayer,
                $enemyOnlinePlayer);

                $declarationFaction = FactionDataManager::getInstance()->get($warData->getDeclarationFactionId());
                $enemyFaction = FactionDataManager::getInstance()->get($warData->getEnemyFactionId());

                foreach ($declarationOnlinePlayer as $player) {
                    $player->sendTitle("戦争開始！", "{$declarationFaction->getName()} VS {$enemyFaction->getName()}", -1, 60);
                    $pk = new PlaySoundPacket;
                    $pk->soundName = "raid.horn";
                    $pk->x = $player->getPosition()->getX();
                    $pk->y = $player->getPosition()->getY();
                    $pk->z = $player->getPosition()->getZ();
                    $pk->volume = 50;
                    $pk->pitch = 1;
                    $player->getNetworkSession()->sendDataPacket($pk);
                }

                $warData->setStarted(true);
                Server::getInstance()->broadcastMessage("§a[システム] {$declarationFaction->getName()} VS {$enemyFaction->getName()}の戦争が開始しました！");
                Main::getInstance()->getOutiServerLogger()->debug("Starting War {$declarationFaction->getName()} VS {$enemyFaction->getName()}");
                Main::getInstance()->getOutiServerLogger()->debug("Added WarManager");
            }
        }
    }
}