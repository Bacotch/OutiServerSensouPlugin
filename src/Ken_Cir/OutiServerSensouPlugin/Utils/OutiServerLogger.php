<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Utils;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\Player\player;
use pocketmine\Server;

/**
 * おうち鯖プラグインログ関係クラス
 */
class OutiServerLogger
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
        try {
            OutiServerPluginUtils::sendDiscordLog(
                Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
                "ファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}"
            );
            Server::getInstance()->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");

            // もしPlayerインスタンスが引数に指定されていたら
            if ($player instanceof Player) {
                $player->sendMessage("§c処理中にエラーが発生しました\n{$error->getMessage()}");
            }
        }
        catch (Error | Exception $error) {
            echo "ファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}" . PHP_EOL;
        }
    }
}