<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\translator;

class ItemTranslatorData
{
    private string $key;

    private int $id;

    private int $meta;

    private string $translator;

    public function __construct(string $key, int $id, int $meta, string $translator)
    {
        $this->key = $key;
        $this->id = $id;
        $this->meta = $meta;
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
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
    public function getMeta(): int
    {
        return $this->meta;
    }

    /**
     * @return string
     */
    public function getTranslator(): string
    {
        return $this->translator;
    }
}