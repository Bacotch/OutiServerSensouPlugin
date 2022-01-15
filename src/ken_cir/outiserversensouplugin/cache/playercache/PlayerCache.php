<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\playercache;

use function strtolower;

/**
 * プレイヤーキャッシュ
 */
final class PlayerCache
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
     * @param bool $lockOutiWatch
     */
    public function setLockOutiWatch(bool $lockOutiWatch): void
    {
        $this->lockOutiWatch = $lockOutiWatch;
    }
}