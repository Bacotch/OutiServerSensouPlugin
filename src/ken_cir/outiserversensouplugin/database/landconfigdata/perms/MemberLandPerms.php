<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\landconfigdata\perms;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use function strtolower;

class MemberLandPerms extends LandPermsBase
{
    /**
     * プレイヤー名
     * @var string
     */
    private string $name;

    #[Pure] public function __construct(string $name, bool $entry, bool $blockTap_Place, bool $blockBreak)
    {
        $this->name = strtolower($name);
        parent::__construct($entry, $blockTap_Place, $blockBreak);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    #[ArrayShape(["name" => "string", "entry" => "bool", "blockTap_Place" => "bool", "blockBreak" => "bool"])]
    public function toArray(): array
    {
        return array(
            "name" => $this->name,
            "entry" => $this->entry,
            "blockTap_Place" => $this->blockTap_Place,
            "blockBreak" => $this->blockBreak
        );
    }
}