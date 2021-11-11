<?php

namespace Ken_Cir\OutiServerSensouPlugin\Utils;

use DateTime;
use DateTimeZone;
use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\CortexPE\DiscordWebhookAPI\Message;
use Ken_Cir\OutiServerSensouPlugin\libs\CortexPE\DiscordWebhookAPI\Webhook;
use Ken_Cir\OutiServerSensouPlugin\Main;

/**
 * おうち鯖プラグイン ユーティリティ
 */
class OutiServerPluginUtils
{
    private function __construct()
    {
    }

    /**
     * @param int $id
     * @return string
     * idをもとにチャットカラー記号を返す
     */
    public static function getChatColor(int $id): string
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

    /**
     * @param int $id
     * @return string
     * idを元に色名を返す
     */
    public static function getChatString(int $id): string
    {
        return match ($id) {
            0 => "黒",
            1 => "濃い青",
            2 => "濃い緑",
            3 => "濃い水色",
            4 => "濃い赤色",
            5 => "濃い紫",
            6 => "金色",
            7 => "灰色",
            8 => "濃い灰色",
            9 => "青",
            10 => "緑",
            11 => "水色",
            12 =>  "赤",
            13 => "ピンク",
            14 => "黄色",
            15 => "白色",
            default => ""
        };
    }

    public static function sendDiscordLog(string $url, string $content)
    {
        try {
            if ($url === "" or $content === "") return;
            try {
                $time = new DateTime('NOW', new DateTimeZone("Asia/Tokyo"));
            } catch (Exception) {
                return;
            }

            $webhook = new Webhook($url);
            $message = new Message();
            $message->setContent("```[{$time->format('Y-m-d H:i:sP')}]: $content```");
            $webhook->send($message);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}