<?php

/**
 * キャッシュ(試験的に導入)
 */

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\playercache;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use function strtolower;

/**
 * プレイヤーキャッシュマネージャー
 */
class PlayerCacheManager
{
    /**
     * インスタンス
     *
     * @var PlayerCacheManager $this
     */
    private static self $instance;

    /**
     * キャッシュ
     *
     * @var PlayerCache[]
     */
    private array $cache;

    public function __construct()
    {
        $this->cache = [];
    }

    /**
     * インスタンスを作成する
     *
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = new self();
    }

    /**
     * @return PlayerCacheManager
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @param string $xuid
     * @return false|PlayerCache
     */
    public function getXuid(string $xuid): false|PlayerCache
    {
        if (!isset($this->cache[$xuid])) return false;
        return $this->cache[$xuid];
    }

    public function getName(string $name): false|PlayerCache
    {
        $playerCache = array_filter($this->cache, function ($cache) use ($name) {
            return $cache->getName() === strtolower($name);
        });

        if (count($playerCache) < 1) return false;
        return array_shift($playerCache);
    }

    /**
     * プレイヤーキャッシュを作成する
     *
     * @param string $name
     * @return void
     */
    public function create(string $xuid, string $name): void
    {
        if (isset($this->cache[$xuid])) return;
        $this->cache[$xuid] = new PlayerCache($xuid, $name);
    }

    public function deleteXuid(string $xuid): void
    {
        unset($this->cache[$xuid]);
    }

    public function deleteName(string $name): void
    {
        if (!$this->getName($name)) return;
        $this->cache = array_filter($this->cache, function ($playerCache) use ($name) {
            return $playerCache->getName() !== strtolower($name);
        });
    }
}