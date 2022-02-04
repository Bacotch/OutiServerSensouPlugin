<?php

namespace ken_cir\outiserversensouplugin\tasks;

use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\scheduler\AsyncTask;

final class BlockMap extends AsyncTask
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function onRun(): void
    {
        echo "Start " . PHP_EOL;
        $stdObj = array();
        foreach (VanillaBlocks::getAll() as $item) {
            $stdObj[] = array(
                "key" => "{$item->getId()}:{$item->getMeta()}",
                "id" => $item->getId(),
                "meta" => $item->getMeta(),
                "name" => $item->getName(),
                "translator" => "%tile." . str_replace(" ", "_", strtolower($item->getName())) . ".name"
            );
        }

        file_put_contents($this->path . "resources/test.json", json_encode($stdObj));

        echo "完了" . PHP_EOL;
    }
}