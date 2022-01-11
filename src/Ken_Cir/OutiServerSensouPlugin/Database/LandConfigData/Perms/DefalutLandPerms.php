<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class DefalutLandPerms extends LandPermsBase
{
    #[Pure] public function __construct(bool $blockTap, bool $blockPlace, bool $blockBreak)
    {
        parent::__construct($blockTap, $blockPlace, $blockBreak);
    }

    #[ArrayShape(["blockTap" => "bool", "blockPlace" => "bool", "blockBreak" => "bool"])]
    public function toArray(): array
    {
        return array(
            "blockTap" => $this->blockTap,
            "blockPlace" => $this->blockPlace,
            "blockBreak" => $this->blockBreak
        );
    }
}