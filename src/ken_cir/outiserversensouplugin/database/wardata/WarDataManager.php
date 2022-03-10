<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\wardata;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_merge;

/**
 * 戦争データマネージャー
 *
 * 依存関係:
 * WarData -> FactionData
 */
class WarDataManager
{
    private DataConnector $connector;

    private static self $instance;

    /**
     * @var WarData[]
     */
    private array $warDatas;

    private int $seq;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->warDatas = [];
        $this->seq = 0;

        $this->connector->executeSelect(
            "outiserver.wars.seq",
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
            "outiserver.wars.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->warDatas[$data["id"]] = new WarData($this->connector,
                        $data["id"],
                        $data["declaration_faction_id"],
                        $data["enemy_faction_id"],
                        $data["start_time"],
                        $data["started"],
                        $data["winner_faction_id"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * @return WarDataManager
     */
    public static function getInstance(): WarDataManager
    {
        return self::$instance;
    }

    public function get(int $id): ?WarData
    {
        if (!isset($this->warDatas[$id])) return null;
        return $this->warDatas[$id];
    }

    /**
     * @return WarData[]
     */
    public function getAll(): array
    {
        return $this->warDatas;
    }

    public function create(int $declarationFactionId, int $enemyFactionId, int $startTime): WarData
    {
        $this->connector->executeInsert(
            "outiserver.wars.create",
            [
                "declaration_faction_id" => $declarationFactionId,
                "enemy_faction_id" => $enemyFactionId,
                "start_time" => $startTime
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        return (new WarData($this->connector, $this->seq, $declarationFactionId, $enemyFactionId, $startTime, 0, null));
    }

    public function delete(int $id): void
    {
        $this->connector->executeGeneric(
            "outiserver.wars.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->warDatas[$id]);
    }

    public function getDeclarationFaction(int $factionId, ?bool $keyValue = false): array
    {
        $warData = array_filter($this->warDatas, function (WarData $warData) use ($factionId) {
            return $warData->getDeclarationFactionId() === $factionId;
        });

        if ($keyValue) return array_values($warData);
        return $warData;
    }

    public function getEnemyFaction(int $factionId, ?bool $keyValue): array
    {
        $warData = array_filter($this->warDatas, function (WarData $warData) use ($factionId) {
            return $warData->getEnemyFactionId() === $factionId;
        });

        if ($keyValue) return array_values($warData);
        return $warData;
    }

    /**
     * @param int $factionId
     * @return WarData[]
     */
    public function getFaction(int $factionId): array
    {
        return array_merge($this->getDeclarationFaction($factionId, true), $this->getEnemyFaction($factionId, true));
    }
}