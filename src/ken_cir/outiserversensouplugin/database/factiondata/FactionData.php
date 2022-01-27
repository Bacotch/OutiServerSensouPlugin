<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\factiondata;

use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\libs\poggit\libasynql\SqlError;

/**
 * 派閥データ
 */
final class FactionData
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
     * @param int $id
     * @param string $name
     * @param string $owner_xuid
     * @param int $color
     */
    public function __construct(int $id, string $name, string $owner_xuid, int $color)
    {
        $this->id = $id;
        $this->name = $name;
        $this->owner_xuid = $owner_xuid;
        $this->color = $color;
    }

    /**
     * db上にアップデート
     */
    public function update(): void
    {
        Main::getInstance()->getDatabase()->executeChange("outiserver.factions.update",
            [
                "name" => $this->name,
                "owner_xuid" => $this->owner_xuid,
                "color" => $this->color,
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
     * @param string $owner_xuid
     */
    public function setOwnerXuid(string $owner_xuid): void
    {
        $this->owner_xuid = $owner_xuid;
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
}