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

    #[Pure] public function __construct(array $defaultPerms, array $rolePerms, array $memberPerms)
    {
        $this->defalutLandPerms = new DefalutLandPerms($defaultPerms["blockTap"], $defaultPerms["blockPlace"], $defaultPerms["blockBreak"]);
        foreach ($rolePerms as $rolePerm) {
            $this->roleLandPerms[$rolePerm["id"]] = new RoleLandPerms($rolePerm["id"], $rolePerm["blockTap"], $rolePerm["blockPlace"], $rolePerm["blockBreak"]);
        }
        foreach ($memberPerms as $memberPerm) {
            $this->memberLandPerms[$memberPerm["name"]] = new MemberLandPerms($memberPerm["name"], $memberPerm["blockTap"], $memberPerm["blockPlace"], $memberPerm["blockBreak"]);
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
     * @return RoleLandPerms[]
     */
    public function getAllRoleLandPerms(): array
    {
        return $this->roleLandPerms;
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

    /**
     * @return MemberLandPerms[]
     */
    public function getAllMemberLandPerms(): array
    {
        return $this->memberLandPerms;
    }
}