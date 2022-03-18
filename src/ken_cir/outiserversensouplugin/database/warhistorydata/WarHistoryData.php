<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\warhistorydata;

use JetBrains\PhpStorm\ArrayShape;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;

class WarHistoryData
{
    private DataConnector $connector;

    private int $id;

    private int $winnerFactionId;

    private int $loserFactionId;

    private int $time;

    public function __construct(DataConnector $connector, int $id, int $winnerFactionId, int $loserFactionId, int $time)
    {
        $this->connector = $connector;
        $this->id = $id;
        $this->winnerFactionId = $winnerFactionId;
        $this->loserFactionId = $loserFactionId;
        $this->time = $time;
    }

    #[ArrayShape(["id" => "int", "winnerFactionId" => "int", "loserFactionId" => "int", "time" => "int"])]
    public function toArray(): array
    {
        return array(
            "id" => $this->id,
            "winnerFactionId" => $this->winnerFactionId,
            "loserFactionId" => $this->loserFactionId,
            "time" => $this->time
        );
    }

    private function update(): void
    {
        $this->connector->executeChange(
            "outiserver.war_historys.update",
            [
                "winner_faction_id" => $this->winnerFactionId,
                "loser_faction_id" => $this->loserFactionId,
                "time" => $this->time,
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
    public function getWinnerFactionId(): int
    {
        return $this->winnerFactionId;
    }

    /**
     * @param int $winnerFactionId
     */
    public function setWinnerFactionId(int $winnerFactionId): void
    {
        $this->winnerFactionId = $winnerFactionId;
        $this->update();
    }

    /**
     * @return int
     */
    public function getLoserFactionId(): int
    {
        return $this->loserFactionId;
    }

    /**
     * @param int $loserFactionId
     */
    public function setLoserFactionId(int $loserFactionId): void
    {
        $this->loserFactionId = $loserFactionId;
        $this->update();
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
        $this->update();
    }
}