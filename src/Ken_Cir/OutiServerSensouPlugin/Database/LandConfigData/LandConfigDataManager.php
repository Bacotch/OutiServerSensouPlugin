<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData;

use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;

class LandConfigDataManager
{
    /**
     * インスタンス
     * @var LandConfigDataManager $this
     */
    private static self $instance;

    /**
     * 土地コンフィグデータ
     * @var LandConfigData[]
     */
    private array $landConfigDatas;

    /**
     * 管理用ID
     * @var int
     */
    private int $seq;

    public function __construct()
    {
        self::$instance = $this;
        $this->landConfigDatas = [];
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.landconfigs.seq",
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
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        Main::getInstance()->getDatabase()->waitAll();
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.landconfigs.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->landConfigDatas[$data["id"]] = new LandConfigData($data["id"], $data["landid"], $data["startx"], $data["startz"], $data["endx"], $data["endz"], $data["perms"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
    }

    /**
<<<<<<< HEAD
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) return;
        self::$instance = new LandConfigDataManager();
    }

    /**
=======
>>>>>>> efe6a50 (土地保護詳細設定進捗)
     * @return LandConfigDataManager
     */
    public static function getInstance(): LandConfigDataManager
    {
        return self::$instance;
    }

    /**
     * @return LandConfigData[]
     */
    public function getLandConfigDatas(): array
    {
        return $this->landConfigDatas;
    }

    /**
     * @param int $id
     * @return LandConfigData|false
     */
    public function get(int $id): LandConfigData|false
    {
        if (!isset($this->landConfigDatas[$id])) return false;
        return $this->landConfigDatas[$id];
    }

    public function create(int $landid, int $startx, int $startz, int $endx, int $endz, array $perms): void
    {
        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.landconfigs.create",
            [
                "landid" => $landid,
                "startx" => $startx,
                "startz" => $startz,
                "endx" => $endx,
                "endz" => $endz,
                "perms" => serialize($perms)
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        $this->seq++;
        $this->landConfigDatas[$this->seq] = new LandConfigData($this->seq, $landid, $startx, $startz, $endx, $endz, serialize($perms));
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.landconfigs.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->landConfigDatas[$id]);
    }

    /**
     * @param int $landid
     * @return void
     */
    public function deleteLand(int $landid): void
    {
        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.landconfigs.delete_land",
            [
                "landid" => $landid
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );

        $this->landConfigDatas = array_filter($this->landConfigDatas, function ($landConfigData) use ($landid) {
            return $landConfigData->getLandid() !== $landid;
        });
    }
}