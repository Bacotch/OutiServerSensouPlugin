<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Utils;

use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\Player;
use pocketmine\Server;

final class Logger
{
    public function __construct()
    {
    }

    /**
     * @param $error
     * @param Player|null $player
     * エラーログ出力
     */
    public function error($error, ?Player $player = null)
    {
        PluginUtils::sendDiscordLog(
            Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
            "ファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}"
        );
        Server::getInstance()->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");

        // もしPlayerインスタンスが引数に指定されていたら
        if ($player instanceof Player) {
            $player->sendMessage("§c処理中にエラーが発生しました\n{$error->getMessage()}");
        }
    }
}