<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\warcache;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;

class WarCacheManager
{
    private static self $instance;

    private array $cache;

    public function __construct()
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;
        $this->cache = [];
    }

    /**
     * @return WarCacheManager
     */
    public static function getInstance(): WarCacheManager
    {
        return self::$instance;
    }

    public function get(int $id): ?WarCache
    {
        if (!isset($this->cache[$id])) return null;
        return $this->cache[$id];
    }

    /**
     * @return WarCache[]
     */
    public function getAll(): array
    {
        return $this->cache;
    }

    public function create(int $id, int $remainingTime, array $declarationFactionPlayers, array $enemyFactionPlayers): WarCache
    {
        if ($this->get($id)) return $this->get($id);
        return ($this->cache[$id] = new WarCache($id,
        $remainingTime,
        $declarationFactionPlayers,
        $enemyFactionPlayers));
    }

    public function delete(int $id): void
    {
        if (!$this->get($id)) return;
        unset($this->cache[$id]);
    }
}