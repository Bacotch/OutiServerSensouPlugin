<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\translator;

use ken_cir\outiserversensouplugin\Main;
use function file_get_contents;
use function json_decode;

class ItemTranslator
{
    /**
     * 翻訳データ
     *
     * @var ItemTranslatorData[]
     */
    private static array $translatorData;

    private function __construct()
    {
    }

    public static function initialize(): void
    {
        $data = file_get_contents(Main::getInstance()->getDataFolder() . "items_map.json");
        if (!$data) return;
        $json = json_decode($data, true);

        foreach ($json as $item) {
            // TODO: 仮処置で(int)してるけどjsonのデータをintに変換
            self::$translatorData[$item["key"]] = new ItemTranslatorData($item["key"],
                (int)$item["id"],
                (int)$item["meta"],
                $item["translator"]);
        }
    }
}