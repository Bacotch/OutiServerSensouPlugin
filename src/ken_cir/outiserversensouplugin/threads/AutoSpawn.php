<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\threads;

use ken_cir\outiserversensouplugin\entitys\Skeleton;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Zombie;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;

final class AutoSpawn extends Task
{
    const HOSTILE_CAP_CONSTANT = 70;
    const PASSIVE_WET_CAP_CONSTANT = 10;
    const PASSIVE_DRY_CAP_CONSTANT = 15;
    const AMBIENT_CAP_CONSTANT = 5;

    public function __construct()
    {
    }

    public function onRun(): void
    {
        // ワールドを全部取得してforeachで回す
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            if (!$world->isLoaded()) continue;

            $mobs = count($world->getEntities());

            $playerLocations = [];
            if (count($world->getPlayers()) > 0) {
                foreach ($world->getPlayers() as $player) {
                    if ($player->spawned) {
                        $playerLocations[] = $player->getPosition();
                    }
                }
            }

            $spawnMap = $this->generateSpawnMap($playerLocations);
            if (($totalChunks = count($spawnMap)) > 0) {
                $hostileCap = self::HOSTILE_CAP_CONSTANT * $totalChunks / 256;
                $passiveDryCap = self::PASSIVE_DRY_CAP_CONSTANT * $totalChunks / 256;
                $passiveWetCap = self::PASSIVE_WET_CAP_CONSTANT * $totalChunks / 256;
                $ambientCap = self::AMBIENT_CAP_CONSTANT * $totalChunks / 256;

                foreach ($spawnMap as $chunk) {
                    // TODO Find source of null chunks
                    if ($chunk !== null) {
                        $this->spawnHostileMob($chunk["chunk"], $world, $chunk["x"], $chunk["z"]);
                    }
                }
            }
        }
    }


    private function generateSpawnMap(array $playerLocations): array
    {
        $convertedChunkList = [];
        $convertedChunkXZList = [];
        $spawnMap = [];

        if (count($playerLocations) > 0) {
            // This will take the location of each player, determine what chunk
            // they are in, and store the chunk in $convertedChunkList.

            /**
             * @var Position $playerPos
             */
            foreach ($playerLocations as $playerPos) {

                $chunkHash = World::chunkHash($playerPos->x >> 4, $playerPos->z >> 4);

                // If the chunk is already in the list, there's no need to add it again.
                if (!isset($convertedChunkList[$chunkHash])) {
                    $convertedChunkList[$chunkHash] = $playerPos->getWorld()->getChunk($playerPos->getFloorX() >> 4, $playerPos->getFloorZ() >> 4);
                    $convertedChunkXZList[$chunkHash] = array(
                        "x" => $playerPos->getFloorX() >> 4,
                        "z" => $playerPos->getFloorZ() >> 4
                    );
                }
            }

            /**
             * Add a 15x15 group of chunks centered around each player to the spawn map.
             * This will avoid adding duplicate chunks when players are in close proximity
             * to one another.
             *
             * @var Chunk $chunk
             */
            foreach ($convertedChunkList as $key => $chunk) {
                for ($x = -7; $x <= 7; $x++) {
                    for ($z = -7; $z <= 7; $z++) {
                        $chunk->getHeightMapArray();
                        $trialX = $convertedChunkXZList[$key]["x"] + $x;
                        $trialZ = $convertedChunkXZList[$key]["z"] + $z;
                        if (!isset($spawnMap[$key])) {
                            $spawnMap[$key] = array(
                                "chunk" => $chunk,
                                "x" => $convertedChunkXZList[$key]["x"],
                                "z" => $convertedChunkXZList[$key]["x"]
                            );
                        }
                    }
                }
            }
        }
        return $spawnMap;
    }

    private function getRandomLocationInChunk(Vector2 $chunk): Vector3
    {
        $x = mt_rand((int)$chunk->x * 16, (int)(($chunk->x * 16) + 15));
        $y = mt_rand(65, 70);
        $z = mt_rand((int)$chunk->y * 16, (int)(($chunk->y * 16) + 15));

        return new Vector3($x, $y, $z);
    }

    private function isValidPackCenter(Vector3 $center, World $world): bool
    {
        if ($world->getBlockAt($center->getFloorX(), $center->getFloorY(), $center->getFloorZ())->isTransparent()) {
            return true;
        } else {
            return false;
        }
    }

    protected function spawnPackToLevel(Vector3 $center, int $entityId, World $world, string $type, bool $isBaby = false)
    {
        $maxPackSize = 4;
        $currentPackSize = 0;

        for ($attempts = 0; $attempts <= 12 and $currentPackSize < $maxPackSize; $attempts++) {
            $x = mt_rand(-20, 20) + $center->x;
            $z = mt_rand(-20, 20) + $center->z;
            $pos = new Position($x, $center->y, $z, $world);

            $success = $this->scheduleCreatureSpawn($pos, $entityId, $world, $type, $isBaby) !== null;
            if ($success) {
                $currentPackSize++;
            }
        }

        Server::getInstance()->broadcastMessage("DEBUG: 合計{$currentPackSize}のMobの自動スポーンに成功しました");
    }

    public function scheduleCreatureSpawn(Position $pos, int $entityid, World $level, string $type, bool $baby = false, Entity $parentEntity = null, Player $owner = null): Zombie|Skeleton|null
    {
        $entity = $this->create($entityid, $pos);
        if ($entity !== null) {
            $entity->spawnToAll();

            return $entity;
        }

        return null;
    }

    public function create(int $type, Position $source): Zombie|Skeleton|null
    {
        return match ($type) {
            32 => new Zombie(Location::fromObject($source, $source->getWorld(), lcg_value() * 360, 0)),
            34 => new Skeleton(Location::fromObject($source, $source->getWorld(), lcg_value() * 360, 0)),
            default => null,
        };
    }

    private function isValidSpawnLocation(Position $spawnLocation): bool
    {
        if (!$spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y, $spawnLocation->z)->isTransparent()
            and $spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y + 1, $spawnLocation->z)->isTransparent()
            and $spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y + 2, $spawnLocation->z)->isTransparent()) {
            return true;
        }
        return false;
    }

    // 水の上スポーン対策(あの時の悲劇を繰り返さないために)
    private function isValidDrySpawnLocation(Position $spawnLocation): bool
    {
        if (!$spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y - 1, $spawnLocation->z)->isTransparent()
            and ($spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y, $spawnLocation->z)->isTransparent() and
                $spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y, $spawnLocation->z)->getId() !== VanillaBlocks::WATER()->getId())
            and ($spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y + 1, $spawnLocation->z)->isTransparent()
                and $spawnLocation->getWorld()->getBlockAt($spawnLocation->x, $spawnLocation->y + 1, $spawnLocation->z)->getId() !== VanillaBlocks::WATER()->getId())
        ) {
            return true;
        }
        return false;
    }

    private function spawnHostileMob(Chunk $chunk, World $level, int $x, int $z)
    {
        $packCenter = $this->getRandomLocationInChunk(new Vector2($x, $z));
        $lightLevel = $level->getFullLightAt($packCenter->getFloorX(), $packCenter->getFloorY(), $packCenter->getFloorZ());
        if ($this->isValidPackCenter($packCenter, $level) and $lightLevel < 7) {
            $mobId = array_rand([32, 34]);
            $this->spawnPackToLevel($packCenter, [32, 34][$mobId], $level, "hostile");
        }
    }
}