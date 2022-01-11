<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Cache\PlayerCache;

use function strtolower;

/**
 * プレイヤーキャッシュ
 */
class PlayerCache
{
    /**
     * プレイヤー名
     *
     * @var string
     */
    private string $name;

    /**
     * おうちウォッチのロック状態
     *
     * @var bool
     */
    private bool $lockOutiWatch;

    public function __construct(string $name)
    {
        $this->name = strtolower($name);
        $this->lockOutiWatch = false;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isLockOutiWatch(): bool
    {
        return $this->lockOutiWatch;
    }

    /**
     * @param bool $lockoOutiWatch
     */
    public function setLockOutiWatch(bool $lockoOutiWatch): void
    {
        $this->lockoOutiWatch = $lockoOutiWatch;
    }
}