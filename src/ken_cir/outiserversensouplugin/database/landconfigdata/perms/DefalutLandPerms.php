<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landconfigdata\perms;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class DefalutLandPerms extends LandPermsBase
{
    #[Pure] public function __construct(bool $entry, bool $blockTap_Place, bool $blockBreak)
    {
        parent::__construct($entry, $blockTap_Place, $blockBreak);
    }


    #[ArrayShape(["entry" => "bool", "blockTap_Place" => "bool", "blockBreak" => "bool"])]
    public function toArray(): array
    {
        return array(
            "entry" => $this->entry,
            "blockTap_Place" => $this->blockTap_Place,
            "blockBreak" => $this->blockBreak
        );
    }
}