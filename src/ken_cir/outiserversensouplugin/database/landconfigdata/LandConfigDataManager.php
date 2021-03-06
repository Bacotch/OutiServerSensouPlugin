<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landconfigdata;

use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_values;
use function count;
use function serialize;

/**
 * 土地保護データマネージャー
 *
 * 依存関係:
 * LandConfigData -> LandData
 * LandConfigData(MemberPerms) -> PlayerData
 * LandConfigData(RolePerms) -> RoleData
 */
class LandConfigDataManager
{
    private DataConnector $connector;

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

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->landConfigDatas = [];
        $this->connector->executeSelect(
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
        $this->connector->executeSelect(
            "outiserver.landconfigs.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->landConfigDatas[$data["id"]] = new LandConfigData($data["id"],
                        $data["landid"],
                        $data["startx"],
                        $data["startz"],
                        $data["endx"],
                        $data["endz"],
                        $data["defaultperms"],
                        $data["roleperms"],
                        $data["memberperms"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
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
        if (!isset($this->landConfigDatas[$id])) return false;
        return $this->landConfigDatas[$id];
    }

    /**
     * @param int $landId
     * @param bool|null $keyValue
     * @return LandConfigData[]
     */
    public function getLandConfigs(int $landId, ?bool $keyValue = false): array
    {
        $landConfigDatas = array_filter($this->landConfigDatas, function (LandConfigData $landConfigData) use ($landId) {
            return $landConfigData->getId() === $landId;
        });

        if ($keyValue) return array_values($landConfigDatas);
        return $landConfigDatas;
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
        $this->connector->executeInsert(
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
        $this->connector->executeGeneric(
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
}