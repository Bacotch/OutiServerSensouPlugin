<?php

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use Ken_Cir\OutiServerSensouPlugin\Entity\Skeleton;
use Ken_Cir\OutiServerSensouPlugin\Entity\Zombie;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

/**
 * エンティティの移動関係タス９
 */
final class EntityMove extends Task
{
    public function __construct()
    {
    }

    public function onRun(int $currentTick)
    {
        // サーバーに湧いている敵性エンティティのターゲット解除
        // 敵性エンティティと5マス以上離れるかクリエイティブモードの時に、解除される
        foreach (Server::getInstance()->getLevels() as $level) {
            foreach (Server::getInstance()->getLevel($level->getId())->getEntities() as $entity) {
                var_dump(get_class($entity));
            }
        }

        // サーバーにいるプレイヤー全員に対しての敵性エンティティターゲット
        // 今のところ敵性エンティティと3マス以内の場所にいる場合はターゲットにされる
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $entity = $this->isThereEntityNonPlayerWithinCircle($player, 3);
            if ($player->getGamemode() === GameMode::SURVIVAL and ($entity instanceof Zombie or $entity instanceof Skeleton)) {
                if (!$entity->hasTarget()) {
                    $entity->setTarget($player);
                }
            }
        }


    }

    private function isThereEntityNonPlayerWithinCircle(Entity $target, float $maxDistance, bool $includeDead = false): ?Entity {
        $minX = ((int)floor($target->x - $maxDistance)) >> 4;
        $maxX = ((int)floor($target->x + $maxDistance)) >> 4;
        $minZ = ((int)floor($target->z - $maxDistance)) >> 4;
        $maxZ = ((int)floor($target->z + $maxDistance)) >> 4;
        $currentTargetDistSq = $maxDistance**2;
        $level = $target->getLevelNonNull();
        for ($x = $minX;$x <= $maxX;++$x) {
            for ($z = $minZ;$z <= $maxZ;++$z) {
                foreach ($level->getChunkEntities($x, $z) as $entity) {
                    if ($entity instanceof Player or $entity->isClosed() or $entity->isFlaggedForDespawn() or (!$includeDead and !$entity->isAlive())) {
                        continue;
                    }
                    $distSq = $entity->distanceSquared($target);
                    if ($distSq < $currentTargetDistSq) {
                        return $entity;
                    }
                }
            }
        }

        return null;
    }

}