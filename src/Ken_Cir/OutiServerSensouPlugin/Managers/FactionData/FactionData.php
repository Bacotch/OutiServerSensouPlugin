<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\FactionData;

use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleDataManager;

final class FactionData
{
    /**
     * @var string
     * 派閥名
     */
    private string $name;

    /**
     * @var string
     * 派閥主Player名
     */
    private string $owner;

    /**
     * @var int
     * 派閥チャットカラー
     */
    private int $color;

    /**
     * @var RoleDataManager
     * ロールマネージャー
     */
    private RoleDataManager $roleDataManager;

    /**
     * @param string $name
     * @param string $owner
     * @param int $color
     * @param array $roles
     */
    public function __construct(string $name, string $owner, int $color, string $roles)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->color = $color;
        $this->roleDataManager = new RoleDataManager(unserialize($roles));
    }

    /**
     * データをdb上にupdateする
     */
    public function save()
    {
        Main::getInstance()->getDatabase()->executeChange("factions.update",
            [
                "owner" => $this->owner,
                "color" => $this->color,
                "roles" => serialize($this->roleDataManager->getRoleDatas()),
                "name" => $this->name,
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
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
    }

    /**
     * @return RoleDataManager
     */
    public function getRoleDataManager(): RoleDataManager
    {
        return $this->roleDataManager;
    }
}