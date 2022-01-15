<?php

/**
 * キャッシュ(試験的に導入)
 */

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\playercache;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
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
        if (isset(self::$instance)) throw new InstanceOverwriteException(PlayerCacheManager::class);
        self::$instance = new PlayerCacheManager();
    }

    /**
     * @return PlayerCacheManager
     */
    public static function getInstance(): PlayerCacheManager
    {
        return self::$instance;
    }

    /**
     * プレイヤーキャッシュを取得する
     *
     * @param string $name
     * @return PlayerCache|null
     */
    public function get(string $name): ?PlayerCache
    {
        if (!isset($this->cache[strtolower($name)])) return null;
        return $this->cache[strtolower($name)];
    }

    /**
     * プレイヤーキャッシュを作成する
     *
     * @param string $name
     * @return void
     */
    public function create(string $name): void
    {
        if (isset($this->cache[strtolower($name)])) return;
        $this->cache[strtolower($name)] = new PlayerCache($name);
    }

    /**
     * キャッシュを削除する
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name): void
    {
       unset($this->cache[strtolower($name)]);
    }
}