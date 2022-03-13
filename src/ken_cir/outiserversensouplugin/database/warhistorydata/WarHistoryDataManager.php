<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\warhistorydata;

use ken_cir\outiserversensouplugin\database\wardata\WarData;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;

class WarHistoryDataManager
{
    private DataConnector $connector;

    private static self $instance;

    private array $warhistoryDatas;

    private int $seq;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->warhistoryDatas = [];
        $this->seq = 0;

        $this->connector->executeSelect(
            "outiserver.war_historys.seq",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->connector->executeSelect(
            "outiserver.war_historys.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->warhistoryDatas[$data["id"]] = new WarHistoryData($this->connector,
                        $data["id"],
                        $data["winner_faction_id"],
                        $data["loser_faction_id"],
                        $data["time"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * @return WarHistoryDataManager
     */
    public static function getInstance(): WarHistoryDataManager
    {
        return self::$instance;
    }

    public function get(int $id): ?WarHistoryData
    {
        if (!isset($this->warhistoryDatas[$id])) return null;
        return $this->warhistoryDatas[$id];
    }

    /**
     * @return WarHistoryData[]
     */
    public function getAll(): array
    {
        return $this->warhistoryDatas;
    }

    public function create(int $winnerFactionId, int $loserFactionId, int $time): WarHistoryData
    {
        $this->connector->executeInsert(
            "outiserver.war_historys.create",
            [
                "winner_faction_id" => $winnerFactionId,
                "loser_faction_id" => $loserFactionId,
                "time" => $time
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        return ($this->warhistoryDatas[$this->seq] = new WarHistoryData($this->connector, $this->seq, $winnerFactionId, $loserFactionId, $time));
    }

    public function delete(int $id): void
    {
        if (!$this->get($id)) return;

        $this->connector->executeGeneric(
            "outiserver.war_historys.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->warhistoryDatas[$id]);
    }


}