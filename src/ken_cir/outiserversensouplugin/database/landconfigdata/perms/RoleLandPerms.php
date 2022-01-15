<?php

namespace ken_cir\outiserversensouplugin\database\landconfigdata\perms;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

final class RoleLandPerms extends LandPermsBase
{
    /**
     * ロールID
     * @var int
     */
    private int $roleid;

    /**
     * @param int $roleid
     * @param bool $entry
     * @param bool $blockTap_Place
     * @param bool $blockBreak
     */
    #[Pure] public function __construct(int $roleid, bool $entry, bool $blockTap_Place, bool $blockBreak)
    {
        $this->roleid = $roleid;
        parent::__construct($entry, $blockTap_Place, $blockBreak);
    }

    /**
     * @return int
     */
    public function getRoleid(): int
    {
        return $this->roleid;
    }


    #[ArrayShape(["id" => "int", "entry" => "bool", "blockTap_Place" => "bool", "blockBreak" => "bool"])]
    public function toArray(): array
    {
        return array(
            "id" => $this->roleid,
            "entry" => $this->entry,
            "blockTap_Place" => $this->blockTap_Place,
            "blockBreak" => $this->blockBreak
        );
    }
}