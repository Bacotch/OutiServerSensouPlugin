<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\chunk;

use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\libs\poggit\libasynql\SqlError;
use ken_cir\outiserversensouplugin\Main;

final class ChunkDataManager
{
    /**
     * @var ChunkDataManager $this
     */
    private static self $instance;

    /**
     * @var ChunkData[]
     */
    private array $chunkDatas;

    /**
     * @var int
     */
    private int $seq;

    public function __construct()
    {
        $this->chunkDatas = [];
        Main::getInstance()->getDatabase()->executeSelect("outiserver.chunks.seq",
            [],
            function (array $row) {
                if (count($row) < 1) {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->executeSelect("outiserver.chunks.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->chunkDatas[$data["id"]] = new ChunkData($data["id"], $data["x"], $data["y"], $data["z"], $data["worldname"], $data["blockid"], $data["meta"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
    }

    /**
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(ChunkDataManager::class);
        self::$instance = new ChunkDataManager();
    }

    /**
     * @return ChunkDataManager
     */
    public static function getInstance(): ChunkDataManager
    {
        return self::$instance;
    }

    /**
     * @param int $id
     * @return bool|ChunkData
     * 派閥データの取得
     */
    public function get(int $id): bool|ChunkData
    {
        if (!isset($this->chunkDatas[$id])) return false;
        return $this->chunkDatas[$id];
    }

    /**
     * @return ChunkData[]
     */
    public function getChunkDatas(): array
    {
        return $this->chunkDatas;
    }

    public function create(int $x, int $y, int $z, string $worldName, int $blockId, int $meta): void
    {
        Main::getInstance()->getDatabase()->executeInsert("outiserver.chunks.create",
            [
                "x" => $x,
                "y" => $y,
                "z" => $z,
                "worldname" => $worldName,
                "blockid" => $blockId,
                "meta" => $meta
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        $this->chunkDatas[$this->seq] = new ChunkData($this->seq, $x, $y, $z, $worldName, $blockId, $meta);
    }
}