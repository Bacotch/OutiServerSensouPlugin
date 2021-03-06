<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\warcache;

use JetBrains\PhpStorm\Pure;
use pocketmine\player\Player;
use pocketmine\world\sound\XpLevelUpSound;

class WarCache
{
    private int $id;

    private int $remainingTime;

    /**
     * @var Player[]
     */
    private array $declarationFactionPlayers;

    /**
     * @var Player[]
     */
    private array $enemyFactionPlayers;

    /**
     * キルリーダー
     *
     * @var Player|null
     */
    private ?Player $killLeader;

    /**
     * キル数をカウントする配列
     * @var array
     */
    private array $killCounts;

    public function __construct(int $id, int $remainingTime, array $declarationFactionPlayers, array $enemyFactionPlayers)
    {
        $this->id = $id;
        $this->remainingTime = $remainingTime;
        $this->declarationFactionPlayers = $declarationFactionPlayers;
        $this->enemyFactionPlayers = $enemyFactionPlayers;
        $this->killLeader = null;
        $this->killCounts = [];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getRemainingTime(): int
    {
        return $this->remainingTime;
    }

    /**
     * @param int $remainingTime
     */
    public function setRemainingTime(int $remainingTime): void
    {
        $this->remainingTime = $remainingTime;
    }

    /**
     * @return Player[]
     */
    public function getDeclarationFactionPlayers(): array
    {
        return $this->declarationFactionPlayers;
    }

    /**
     * @param Player[] $declarationFactionPlayers
     */
    public function setDeclarationFactionPlayers(array $declarationFactionPlayers): void
    {
        $this->declarationFactionPlayers = $declarationFactionPlayers;
    }

    #[Pure]
    public function hasDeclarationFactionPlayer(string $xuid): bool
    {
        foreach ($this->declarationFactionPlayers as $player) {
            if ($player->getXuid() === $xuid) return true;
        }

        return false;
    }

    public function addDeclarationFactionPlayer(Player $player): void
    {
        if ($this->hasDeclarationFactionPlayer($player->getXuid())) return;
        $this->declarationFactionPlayers[] = $player;
    }

    public function removeDeclarationFactionPlayer(Player $player): void
    {
        foreach ($this->declarationFactionPlayers as $key => $declarationPlayer) {
            if ($declarationPlayer->getXuid() === $player->getXuid()) {
                unset($this->declarationFactionPlayers[$key]);
            }
        }
    }

    /**
     * @return Player[]
     */
    public function getEnemyFactionPlayers(): array
    {
        return $this->enemyFactionPlayers;
    }

    /**
     * @param Player[] $enemyFactionPlayers
     */
    public function setEnemyFactionPlayers(array $enemyFactionPlayers): void
    {
        $this->enemyFactionPlayers = $enemyFactionPlayers;
    }

    #[Pure]
    public function hasEnemyFactionPlayer(string $xuid): bool
    {
        foreach ($this->enemyFactionPlayers as $player) {
            if ($player->getXuid() === $xuid) return true;
        }

        return false;
    }

    public function addEnemyFactionPlayer(Player $player): void
    {
        if ($this->hasEnemyFactionPlayer($player->getXuid())) return;
        $this->enemyFactionPlayers[] = $player;
    }

    public function removeEnemyFactionPlayer(Player $player): void
    {
        foreach ($this->enemyFactionPlayers as $key => $enemyFactionPlayer) {
            if ($enemyFactionPlayer->getXuid() === $player->getXuid()) {
                unset($this->enemyFactionPlayers[$key]);
            }
        }
    }

    /**
     * @return Player|null
     */
    public function getKillLeader(): ?Player
    {
        return $this->killLeader;
    }

    /**
     * @param Player|null $killLeader
     */
    public function setKillLeader(?Player $killLeader): void
    {
        $this->killLeader = $killLeader;
    }

    /**
     * @param string $xuid
     * @return int|null
     */
    public function getKillCount(string $xuid): ?int
    {
        if (!isset($this->killCounts[$xuid])) return null;
        return $this->killCounts[$xuid];
    }

    /**
     * @param string $xuid
     * @param int $killCount
     */
    public function setKillCount(string $xuid, int $killCount): void
    {
        $this->killCounts[$xuid] = $killCount;
    }
}