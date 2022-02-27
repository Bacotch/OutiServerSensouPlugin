<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landconfigdata;

use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;
use function array_filter;
use function count;
use function serialize;

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
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.landconfigs.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->landConfigDatas[$data["id"]] = new LandConfigData($data["id"], $data["landid"], $data["startx"], $data["startz"], $data["endx"], $data["endz"], $data["defaultperms"], $data["roleperms"], $data["memberperms"]);
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
        if (isset(self::$instance)) throw new InstanceOverwriteException(LandConfigDataManager::class);
        self::$instance = new self();
    }

    /**
     * @return LandConfigDataManager
     */
    public static function getInstance(): LandConfigDataManager
    {
        return self::$instance;
    }

    /**
     * @param int $id
     * @return LandConfigData|false
     */
    public function get(int $id): LandConfigData|false
    {
        if (!isset($this->landConfigDatas[$id])) throw new InstanceOverwriteException(LandConfigDataManager::class . "has already been initialized");
        return $this->landConfigDatas[$id];
    }

    /**
     * X座標とY座標とワールド名を元にコンフィグデータを返す、無ければnullを返す
     * @param int $x
     * @param int $z
     * @param string $worldName
     * @return LandConfigData|null
     */
    public function getPos(int $x, int $z, string $worldName): ?LandConfigData
    {
        foreach ($this->landConfigDatas as $landConfigData) {
            $landData = LandDataManager::getInstance()->get($landConfigData->getLandid());
            if (!$landData) {
                $this->delete($landConfigData->getId());
            } elseif ($landData->getWorld() === $worldName
                and $landConfigData->getStartx() <= $x and $x <= $landConfigData->getEndx()
                and $landConfigData->getStartz() <= $z and $z <= $landConfigData->getEndz()) {
                return $landConfigData;
            }
        }

        return null;
    }

    public function create(int $landid, int $startx, int $startz, int $endx, int $endz, array $defaultPerms, array $rolePerms, array $memberPerms): LandConfigData
    {
        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.landconfigs.create",
            [
                "landid" => $landid,
                "startx" => $startx,
                "startz" => $startz,
                "endx" => $endx,
                "endz" => $endz,
                "defaultperms" => serialize($defaultPerms),
                "roleperms" => serialize($rolePerms),
                "memberperms" => serialize($memberPerms)
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->seq++;
        $this->landConfigDatas[$this->seq] = new LandConfigData($this->seq, $landid, $startx, $startz, $endx, $endz, serialize($defaultPerms), serialize($rolePerms), serialize($memberPerms));

        return $this->landConfigDatas[$this->seq];
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
                Main::getInstance()->getOutiServerLogger()->error($error);
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
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->landConfigDatas = array_filter($this->landConfigDatas, function ($landConfigData) use ($landid) {
            return $landConfigData->getLandid() !== $landid;
        });
    }
}