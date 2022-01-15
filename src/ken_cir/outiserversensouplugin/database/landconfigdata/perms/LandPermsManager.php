<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landconfigdata\perms;

use JetBrains\PhpStorm\Pure;
use function array_values;
use function strtolower;

final class LandPermsManager
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
        $this->defalutLandPerms = new DefalutLandPerms($defaultPerms["entry"], $defaultPerms["blockTap_Place"], $defaultPerms["blockBreak"]);
        $this->roleLandPerms = [];
        $this->memberLandPerms = [];
        foreach ($rolePerms as $rolePerm) {
            $this->roleLandPerms[$rolePerm["id"]] = new RoleLandPerms($rolePerm["id"], $rolePerm["entry"], $rolePerm["blockTap_Place"], $rolePerm["blockBreak"]);
        }
        foreach ($memberPerms as $memberPerm) {
            $this->memberLandPerms[$memberPerm["name"]] = new MemberLandPerms($memberPerm["name"], $memberPerm["entry"], $memberPerm["blockTap_Place"], $memberPerm["blockBreak"]);
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
        return array_values($this->roleLandPerms);
    }

    /**
     * ロール権限を追加する
     *
     * @param int $roleid
     * @param bool $entry
     * @param bool $blockTap_Place
     * @param bool $blockBreak
     * @return void
     */
    public function createRoleLandPerms(int $roleid, bool $entry, bool $blockTap_Place, bool $blockBreak): void
    {
        if (isset($this->roleLandPerms[$roleid])) return;
        $this->roleLandPerms[$roleid] = new RoleLandPerms($roleid, $entry, $blockTap_Place, $blockBreak);
    }

    /**
     * ロール権限を削除する
     *
     * @param int $roleid
     * @return void
     */
    public function deleteRoleLandPerms(int $roleid): void
    {
        unset($this->roleLandPerms[$roleid]);
    }

    /**
     * @param string $name
     * @return MemberLandPerms|null
     */
    public function getMemberLandPerms(string $name): ?MemberLandPerms
    {
        if (!isset($this->memberLandPerms[strtolower($name)])) return null;
        return $this->memberLandPerms[strtolower($name)];
    }

    /**
     * @return MemberLandPerms[]
     */
    public function getAllMemberLandPerms(): array
    {
        return array_values($this->memberLandPerms);
    }

    /**
     * @param string $name
     * @param bool $entry
     * @param bool $blockTap_Place
     * @param bool $blockBreak
     * @return void
     */
    public function createMemberLandPerms(string $name, bool $entry, bool $blockTap_Place, bool $blockBreak): void
    {
        if (isset($this->memberLandPerms[strtolower($name)])) return;
        $this->memberLandPerms[strtolower($name)] = new MemberLandPerms($name, $entry, $blockTap_Place, $blockBreak);
    }

    public function deleteMemberLandPerms(string $name): void
    {
        unset($this->memberLandPerms[strtolower($name)]);
    }
}