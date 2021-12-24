<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

use JetBrains\PhpStorm\Pure;
use function strtolower;

class MemberLandPerms extends LandPermsBase
{
    /**
     * プレイヤー名
     * @var string
     */
    private string $name;

    #[Pure] public function __construct(string $name, bool $blockTap, bool $blockPlace, bool $blockBreak)
    {
        $this->name = strtolower($name);
        parent::__construct($blockTap, $blockPlace, $blockBreak);
    }
}