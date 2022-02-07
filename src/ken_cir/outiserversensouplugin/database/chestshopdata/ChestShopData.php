<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\chestshopdata;

use ken_cir\outiserversensouplugin\libs\poggit\libasynql\SqlError;
use ken_cir\outiserversensouplugin\Main;

final class ChestShopData
{
    /**
     * 管理ID
     *
     * @var int
     */
    private int $id;

    /**
     * 派閥ID
     *
     * @var int
     */
    private int $faction_id;

    /**
     * チェスト & 看板が設置してあるワールド名
     *
     * @var string
     */
    private string $worldName;

    /**
     * チェストX座標
     *
     * @var int
     */
    private int $chestX;

    /**
     * チェストY座標
     *
     * @var int
     */
    private int $chestY;

    /**
     * チェストZ座標
     *
     * @var int
     */
    private int $chestZ;

    /**
     * 看板X座標
     *
     * @var int
     */
    private int $signboardX;

    /**
     * 看板Y座標
     *
     * @var int
     */
    private int $signboardY;

    /**
     * 看板Z座標
     *
     * @var int
     */
    private int $signboardZ;

    /**
     * 販売しているアイテムIDz
     *
     * @var int
     */
    private int $itemId;

    /**
     * 販売しているアイテムメタ値
     *
     * @var int
     */
    private int $itemMeta;

    /**
     * 1個あたりの値段
     *
     * @var int
     */
    private int $price;

    /**
     * 関税
     *
     * @var int
     */
    private int $duty;

    /**
     * @param int $id
     * @param int $faction_id
     * @param string $worldName
     * @param int $chestX
     * @param int $chestY
     * @param int $chestZ
     * @param int $signboardX
     * @param int $signboardY
     * @param int $signboardZ
     * @param int $itemId
     * @param int $itemMeta
     * @param int $price
     * @param int $duty
     */
    public function __construct(int $id, int $faction_id, string $worldName, int $chestX, int $chestY, int $chestZ, int $signboardX, int $signboardY, int $signboardZ, int $itemId, int $itemMeta, int $price, int $duty)
    {
        $this->id = $id;
        $this->faction_id = $faction_id;
        $this->worldName = $worldName;
        $this->chestX = $chestX;
        $this->chestY = $chestY;
        $this->chestZ = $chestZ;
        $this->signboardX = $signboardX;
        $this->signboardY = $signboardY;
        $this->signboardZ = $signboardZ;
        $this->itemId = $itemId;
        $this->itemMeta = $itemMeta;
        $this->price = $price;
        $this->duty = $duty;
    }

    private function update(): void
    {
        Main::getInstance()->getDatabase()->executeChange("outiserver.chestshops.update",
            [
                "itemid" => $this->itemId,
                "itemmeta" => $this->itemMeta,
                "price" => $this->price,
                "duty" => $this->duty,
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
    public function getFactionId(): int
    {
        return $this->faction_id;
    }

    /**
     * @return string
     */
    public function getWorldName(): string
    {
        return $this->worldName;
    }

    /**
     * @return int
     */
    public function getChestX(): int
    {
        return $this->chestX;
    }

    /**
     * @return int
     */
    public function getChestY(): int
    {
        return $this->chestY;
    }

    /**
     * @return int
     */
    public function getChestZ(): int
    {
        return $this->chestZ;
    }

    /**
     * @return int
     */
    public function getSignboardX(): int
    {
        return $this->signboardX;
    }

    /**
     * @return int
     */
    public function getSignboardY(): int
    {
        return $this->signboardY;
    }

    /**
     * @return int
     */
    public function getSignboardZ(): int
    {
        return $this->signboardZ;
    }

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     */
    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
        $this->update();
    }

    /**
     * @return int
     */
    public function getItemMeta(): int
    {
        return $this->itemMeta;
    }

    /**
     * @param int $itemMeta
     */
    public function setItemMeta(int $itemMeta): void
    {
        $this->itemMeta = $itemMeta;
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
    public function getDuty(): int
    {
        return $this->duty;
    }

    /**
     * @param int $duty
     */
    public function setDuty(int $duty): void
    {
        $this->duty = $duty;
        $this->update();
    }
}