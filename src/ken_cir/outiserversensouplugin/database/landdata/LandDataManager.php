<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landdata;

use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_shift;
use function array_values;
use function count;

/**
 * 土地データマネージャー
 *
 * 依存関係:
 * LandData <- LandConfigData
 * LandData -> FactionData
 */
class LandDataManager
{
    /**
     * インスタンス
     * @var LandDataManager $this
     */
    private static self $instance;

    /**
     * @var LandData[]
     */
    private array $land_datas;

    /**
     * 現在の管理用ID
     * @var int
     */
    private int $seq;

    public function __construct()
    {
        $this->land_datas = [];
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.lands.seq",
            [],
            function (array $row) {
                if (count($row) < 1) {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.lands.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->land_datas[$data["id"]] = new LandData($data["id"], $data["faction_id"], $data["x"], $data["z"], $data["world"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(LandDataManager::class);
        self::$instance = new self();
    }

    /**
     * インスタンスを返す
     * @return LandDataManager
     */
    public static function getInstance(): LandDataManager
    {
        return self::$instance;
    }

    /**
     * idと一致するデータを返す
     * @param int $id
     * @return LandData|false
     */
    public function get(int $id): LandData|false
    {
        if (!isset($this->land_datas[$id])) return false;
        return $this->land_datas[$id];
    }

    /**
     * @param int $factionId
     * @param bool|null $keyValue
     * @return LandData[]
     */
    public function getFactionLands(int $factionId, ?bool $keyValue = false): array
    {
        $factionLands = array_filter($this->land_datas, function (LandData $landData) use ($factionId) {
            return $landData->getFactionId() === $factionId;
        });

        if ($keyValue) return array_values($factionLands);
        return $factionLands;
    }

    /**
     * @param int $x
     * @param int $z
     * @param string $world
     * @return LandData|false
     */
    public function getChunk(int $x, int $z, string $world): LandData|false
    {
        $landData = array_filter($this->land_datas, function ($landData) use ($x, $z, $world) {
            return ($landData->getX() === $x && $landData->getZ() === $z && $landData->getWorld() === $world);
        });

        if (count($landData) < 1) return false;
        return array_shift($landData);
    }

    /**
     * @param int $x
     * @param int $z
     * @param string $world
     * @return bool
     */
    public function hasChunk(int $x, int $z, string $world): bool
    {
        $landData = array_filter($this->land_datas, function ($landData) use ($x, $z, $world) {
            return ($landData->getX() === $x && $landData->getZ() === $z && $landData->getWorld() === $world);
        });
        if (count($landData) < 1) return false;
        return true;
    }

    /**
     * データを追加する
     * @param int $faction_id
     * @param int $x
     * @param int $z
     * @param string $world
     * @return void
     */
    public function create(int $faction_id, int $x, int $z, string $world): void
    {
        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.lands.create",
            [
                "faction_id" => $faction_id,
                "x" => $x,
                "z" => $z,
                "world" => $world
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->seq++;
        $this->land_datas[$this->seq] = new LandData($this->seq, $faction_id, $x, $z, $world);
    }

    public function delete(int $id)
    {
        if (!$deleteLandData = $this->get($id)) return;

        // 該当の土地保護データを削除する
        foreach (LandConfigDataManager::getInstance()->getLandConfigs($deleteLandData->getId()) as $landConfigData) {
            LandConfigDataManager::getInstance()->delete($landConfigData->getId());
        }

        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.lands.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        unset($this->land_datas[$id]);
    }
}
