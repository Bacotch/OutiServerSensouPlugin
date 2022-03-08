<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\adminshopdata;

use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;

/**
 * AdminShopのデータ
 */
class AdminShopData
{
    private int $id;

    private int $itemId;

    private int $itemMeta;

    private int $minPrice;

    private int $maxPrice;

    private int $price;

    private int $defaultPrice;

    private int $rateCount;

    private int $rateFluctuation;

    private int $sellCount;

    public function __construct(int $id, int $itemId, int $itemMeta, int $minPrice, int $maxPrice, int $price, int $defaultPrice, int $rateCount, int $rateFluctuation, int $sellCount)
    {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->itemMeta = $itemMeta;
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
        $this->price = $price;
        $this->defaultPrice = $defaultPrice;
        $this->rateCount = $rateCount;
        $this->rateFluctuation = $rateFluctuation;
        $this->sellCount = $sellCount;
    }

    private function update(): void
    {
        Main::getInstance()->getDatabase()->executeChange("outiserver.adminshops.update",
            [
                "item_id" => $this->itemId,
                "item_meta" => $this->itemMeta,
                "min_price" => $this->minPrice,
                "max_price" => $this->maxPrice,
                "price" => $this->price,
                "default_price" => $this->defaultPrice,
                "rate_count" => $this->rateCount,
                "rate_fluctuation" => $this->rateFluctuation,
                "sell_count" => $this->sellCount,
                "id" => $this->id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @return int
     */
    public function getItemMeta(): int
    {
        return $this->itemMeta;
    }

    /**
     * @return int
     */
    public function getMinPrice(): int
    {
        return $this->minPrice;
    }

    /**
     * @param int $minPrice
     */
    public function setMinPrice(int $minPrice): void
    {
        $this->minPrice = $minPrice;
        $this->update();
    }

    /**
     * @return int
     */
    public function getMaxPrice(): int
    {
        return $this->maxPrice;
    }

    /**
     * @param int $maxPrice
     */
    public function setMaxPrice(int $maxPrice): void
    {
        $this->maxPrice = $maxPrice;
        $this->update();
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param int $price
     */
    public function setPrice(int $price): void
    {
        $this->price = $price;
        $this->update();
    }

    /**
     * @return int
     */
    public function getDefaultPrice(): int
    {
        return $this->defaultPrice;
    }

    /**
     * @param int $defaultPrice
     */
    public function setDefaultPrice(int $defaultPrice): void
    {
        $this->defaultPrice = $defaultPrice;
        $this->update();
    }

    /**
     * @return int
     */
    public function getRateCount(): int
    {
        return $this->rateCount;
    }

    /**
     * @param int $rateCount
     */
    public function setRateCount(int $rateCount): void
    {
        $this->rateCount = $rateCount;
        $this->update();
    }

    /**
     * @return int
     */
    public function getRateFluctuation(): int
    {
        return $this->rateFluctuation;
    }

    /**
     * @param int $rateFluctuation
     */
    public function setRateFluctuation(int $rateFluctuation): void
    {
        $this->rateFluctuation = $rateFluctuation;
        $this->update();
    }

    /**
     * @return int
     */
    public function getSellCount(): int
    {
        return $this->sellCount;
    }

    /**
     * @param int $sellCount
     */
    public function setSellCount(int $sellCount): void
    {
        $this->sellCount = $sellCount;
        $this->update();
    }
}