<?php

namespace Ken_Cir\OutiServerSensouPlugin\Utils;

class PluginUtils
{
    private function __construct()
    {
    }

    /**
     * @param int $id
     * idをもとにチャットカラー記号を返す
     */
    static public function getChatColor(int $id): string
    {
        return match ($id) {
            0 => "§0",
            1 => "§1",
            2 => "§2",
            3 => "§3",
            4 => "§4",
            5 => "§5",
            6 => "§6",
            7 => "§7",
            8 => "§8",
            9 => "§9",
            10 => "§a",
            11 => "§b",
            12 => "§c",
            13 => "§d",
            14 => "§e",
            15 => "§f",
            default => "",
        };
    }
}