<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\wardata;

use JetBrains\PhpStorm\Pure;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;

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
                        $data["war_type"],
                        $data["start_day"],
                        $data["start_hour"],
                        $data["start_minutes"],
                        $data["started"]);
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

    public function create(int $declarationFactionId, int $enemyFactionId): WarData
    {
        $this->connector->executeInsert(
            "outiserver.wars.create",
            [
                "declaration_faction_id" => $declarationFactionId,
                "enemy_faction_id" => $enemyFactionId,
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        return ($this->warDatas[$this->seq] = new WarData($this->connector, $this->seq, $declarationFactionId, $enemyFactionId, null, null, null, null, 0));
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

    #[Pure]
    public function getDeclarationFaction(int $factionId): ?WarData
    {
        foreach ($this->warDatas as $warData) {
            if ($warData->getDeclarationFactionId() === $factionId) return $warData;
        }

        return null;
    }

    #[Pure]
    public function getEnemyFaction(int $factionId): ?WarData
    {
        foreach ($this->warDatas as $warData) {
            if ($warData->getEnemyFactionId() === $factionId) return $warData;
        }

        return null;
    }
}