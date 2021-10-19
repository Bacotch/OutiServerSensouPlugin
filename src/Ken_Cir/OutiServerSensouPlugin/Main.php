<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Commands\OutiWatchCommand;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\libasynql;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Tasks\DiscordBot;
use Ken_Cir\OutiServerSensouPlugin\Tasks\PlayerInfoScoreBoard;
use Ken_Cir\OutiServerSensouPlugin\Utils\Logger;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\DataConnector;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

/**
 * プラグインメインクラス
 */
final class Main extends PluginBase
{
    /**
     * @var Main $this
     */
    private static self $instance;

    /**
     * @var Config
     * プラグインコンフィグ
     */
    private Config $config;

    /**
     * @var Logger
     * プラグイン用ログ出力
     */
    private Logger $logger;

    /**
     * @var DiscordBot
     * DiscordBotClientオブジェクト
     */
    private DiscordBot $discord_client;

    /**
     * @var DataConnector
     * Databaseオブジェクト
     */
    private DataConnector $database;

    /**
     * @var bool
     * プラグインが正常に有効化されたかどうか
     */
    private bool $enabled;

    /**
     * @var PlayerDataManager
     * プレイヤーデータマネージャー
     */
    private PlayerDataManager $playerDataManager;

    /**
     * @var FactionDataManager
     * 派閥データマネージャー
     */
    private FactionDataManager $factionDataManager;

    /**
     * @var MailManager
     * メールデータマネージャー
     */
    private MailManager $mailManager;

    /**
     * プラグインがロードされた時に呼び出される
     */
    public function onLoad()
    {
        self::$instance = $this;
        $this->enabled = false;
    }

    /**
     * プラグインが有効化された時に呼び出される
     */
    public function onEnable()
    {
        try {
            // ---リソースを保存---
            $this->saveResource("config.yml");

            // ---プラグインコンフィグを読み込む---
            $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

            // ---DiscordBotTokenが設定されているかの確認
            $token = $this->config->get("Discord_Bot_Token", "");
            if ($token === "") {
                $this->getLogger()->error("config.yml Discord_Bot_Token が設定されていません");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }

            // ---イベント処理クラスを登録--
            $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

            // ---各クラスインスタンスを生成---
            $this->logger = new Logger();
            $this->discord_client = new DiscordBot($token, $this->getFile(), $this->config->get("Discord_Guild_Id", ""), $this->config->get("Discord_Console_Channel_Id", ""), $this->config->get("Discord_MinecraftChat_Channel_Id", ""));
            unset($token);
            $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
                "sqlite" => "sqlite.sql"
            ]);
            /*
            $this->database->executeGeneric("players.drop");
            $this->database->executeGeneric("factions.drop");
            $this->database->waitAll();
            */
            $this->database->executeGeneric("players.init");
            $this->database->executeGeneric("factions.init");
            $this->database->executeGeneric("mails.init");
            $this->database->executeGeneric("roles.init");
            $this->database->waitAll();
            $this->playerDataManager = new PlayerDataManager();
            $this->factionDataManager = new FactionDataManager();
            $this->mailManager = new MailManager();

            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (): void {
                    $this->getLogger()->info("出力バッファリングを開始致します。");
                    ob_start();
                }
            ), 10);

            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
                function (): void {
                    if (!$this->discord_client->started) return;
                    $string = ob_get_contents();

                    if ($string === "") return;
                    $this->discord_client->sendConsoleMessage($string);
                    ob_flush();
                }
            ), 10, 1);

            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
                function (): void {
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

            $this->getServer()->getCommandMap()->registerAll($this->getName(),
                [
                    new OutiWatchCommand($this)
                ]);

            $this->getScheduler()->scheduleRepeatingTask(new PlayerInfoScoreBoard(), 5);

            $this->discord_client->sendChatMessage("サーバーが起動しました！");
            $this->enabled = true;
        }
        catch (Error | Exception $error) {
            $this->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");
            $this->getLogger()->info("§c回復不可能な致命的エラーが発生しました\nプラグインを無効化します");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function onDisable()
    {
        try {
            if (!$this->enabled) return;
            $this->database->waitAll();
            $this->database->close();
            $this->discord_client->sendChatMessage("サーバーが停止しました");
            $this->discord_client->shutdown();
            ob_flush();
            ob_end_clean();
        }
        catch (Error | Exception $error) {
            $this->getLogger()->error("エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}");
            $this->getLogger()->info("§cプラグイン無効化中にエラーが発生しました\nプラグインが正常に無効化できていない可能性があります");
        }
    }

    /**
     * @return Main
     * プラグインインスタンスを返す
     */
    public static function getInstance(): Main
    {
        return self::$instance;
    }

    /**
     * @return Config
     * プラグインConfigインスタンスを返す
     */
    public function getPluginConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return Logger
     * このプラグイン用のLoggerを返す
     */
    public function getPluginLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @return DiscordBot
     * DiscordBotClientを返す
     */
    public function getDiscordClient(): DiscordBot
    {
        return $this->discord_client;
    }

    /**
     * @return DataConnector
     * db接続オブジェクトを返す
     */
    public function getDatabase(): DataConnector
    {
        return $this->database;
    }

    /**
     * @return PlayerDataManager
     * プレイヤーデータマネージャーを返す
     */
    public function getPlayerDataManager(): PlayerDataManager
    {
        return $this->playerDataManager;
    }

    /**
     * @return FactionDataManager
     * 派閥管理マネージャーを返す
     */
    public function getFactionDataManager(): FactionDataManager
    {
        return $this->factionDataManager;
    }

    /**
     * @return MailManager
     * メールデータマネージャーを返す
     */
    public function getMailManager(): MailManager
    {
        return $this->mailManager;
    }
}