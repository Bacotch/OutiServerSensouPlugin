<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\LandData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;

/**
 * 派閥の土地データ
 */
class LandData
{
    /**
     * 管理用ID
     * @var int
     */
    private int $id;

    /**
     * 派閥ID
     * @var int
     */
    private int $faction_id;

    /**
     * X座標
     * @var int
     */
    private int $x;

    /**
     * Z座標
     * @var int
     */
    private int $z;

    /**
     * ワールド名
     * @var string
     */
    private string $world;

    public function __construct(int $id, int $faction_id, int $x, int $z, string $world)
    {
        $this->id = $id;
        $this->faction_id = $faction_id;
        $this->x = $x;
        $this->z = $z;
        $this->world = $world;
    }

    public function update(): void
    {
        try {
            Main::getInstance()->getDatabase()->executeChange("lands.update",
                [
                    "faction_id" => $this->faction_id,
                    "x" => $this->x,
                    "z" => $this->z,
                    "world" => $this->world,
                    "id" => $this->id
                ],
                null,
                function (SqlError $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            );
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * 管理用IDを返す
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 派閥IDを返す
     * @return int
     */
    public function getFactionId(): int
    {
        return $this->faction_id;
    }

    /**
     * 派閥IDを更新する
     * @param int $faction_id
     */
    public function setFactionId(int $faction_id): void
    {
        $this->faction_id = $faction_id;
        $this->update();
    }

    /**
     * X座標を返す
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * X座標を更新する
     * @param int $x
     */
    public function setX(int $x): void
    {
        $this->x = $x;
        $this->update();
    }

    /**
     * Z座標を返す
     * @return int
     */
    public function getZ(): int
    {
        return $this->z;
    }

    /**
     * Z座標を更新する
     * @param int $z
     */
    public function setZ(int $z): void
    {
        $this->z = $z;
        $this->update();
    }

    /**
     * ワールド名を返す
     * @return string
     */
    public function getWorld(): string
    {
        return $this->world;
    }

    /**
     * ワールド名を更新する
     * @param string $world
     */
    public function setWorld(string $world): void
    {
        $this->world = $world;
        $this->update();
    }
}