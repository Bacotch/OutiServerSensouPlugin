<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\adminshopdata;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function count;
use function array_values;

/**
 * AdminShopデータマネージャー
 */
class AdminShopDataManager
{
    private DataConnector $connector;

    /**
     * @var AdminShopDataManager $this
     */
    private static self $instance;

    private int $seq;

    /**
     * @var AdminShopData[]
     */
    private array $adminshopDatas;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->adminshopDatas = [];

        $this->connector->executeSelect("outiserver.adminshops.seq",
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
        $this->connector->executeSelect("outiserver.adminshops.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->adminshopDatas[$data["id"]] = new AdminShopData($data["id"],
                        $data["item_id"],
                        $data["item_meta"],
                        $data["min_price"],
                        $data["max_price"],
                        $data["price"],
                        $data["default_price"],
                        $data["rate_count"],
                        $data["rate_fluctuation"],
                        $data["sell_count"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
    }

    /**
     * @return AdminShopDataManager
     */
    public static function getInstance(): AdminShopDataManager
    {
        return self::$instance;
    }

    public function get(int $id): ?AdminShopData
    {
        if (!isset($this->adminshopDatas[$id])) return null;
        return $this->adminshopDatas[$id];
    }

    /**
     * @return AdminShopData[]
     */
    public function getAll(?bool $keyValue = false): array
    {
        if ($keyValue) return array_values($this->adminshopDatas);
        return $this->adminshopDatas;
    }

    public function create(int $itemId, int $itemMeta, int $minPrice, int $maxprice, int $defaultPrice, int $rateCount, int $rateFluctuation): AdminShopData
    {
        $this->connector->executeInsert("outiserver.adminshops.create",
            [
                "item_id" => $itemId,
                "item_meta" => $itemMeta,
                "min_price" => $minPrice,
                "max_price" => $maxprice,
                "default_price" => $defaultPrice,
                "rate_count" => $rateCount,
                "rate_fluctuation" => $rateFluctuation
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        return ($this->adminshopDatas[$this->seq] = new AdminShopData($this->seq,
            $itemId,
            $itemMeta,
            $minPrice,
            $maxprice,
            $defaultPrice,
            $defaultPrice,
            $rateCount,
            $rateFluctuation,
            0));
    }

    public function delete(int $id): void
    {
        if (!$this->get($id)) return;

        $this->connector->executeGeneric("outiserver.adminshops.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->adminshopDatas[$id]);
    }
}