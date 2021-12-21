<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\LandData;

use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;
use function array_filter;
use function count;
use function array_shift;

/**
 * 土地データマネージャー
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
        self::$instance = $this;
        $this->land_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("mails.seq",
            [],
            function (array $row) {
                if (count($row) < 1)  {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->waitAll();
        Main::getInstance()->getDatabase()->executeSelect("lands.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->land_datas[$data["id"]] = new LandData($data["id"], $data["faction_id"], $data["x"], $data["z"], $data["world"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
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
     * データ配列を返す
     * @return LandData[]
     */
    public function getLandDatas(): array
    {
        return $this->land_datas;
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
        Main::getInstance()->getDatabase()->executeInsert("lands.create",
            [
                "faction_id" => $faction_id,
                "x" => $x,
                "z" => $z,
                "world" => $world
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        $this->seq++;
        $this->land_datas[$this->seq] = new LandData($this->seq, $faction_id, $x, $z, $world);
    }

    public function delete(int $id)
    {
        Main::getInstance()->getDatabase()->executeGeneric("lands.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->land_datas[$id]);
    }
}