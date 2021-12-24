<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

use JetBrains\PhpStorm\Pure;

class DefalutLandPerms extends LandPermsBase
{
    #[Pure] public function __construct(bool $blockTap, bool $blockPlace, bool $blockBreak)
    {
        parent::__construct($blockTap, $blockPlace, $blockBreak);
    }
}