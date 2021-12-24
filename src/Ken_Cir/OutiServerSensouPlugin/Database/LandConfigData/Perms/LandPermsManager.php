<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

use JetBrains\PhpStorm\Pure;

class LandPermsManager
{
    /**
     * デフォルト権限
     * @var DefalutLandPerms
     */
    private DefalutLandPerms $defalutLandPerms;

    /**
     * ロール権限
     * @var RoleLandPerms[]
     */
    private array $roleLandPerms;

    /**
     * @var MemberLandPerms[]
     */
    private array $memberLandPerms;

    #[Pure] public function __construct(array $perms)
    {
        $this->defalutLandPerms = new DefalutLandPerms($perms["default"]["blockTap"], $perms["default"]["blockPlace"], $perms["default"]["blockBreak"]);
        foreach ($perms["roles"] as $rolePerms) {
            $this->roleLandPerms[$rolePerms["id"]] = new RoleLandPerms($rolePerms["id"], $rolePerms["blockTap"], $rolePerms["blockPlace"], $rolePerms["blockBreak"]);
        }
        foreach ($perms["members"] as $memberPerms) {
            $this->memberLandPerms[$memberPerms["name"]] = new MemberLandPerms($memberPerms["name"], $memberPerms["blockTap"], $memberPerms["blockPlace"],$memberPerms["blockBreak"]);
        }
    }

    /**
     * @return DefalutLandPerms
     */
    public function getDefalutLandPerms(): DefalutLandPerms
    {
        return $this->defalutLandPerms;
    }

    /**
     * @param int $roleid
     * @return RoleLandPerms|null
     */
    public function getRoleLandPerms(int $roleid): ?RoleLandPerms
    {
        if (!isset($this->roleLandPerms[$roleid])) return null;
        return $this->roleLandPerms[$roleid];
    }

    /**
     * @param string $name
     * @return MemberLandPerms|null
     */
    public function getMemberLandPerms(string $name): ?MemberLandPerms
    {
        if (!isset($this->memberLandPerms[$name])) return null;
        return $this->memberLandPerms[$name];
    }
}