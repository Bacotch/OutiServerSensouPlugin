<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landconfigdata;

use ken_cir\outiserversensouplugin\database\landconfigdata\perms\LandPermsManager;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;
use function array_map;
use function serialize;
use function unserialize;

class LandConfigData
{
    /**
     * 識別用ID
     * @var int
     */
    private int $id;

    /**
     * 土地ID
     * @var int
     */
    private int $landid;

    /**
     * 開始X座標
     * @var int
     */
    private int $startx;

    /**
     * 開始Z座標
     * @var int
     */
    private int $startz;

    /**
     * 終了X座標
     * @var int
     */
    private int $endx;

    /**
     * 終了Z座標
     * @var int
     */
    private int $endz;

    /**
     * 権限マネージャー
     * @var LandPermsManager
     */
    private LandPermsManager $landPermsManager;

    public function __construct(int $id, int $landid, int $startx, int $startz, int $endx, int $endz, string $defaultPerms, string $rolePerms, string $memberPerms)
    {
        $this->id = $id;
        $this->landid = $landid;
        $this->startx = $startx;
        $this->startz = $startz;
        $this->endx = $endx;
        $this->endz = $endz;
        $this->landPermsManager = new LandPermsManager(unserialize($defaultPerms), unserialize($rolePerms), unserialize($memberPerms));
    }

    public function update()
    {
        Main::getInstance()->getDatabase()->executeChange(
            "outiserver.landconfigs.update",
            [
                "defaultperms" => serialize($this->landPermsManager->getDefalutLandPerms()->toArray()),
                "roleperms" => serialize(array_map(function ($roleLandPerms) {
                    return $roleLandPerms->toArray();
                }, $this->landPermsManager->getAllRoleLandPerms())),
                "memberperms" => serialize(array_map(function ($memberLandPerms) {
                    return $memberLandPerms->toArray();
                }, $this->landPermsManager->getAllMemberLandPerms())),
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
    public function getLandid(): int
    {
        return $this->landid;
    }

    /**
     * @return int
     */
    public function getStartx(): int
    {
        return $this->startx;
    }

    /**
     * @return int
     */
    public function getStartz(): int
    {
        return $this->startz;
    }

    /**
     * @return int
     */
    public function getEndx(): int
    {
        return $this->endx;
    }

    /**
     * @return int
     */
    public function getEndz(): int
    {
        return $this->endz;
    }

    /**
     * @return LandPermsManager
     */
    public function getLandPermsManager(): LandPermsManager
    {
        return $this->landPermsManager;
    }
}