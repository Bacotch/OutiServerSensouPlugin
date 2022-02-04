<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\chestshopdata;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\libs\poggit\libasynql\SqlError;
use ken_cir\outiserversensouplugin\Main;
use function count;
use function array_shift;

final class ChestShopDataManager
{
    /**
     * @var ChestShopDataManager $this
     */
    private static self $instance;

    /**
     * @var int
     */
    private int $seq;

    /**
     * @var ChestShopData[]
     */
    private array $chestShopDatas;

    public function __construct()
    {
        $this->chestShopDatas = [];
        Main::getInstance()->getDatabase()->executeSelect("outiserver.chestshops.seq",
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
            });
        Main::getInstance()->getDatabase()->executeSelect("outiserver.chestshops.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->chestShopDatas[$data["id"]] = new ChestShopData($data["id"],
                        $data["faction_id"],
                        $data["worldName"],
                        $data["chestx"],
                        $data["chesty"],
                        $data["chestz"],
                        $data["signboardx"],
                        $data["signboardy"],
                        $data["signboardz"],
                        $data["itemid"],
                        $data["itemmeta"],
                        $data["price"],
                        $data["duty"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
    }

    /**
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = new self();
    }

    /**
     * チェストショップデータをIDで取得する
     *
     * @param int $id
     * @return false|ChestShopData
     */
    public function getId(int $id): false|ChestShopData
    {
        if (!isset($this->chestShopDatas[$id])) return false;
        return $this->chestShopDatas[$id];
    }

    public function getPosition(string $worldName, ?int $x = 0, ?int $y = 0, int $z = 0): false|ChestShopData
    {
        $chestShopData = array_filter($this->chestShopDatas, function ($chestShopData) use ($worldName, $x, $y, $z) {
            return $chestShopData->getWorldName() === $worldName
                and (($chestShopData->getChestX() === $x
                    and $chestShopData->getChestY() === $y
                    and $chestShopData->getChestZ() === $z)
                or ($chestShopData->getSignboardX() === $x
                    and $chestShopData->getSignboardY() === $y
                    and $chestShopData->getSignboardZ() === $z));
        });

        if (count($chestShopData) < 1) return false;
        return array_shift($chestShopData);
    }

    public function create(int $factionId, ): void
    {
        Main::getInstance()->getDatabase()->executeInsert("outiserver.factions.create",
            [
                "faction_id" => $name,
                "owner_xuid" => $owner_xuid,
                "color" => $color,
                "money" => 0
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        $this->faction_datas[$this->seq] = new FactionData($this->seq, $name, $owner_xuid, $color, 0);
    }
}