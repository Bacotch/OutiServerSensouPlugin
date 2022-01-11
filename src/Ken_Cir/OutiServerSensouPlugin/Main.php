<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin;

use JsonException;
use Ken_Cir\OutiServerSensouPlugin\Cache\PlayerCache\PlayerCacheManager;
use Ken_Cir\OutiServerSensouPlugin\Commands\OutiWatchCommand;
use Ken_Cir\OutiServerSensouPlugin\Commands\RestartCommand;
use Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData\LandConfigDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\PluginAutoUpdateChecker;
use Ken_Cir\OutiServerSensouPlugin\Threads\PMMPAutoUpdateChecker;
use pocketmine\lang\Language;
use pocketmine\Server;
use poggit\libasynql\generic\GenericStatementFileParser;
use poggit\libasynql\libasynql;
use Ken_Cir\OutiServerSensouPlugin\Database\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\DiscordBot;
use Ken_Cir\OutiServerSensouPlugin\Threads\PlayerBackGround;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerLogger;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use function ob_end_clean;
use function ob_flush;
use function ob_get_contents;
use function ob_start;
use function file_exists;
use function mkdir;

/**
 * プラグインメインクラス
 */
class Main extends PluginBase
{
    /**
     * プラグインインスタンス
     * @var Main $this
     */
    private static self $instance;

    /**
     * プラグインコンフィグ
     * @var Config
     */
    private Config $config;

    /**
     * プラグイン永続データ
     * @var Config
     */
    private Config $pluginData;

    /**
     * @var OutiServerLogger
     * プラグイン用ログ出力
     * @var OutiServerLogger
     */
    private OutiServerLogger $outiServerLogger;

    /**
     * DiscordBot
     * @var DiscordBot
     */
    private DiscordBot $discordClient;

    /**
     * DB接続
     * @var DataConnector
     */
    private DataConnector $database;

    /**
     * プラグインがロードされた時に呼び出される
     */
    public function onLoad(): void
    {
        self::$instance = $this;
    }

    /**
     * プラグインが有効化された時に呼び出される
     */
    public function onEnable(): void
    {
        // ---バックアップ用のフォルダがなければ作成する---
        if (!file_exists(Main::getInstance()->getDataFolder() . "backups/")) {
            mkdir(Main::getInstance()->getDataFolder() . "backups/");
        }

        // ---リソースを保存---
        $this->saveResource("config.yml");
        $this->saveResource("database.yml");
        $this->saveResource("data.yml");

        // ---プラグインコンフィグを読み込む---
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->pluginData = new Config($this->getDataFolder() . "data.yml", Config::YAML);

        // ---イベント処理クラスを登録--
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);

        // ---Logger初期化---
        $this->outiServerLogger = new OutiServerLogger();

        // ---db初期化---
        $databaseConfig = new Config($this->getDataFolder() . "database.yml", Config::YAML);
        $this->database = libasynql::create($this, $databaseConfig->get("database"), [
            "sqlite" => "sqlite.sql"
        ]);

        $this->database->executeGeneric("outiserver.players.init");
        $this->database->executeGeneric("outiserver.factions.init");
        $this->database->executeGeneric("outiserver.mails.init");
        $this->database->executeGeneric("outiserver.roles.init");
        $this->database->executeGeneric("outiserver.lands.init");
        $this->database->executeGeneric("outiserver.landconfigs.init");
        $this->database->waitAll();
        $this->getLogger()->info("データベースを初期化しています...");
        PlayerDataManager::createInstance();
        FactionDataManager::createInstance();
        MailManager::createInstance();
        RoleDataManager::createInstance();
        LandDataManager::createInstance();
        LandConfigDataManager::createInstance();
        $this->database->waitAll();
        $this->getLogger()->info("データベースの初期化が完了しました");

        // ---キャッシュ初期化---
        PlayerCacheManager::createInstance();

        // ---スレッド初期化---
        // ---DiscordBot処理用---
        $this->discordClient = new DiscordBot($this->config->get("Discord_Bot_Token", ""), $this->getFile(), $this->config->get("Discord_Guild_Id", ""), $this->config->get("Discord_Console_Channel_Id", ""), $this->config->get("Discord_MinecraftChat_Channel_Id", ""));
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function (): void {
                $this->getLogger()->info("出力バッファリングを開始致します。");
                ob_start();
            }
        ), 10);
        $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
            function (): void {
                if (!$this->discordClient->started) return;
                $string = ob_get_contents();

                if ($string === "") return;
                $this->discordClient->sendConsoleMessage($string);
                ob_flush();
            }
        ), 10, 1);
        $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
            function (): void {
                foreach ($this->discordClient->fetchConsoleMessages() as $message) {
                    if ($message === "") continue;
                    Server::getInstance()->dispatchCommand(new ConsoleCommandSender($this->getServer(), new Language("jpn")), $message);
                }

                foreach ($this->discordClient->fetchChatMessages() as $message) {
                    $content = $message["content"];
                    if ($content === "") continue;
                    Server::getInstance()->broadcastMessage("[Discord:{$message["username"]}] $content");
                }
            }
        ), 5, 1);
        // プレイヤーバックグラウンド処理タスク登録
        $this->getScheduler()->scheduleRepeatingTask(new PlayerBackGround(), 5);
        if ($this->config->get("plugin_auto_update_enable", true)) {
            $this->getScheduler()->scheduleRepeatingTask(new PMMPAutoUpdateChecker(), 20 * 600);
        }
        // TODO: プラグインも自動アップデートができるようにする
        // $this->getServer()->getAsyncPool()->submitTask(new PluginAutoUpdateChecker());

        // ---コマンド登録---
        $this->getServer()->getCommandMap()->registerAll(
            $this->getName(),
            [
                new OutiWatchCommand($this),
                new RestartCommand($this)
            ]
        );

        // 初期化完了！
        $this->discordClient->sendChatMessage("サーバーが起動しました！");
    }

    /**
     * プラグインが無効化された時に呼び出される
     */
    public function onDisable(): void
    {
        if (isset($this->database)) {
            $this->getLogger()->info("キャッシュデータをdbファイルに書き込んでいます...\nこれには時間がかかることがあります");
            $this->database->waitAll();
            $this->database->close();
        }

        if (isset($this->discordClient) and $this->discordClient->started) {
            $this->discordClient->sendChatMessage("サーバーが停止しました");
            $this->discordClient->shutdown();
        }

        if (ob_get_contents()) {
            ob_flush();
            ob_end_clean();
        }

        if (isset($this->pluginData)) {
            try {
                $this->pluginData->save();
            }
            catch (JsonException) {}
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
     * @return Config
     */
    public function getPluginData(): Config
    {
        return $this->pluginData;
    }

    /**
     * @return OutiServerLogger
     * このプラグイン用のLoggerを返す
     */
    /**
     * @return OutiServerLogger
     */
    public function getOutiServerLogger(): OutiServerLogger
    {
        return $this->outiServerLogger;
    }

    /**
     * @return DiscordBot
     * DiscordBotClientを返す
     */
    public function getDiscordClient(): DiscordBot
    {
        return $this->discordClient;
    }

    /**
     * @return DataConnector
     * db接続オブジェクトを返す
     */
    public function getDatabase(): DataConnector
    {
        return $this->database;
    }
}
