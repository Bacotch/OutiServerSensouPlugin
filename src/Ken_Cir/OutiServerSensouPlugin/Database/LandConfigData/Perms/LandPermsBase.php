<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\Perms;

abstract class LandPermsBase
{
    /**
     * ブロックタップ権限
     * @var bool
     */
    protected bool $blockTap;

    /**
     * ブロック設置権限
     * @var bool
     */
    protected bool $blockPlace;

    /**
     * ブロック破壊権限
     * @var bool
     */
    protected bool $blockBreak;

    public function __construct(bool $blockTap, bool $blockPlace, bool $blockBreak)
    {
        $this->blockTap = $blockTap;
        $this->blockPlace = $blockPlace;
        $this->blockBreak = $blockBreak;
    }

    /**
     * @return bool
     */
    public function isBlockTap(): bool
    {
        return $this->blockTap;
    }

    /**
     * @param bool $blockTap
     */
    public function setBlockTap(bool $blockTap): void
    {
        $this->blockTap = $blockTap;
    }

    /**
     * @return bool
     */
    public function isBlockPlace(): bool
    {
        return $this->blockPlace;
    }

    /**
     * @param bool $blockPlace
     */
    public function setBlockPlace(bool $blockPlace): void
    {
        $this->blockPlace = $blockPlace;
    }

    /**
     * @return bool
     */
    public function isBlockBreak(): bool
    {
        return $this->blockBreak;
    }

    /**
     * @param bool $blockBreak
     */
    public function setBlockBreak(bool $blockBreak): void
    {
        $this->blockBreak = $blockBreak;
    }

    abstract public function toArray(): array;
}