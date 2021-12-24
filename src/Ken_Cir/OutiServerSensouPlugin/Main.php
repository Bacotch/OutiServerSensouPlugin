<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Commands\OutiWatchCommand;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\PluginAutoUpdateChecker;
use Ken_Cir\OutiServerSensouPlugin\Threads\PMMPAutoUpdateChecker;
use pocketmine\lang\Language;
use pocketmine\Server;
use poggit\libasynql\libasynql;
use Ken_Cir\OutiServerSensouPlugin\Database\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\Backup;
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

/**
 * プラグインメインクラス
 */
class Main extends PluginBase
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
     * プラグイン永続データ
     * @var Config
     */
    private Config $pluginData;

    /**
     * @var OutiServerLogger
     * プラグイン用ログ出力
     */
    private OutiServerLogger $logger;

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
     * @var RoleDataManager
     * 派閥ロールデータマネージャー
     */
    private RoleDataManager $factionRoleDataManager;

    /**
     * 土地データマネージャー
     * @var LandDataManager
     */
    private LandDataManager $landDataManager;

    /**
     * プラグインがロードされた時に呼び出される
     */
    public function onLoad(): void
    {
        self::$instance = $this;
        $this->enabled = false;
    }

    /**
     * プラグインが有効化された時に呼び出される
     */
    public function onEnable(): void
    {
        try {
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
            $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

            // ---色々初期化---
            $this->logger = new OutiServerLogger();
            $this->InitializeDatabase();
            $this->InitializeDatabase();
            $this->InitializeThreads();

            $this->getServer()->getAsyncPool()->submitTask(new Backup());

            $this->discord_client->sendChatMessage("サーバーが起動しました！");
            $this->enabled = true;
        } catch (Error | Exception $error) {
            $this->enabled = false;
            $this->getLogger()->error("エラーが発生しました\n{$error->getTraceAsString()}");
            $this->getLogger()->emergency("致命的エラーが発生しました\nプラグインを無効化します");
            $this->database->close();
            $this->discord_client->shutdown();
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    /**
     * プラグインが無効化された時に呼び出される
     */
    public function onDisable(): void
    {
        try {
            if (!$this->enabled) return;
            $this->getLogger()->info("キャッシュデータをdbファイルに書き込んでいます...\nこれには時間がかかることがあります");
            $this->database->waitAll();
            $this->database->close();
            $this->discord_client->sendChatMessage("サーバーが停止しました");
            $this->discord_client->shutdown();
            if (ob_get_contents()) {
                ob_flush();
                ob_end_clean();
            }
            $this->pluginData->save();
        } catch (Error | Exception $error) {
            $this->getLogger()->error("エラーが発生しました\n{$error->getMessage()}");
            $this->getLogger()->emergency("プラグイン無効化中にエラーが発生しました\nプラグインが正常に無効化できていない可能性があります");
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
    public function getPluginLogger(): OutiServerLogger
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

    /**
     * @return RoleDataManager
     * 派閥ロールデータマネージャーを返す
     */
    public function getFactionRoleDataManager(): RoleDataManager
    {
        return $this->factionRoleDataManager;
    }

    /**
     * 土地データマネージャーを返す
     * @return LandDataManager
     */
    public function getLandDataManager(): LandDataManager
    {
        return $this->landDataManager;
    }

    /**
     * データベース初期化処理まとめ
     */
    private function InitializeDatabase(): void
    {
        $databaseConfig = new Config($this->getDataFolder() . "database.yml", Config::YAML);
        $this->database = libasynql::create($this, $databaseConfig->get("database"), [
            "sqlite" => "sqlite.sql"
        ]);
        $this->database->executeGeneric("lands.drop");
        $this->database->waitAll();
        $this->database->executeGeneric("players.init");
        $this->database->executeGeneric("factions.init");
        $this->database->executeGeneric("mails.init");
        $this->database->executeGeneric("roles.init");
        $this->database->executeGeneric("lands.init");
        $this->database->waitAll();
        $this->playerDataManager = new PlayerDataManager();
        $this->factionDataManager = new FactionDataManager();
        $this->mailManager = new MailManager();
        $this->factionRoleDataManager = new RoleDataManager();
        $this->landDataManager = new LandDataManager();
        $this->database->waitAll();
    }

    /**
     * スレッド初期化処理まとめ
     */
    private function InitializeThreads(): void
    {
        $this->discord_client = new DiscordBot($this->config->get("Discord_Bot_Token", ""), $this->getFile(), $this->config->get("Discord_Guild_Id", ""), $this->config->get("Discord_Console_Channel_Id", ""), $this->config->get("Discord_MinecraftChat_Channel_Id", ""));

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
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender($this->getServer(), new Language("jpn")), $message);
                }

                foreach ($this->discord_client->fetchChatMessages() as $message) {
                    $content = $message["content"];
                    if ($content === "") continue;
                    $this->getServer()->broadcastMessage("[Discord:{$message["username"]}] $content");
                }
            }
        ), 5, 1);

        $this->getServer()->getCommandMap()->registerAll(
            $this->getName(),
            [
                new OutiWatchCommand($this)
            ]
        );

        $this->getScheduler()->scheduleRepeatingTask(new PlayerBackGround(), 5);

        if ($this->config->get("plugin_auto_update_enable", true)) {
            $this->getScheduler()->scheduleRepeatingTask(new PMMPAutoUpdateChecker(), 20 * 600);
        }

        // TODO: プラグインも自動アップデートができるようにする
        // $this->getServer()->getAsyncPool()->submitTask(new PluginAutoUpdateChecker());
    }
}
