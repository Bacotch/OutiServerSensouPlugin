<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Commands\CreateFactionCommand;
use Ken_Cir\OutiServerSensouPlugin\Commands\SendMailCommand;
use Ken_Cir\OutiServerSensouPlugin\Tasks\DiscordBot;
use Ken_Cir\OutiServerSensouPlugin\Tasks\PlayerInfoScoreBoard;
use Ken_Cir\OutiServerSensouPlugin\Utils\Database;
use Ken_Cir\OutiServerSensouPlugin\Utils\Logger;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

/**
 * プラグインメインクラス
 */
class Main extends PluginBase
{
    /**
     * @var Config
     * プラグインコンフィグ
     */
    public Config $config;

    /**
     * @var Logger
     * プラグイン用ログ出力
     */
    public Logger $logger;

    /**
     * @var DiscordBot
     * DiscordBotClientオブジェクト
     */
    public DiscordBot $discord_client;

    /**
     * @var Database
     * Databaseオブジェクト
     */
    public Database $database;

    /**
     * @var bool
     * プラグインが正常に有効化されたかどうか
     */
    private bool $enabled;

    /**
     * プラグインが有効化された時に呼び出される
     */
    public function onEnable()
    {
        try {
            $this->enabled = false;

            // ---リソースを保存---
            $this->saveResource("config.yml");

            // プラグインコンフィグを読み込む
            $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

            $token = $this->config->get("Discord_Bot_Token", "");
            if ($token === "") {
                $this->getLogger()->error("config.yml Discord_Bot_Token が設定されていません");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }

            // クラスインスタンスを生成
            $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
            $this->logger = new Logger($this->config);
            $this->discord_client = new DiscordBot($token, $this->getFile(), $this->config->get("Discord_Guild_Id", ""), $this->config->get("Discord_Console_Channel_Id", ""), $this->config->get("Discord_MinecraftChat_Channel_Id", ""));
            $this->database = new Database($this, $this->getDataFolder() . 'outiserveplugin.db');
            unset($token);

            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (int $currentTick): void {
                    $this->getLogger()->info("出力バッファリングを開始致します。");
                    ob_start();
                }
            ), 10);

            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
                function (int $currentTick): void {
                    if (!$this->discord_client->started) return;
                    $string = ob_get_contents();

                    if ($string === "") return;
                    $this->discord_client->sendConsoleMessage($string);
                    ob_flush();
                }
            ), 10, 1);

            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
                function (int $currentTick): void {
                    foreach ($this->discord_client->fetchConsoleMessages() as $message) {
                        if ($message === "") continue;
                        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $message);
                    }

                    foreach ($this->discord_client->fetchChatMessages() as $message) {
                        $content = $message["content"];
                        if ($content === "") continue;
                        $this->getServer()->broadcastMessage("[Discord:{$message["username"]}] $content");
                    }
                }
            ), 5, 1);

            $this->getServer()->getCommandMap()->registerAll("OutiServerSensouPlugin", [new CreateFactionCommand($this), new SendMailCommand($this)]);

            $this->getScheduler()->scheduleRepeatingTask(new PlayerInfoScoreBoard($this), 5);

            $this->logger->info("プラグインが正常に有効化されました");
            $this->discord_client->sendChatMessage("サーバーが起動しました！");
            $this->enabled = true;
        } catch (Error | Exception $error) {
            $this->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");
            $this->getLogger()->info("§c回復不可能な致命的エラーが発生しました\nプラグインを無効化します");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function onDisable()
    {
        try {
            if (!$this->enabled) return;
            $this->database->close();
            $this->discord_client->sendChatMessage("サーバーが停止しました");
            $this->discord_client->shutdown();
            ob_flush();
            ob_end_clean();
            $this->logger->info("プラグインが正常に無効化されました");
        } catch (Error | Exception $error) {
            $this->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");
            $this->getLogger()->info("§cプラグイン無効化中にエラーが発生しました\nプラグインが正常に無効化できていない可能性があります");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }
}