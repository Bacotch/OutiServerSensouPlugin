<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\wardata;

use JetBrains\PhpStorm\ArrayShape;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;

class WarData
{
    private DataConnector $connector;

    private int $id;

    private int $declarationFactionId;

    private int $enemyFactionId;

    private ?int $warType;

    private ?int $startDay;

    private ?int $startHour;

    private ?int $startMinutes;

    private int $started;

    public function __construct(DataConnector $connector, int $id, int $declarationFactionId, int $enemyFactionId, ?int $warType, ?int $startDay, ?int $startHour, ?int $startMinutes, int $started)
    {
        $this->connector = $connector;
        $this->id = $id;
        $this->declarationFactionId = $declarationFactionId;
        $this->enemyFactionId = $enemyFactionId;
        $this->warType = $warType;
        $this->startDay = $startDay;
        $this->startHour = $startHour;
        $this->startMinutes = $startMinutes;
        $this->started = $started;
    }



    #[ArrayShape(["id" => "int", "declarationFactionId" => "int", "enemyFactionId" => "int", "warType" => "int|null", "startDay" => "int|null", "startHour" => "int|null", "startMinutes" => "int|null", "started" => "int"])]
    public function toArray(): array
    {
        return array(
            "id" => $this->id,
            "declarationFactionId" => $this->declarationFactionId,
            "enemyFactionId" => $this->enemyFactionId,
            "warType" => $this->warType,
            "startDay" => $this->startDay,
            "startHour" => $this->startHour,
            "startMinutes" => $this->startMinutes,
            "started" => $this->started
        );
    }

    private function update(): void
    {
        $this->connector->executeChange(
            "outiserver.wars.update",
            [
                "declaration_faction_id" => $this->declarationFactionId,
                "enemy_faction_id" => $this->enemyFactionId,
                "war_type" => $this->warType,
                "start_day" => $this->startDay,
                "start_hour" => $this->startHour,
                "start_minutes" => $this->startMinutes,
                "started" => $this->started,
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
     * @return int|null
     */
    public function getWarType(): ?int
    {
        return $this->warType;
    }

    /**
     * @param int|null $warType
     */
    public function setWarType(?int $warType): void
    {
        $this->warType = $warType;
        $this->update();
    }

    /**
     * @return int|null
     */
    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    /**
     * @param int|null $startDay
     */
    public function setStartDay(?int $startDay): void
    {
        $this->startDay = $startDay;
        $this->update();
    }

    /**
     * @return int|null
     */
    public function getStartHour(): ?int
    {
        return $this->startHour;
    }

    /**
     * @param int|null $startHour
     */
    public function setStartHour(?int $startHour): void
    {
        $this->startHour = $startHour;
        $this->update();
    }

    /**
     * @return int|null
     */
    public function getStartMinutes(): ?int
    {
        return $this->startMinutes;
    }

    /**
     * @param int|null $startMinutes
     */
    public function setStartMinutes(?int $startMinutes): void
    {
        $this->startMinutes = $startMinutes;
        $this->update();
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
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
}