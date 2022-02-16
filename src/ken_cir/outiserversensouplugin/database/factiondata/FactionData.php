<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\factiondata;

use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;

/**
 * 派閥データ
 */
class FactionData
{
    /**
     * @var int
     * 派閥ID
     */
    private int $id;

    /**
     * @var string
     * 派閥名
     */
    private string $name;

    /**
     * @var string
     * 派閥主Player名
     */
    private string $owner_xuid;

    /**
     * @var int
     * 派閥チャットカラー
     */
    private int $color;

    /**
     * 派閥所持金
     *
     * @var int
     */
    private int $money;

    /**
     * 派閥の通貨名
     *
     * @var string
     */
    private string $currencyName;

    /**
     * 通貨レート(100円あたり)
     *
     * @var int
     */
    private int $currencyRate;

    /**
     * @param int $id
     * @param string $name
     * @param string $owner_xuid
     * @param int $color
     * @param int $money
     * @param string $currencyName
     * @param int $currencyRate
     */
    public function __construct(int $id, string $name, string $owner_xuid, int $color, int $money, string $currencyName, int $currencyRate)
    {
        $this->id = $id;
        $this->name = $name;
        $this->owner_xuid = $owner_xuid;
        $this->color = $color;
        $this->money = $money;
        $this->currencyName = $currencyName;
        $this->currencyRate = $currencyRate;
    }

    /**
     * db上にアップデート
     */
    private function update(): void
    {
        Main::getInstance()->getDatabase()->executeChange("outiserver.factions.update",
            [
                "name" => $this->name,
                "owner_xuid" => $this->owner_xuid,
                "color" => $this->color,
                "money" => $this->money,
                "currency_name" => $this->currencyName,
                "currency_rate" => $this->currencyRate,
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
     * 派閥IDを取得する
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->update();
    }

    /**
     * @param string $owner_xuid
     */
    public function setOwnerXuid(string $owner_xuid): void
    {
        $this->owner_xuid = $owner_xuid;
        $this->update();
    }

    /**
     * @return string
     */
    public function getOwnerXuid(): string
    {
        return $this->owner_xuid;
    }

    /**
     * @return int
     */
    public function getColor(): int
    {
        return $this->color;
    }

    /**
     * @param int $color
     */
    public function setColor(int $color): void
    {
        $this->color = $color;
        $this->update();
    }

    /**
     * @return int
     */
    public function getMoney(): int
    {
        return $this->money;
    }

    /**
     * @param int $money
     */
    public function setMoney(int $money): void
    {
        $this->money = $money;
        $this->update();
    }

    /**
     * @return string
     */
    public function getCurrencyName(): string
    {
        return $this->currencyName;
    }

    /**
     * @param string $currencyName
     */
    public function setCurrencyName(string $currencyName): void
    {
        $this->currencyName = $currencyName;
        $this->update();
    }

    /**
     * @return int
     */
    public function getCurrencyRate(): int
    {
        return $this->currencyRate;
    }

    /**
     * @param int $currencyRate
     */
    public function setCurrencyRate(int $currencyRate): void
    {
        $this->currencyRate = $currencyRate;
        $this->update();
    }
}