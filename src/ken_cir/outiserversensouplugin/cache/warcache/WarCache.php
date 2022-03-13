<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\warcache;

use pocketmine\player\Player;

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

    public function __construct(int $id, int $remainingTime, array $declarationFactionPlayers, array $enemyFactionPlayers)
    {
        $this->id = $id;
        $this->remainingTime = $remainingTime;
        $this->declarationFactionPlayers = $declarationFactionPlayers;
        $this->enemyFactionPlayers = $enemyFactionPlayers;
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
}