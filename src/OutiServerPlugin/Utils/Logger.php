<?php

declare(strict_types=1);

namespace OutiServerPlugin\Utils;

use OutiServerPlugin\Tasks\LogDiscordSend;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class Logger
{
    /**
     * @var Config
     * コンフィグインスタンス
     */
    private Config $config;

    /**
     * @param Config $config
     * 初期化
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $message
     * ログ出力
     */
    public function info(string $message)
    {
        Server::getInstance()->getLogger()->info($message);
        Server::getInstance()->getAsyncPool()->submitTask(
            new LogDiscordSend($this->config, $message, LogDiscordSend::SERVER)
        );
    }

    /**
     * @param $error
     * @param Player|null $player
     * エラーログ出力
     */
    public function error($error, ?Player $player = null)
    {
        Server::getInstance()->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");
        Server::getInstance()->getAsyncPool()->submitTask(
            new LogDiscordSend($this->config, "ファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}", LogDiscordSend::ERROR)
        );

        // もしPlayerインスタンスが引数に指定されていたら
        if ($player instanceof Player) {
            $player->sendMessage("§c処理中にエラーが発生しました\n{$error->getMessage()}");
        }
    }
}