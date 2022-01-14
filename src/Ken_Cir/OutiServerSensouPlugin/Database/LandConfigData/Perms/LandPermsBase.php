<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

abstract class LandPermsBase
{
    /**
     * 立ち入り権限
     * @var bool
     */
    protected bool $entry;

    /**
     * ブロックタップ権限
     * @var bool
     */
    protected bool $blockTap_Place;

    /**
     * ブロック破壊権限
     * @var bool
     */
    protected bool $blockBreak;

    public function __construct(bool $entry, bool $blockTap_Place, bool $blockBreak)
    {
        $this->entry = $entry;
        $this->blockTap_Place = $blockTap_Place;
        $this->blockBreak = $blockBreak;
    }

    /**
     * @return bool
     */
    public function isEntry(): bool
    {
        return $this->entry;
    }

    /**
     * @param bool $entry
     */
    public function setEntry(bool $entry): void
    {
        $this->entry = $entry;
    }

    /**
     * @return bool
     */
    final public function isBlockTap_Place(): bool
    {
        return $this->blockTap_Place;
    }

    /**
     * @param bool $blockTap_Place
     */
    final public function setBlockTap_Place(bool $blockTap_Place): void
    {
        $this->blockTap_Place= $blockTap_Place;
    }

    /**
     * @return bool
     */
    final public function isBlockBreak(): bool
    {
        return $this->blockBreak;
    }

    /**
     * @param bool $blockBreak
     */
    final public function setBlockBreak(bool $blockBreak): void
    {
        $this->blockBreak = $blockBreak;
    }

    abstract public function toArray(): array;
}