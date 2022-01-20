<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\chunk;

final class ChunkData
{
    private int $id;

    private int $x;

    private int $y;

    private int $z;

    private string $worldName;

    private int $blockid;

    private int $meta;

    public function __construct(int $id, int $x, int $y, int $z, string $worldName, int $blockid, int $meta)
    {
        $this->id = $id;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->worldName = $worldName;
        $this->blockid = $blockid;
        $this->meta = $meta;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getZ(): int
    {
        return $this->z;
    }

    /**
     * @return string
     */
    public function getWorldName(): string
    {
        return $this->worldName;
    }

    /**
     * @return int
     */
    public function getBlockid(): int
    {
        return $this->blockid;
    }

    /**
     * @return int
     */
    public function getMeta(): int
    {
        return $this->meta;
    }
}