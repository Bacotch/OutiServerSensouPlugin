<?php

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class RoleLandPerms extends LandPermsBase
{
    /**
     * ロールID
     * @var int
     */
    private int $roleid;

    /**
     * @param int $roleid
     * @param bool $blockTap
     * @param bool $blockPlace
     * @param bool $blockBreak
     */
    #[Pure] public function __construct(int $roleid, bool $blockTap, bool $blockPlace, bool $blockBreak)
    {
        $this->roleid = $roleid;
        parent::__construct($blockTap, $blockPlace, $blockBreak);
    }

    /**
     * @return int
     */
    public function getRoleid(): int
    {
        return $this->roleid;
    }

    #[ArrayShape(["id" => "int", "blockTap" => "bool", "blockPlace" => "bool", "blockBreak" => "bool"])]
    public function toArray(): array
    {
        return array(
            "id" => $this->roleid,
            "blockTap" => $this->blockTap,
            "blockPlace" => $this->blockPlace,
            "blockBreak" => $this->blockBreak
        );
    }
}