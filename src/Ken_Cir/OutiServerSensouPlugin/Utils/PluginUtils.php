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
final class PluginUtils
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

    static public function sendDiscordLog(string $url, string $content)
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