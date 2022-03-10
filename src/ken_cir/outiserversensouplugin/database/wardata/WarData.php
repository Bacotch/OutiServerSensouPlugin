<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\wardata;

use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;

class WarData
{
    private DataConnector $connector;

    private int $id;

    private int $declarationFactionId;

    private int $enemyFactionId;

    private int $startTime;

    private int $started;

    private ?int $winnerFactionId;

    public function __construct(DataConnector $connector, int $id, int $declarationFactionId, int $enemyFactionId, int $startTime, int $started, ?int $winnerFactionId)
    {
        $this->connector = $connector;
        $this->id = $id;
        $this->declarationFactionId = $declarationFactionId;
        $this->enemyFactionId = $enemyFactionId;
        $this->startTime = $startTime;
        $this->started = $started;
        $this->winnerFactionId = $winnerFactionId;
    }

    private function update(): void
    {
        $this->connector->executeChange(
            "outiserver.wars.update",
            [
                "declaration_faction_id" => $this->declarationFactionId,
                "enemy_faction_id" => $this->enemyFactionId,
                "start_time" => $this->startTime,
                "winner_faction_id" => $this->winnerFactionId,
                "id" => $this->id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
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
    public function getDeclarationFactionId(): int
    {
        return $this->declarationFactionId;
    }

    /**
     * @param int $declarationFactionId
     */
    public function setDeclarationFactionId(int $declarationFactionId): void
    {
        $this->declarationFactionId = $declarationFactionId;
        $this->update();
    }

    /**
     * @return int
     */
    public function getEnemyFactionId(): int
    {
        return $this->enemyFactionId;
    }

    /**
     * @param int $enemyFactionId
     */
    public function setEnemyFactionId(int $enemyFactionId): void
    {
        $this->enemyFactionId = $enemyFactionId;
        $this->update();
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     */
    public function setStartTime(int $startTime): void
    {
        $this->startTime = $startTime;
        $this->update();
    }

    /**
     * @return int
     */
    public function getStarted(): bool
    {
        return (bool)$this->started;
    }

    /**
     * @param bool $started
     */
    public function setStarted(bool $started): void
    {
        $this->started = (int)$started;
        $this->update();
    }

    /**
     * @return int|null
     */
    public function getWinnerFactionId(): ?int
    {
        return $this->winnerFactionId;
    }

    /**
     * @param int|null $winnerFactionId
     */
    public function setWinnerFactionId(?int $winnerFactionId): void
    {
        $this->winnerFactionId = $winnerFactionId;
        $this->update();
    }
}