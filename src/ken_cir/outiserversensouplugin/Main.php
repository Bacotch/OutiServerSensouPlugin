<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin;

use JsonException;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\commands\OutiWatchCommand;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\schedulemessagedata\ScheduleMessageDataManager;
use ken_cir\outiserversensouplugin\entitys\Skeleton;
use ken_cir\outiserversensouplugin\threads\PMMPAutoUpdateChecker;
use ken_cir\outiserversensouplugin\threads\ScheduleMessage;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\SpawnEgg;
use pocketmine\lang\Language;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\world\World;
use poggit\libasynql\libasynql;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\threads\DiscordBot;
use ken_cir\outiserversensouplugin\threads\PlayerBackGround;
use ken_cir\outiserversensouplugin\utilitys\OutiServerLogger;
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
final class Main extends PluginBase
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
        $this->database->executeGeneric("outiserver.schedulemessages.init");
        $this->database->waitAll();
        PlayerDataManager::createInstance();
        FactionDataManager::createInstance();
        MailDataManager::createInstance();
        RoleDataManager::createInstance();
        LandDataManager::createInstance();
        LandConfigDataManager::createInstance();
        ScheduleMessageDataManager::createInstance();
        $this->database->waitAll();

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
        $this->getScheduler()->scheduleRepeatingTask(new ScheduleMessage(), $this->config->get("scheduleMessageDelay", 300) * 20);

        // ---コマンド登録---
        $this->getServer()->getCommandMap()->registerAll(
            $this->getName(),
            [
                new OutiWatchCommand($this)
            ]
        );

        // ---エンティティ系登録
        EntityFactory::getInstance()->register(Skeleton::class, function(World $world,CompoundTag $nbt): Skeleton{
            return new Skeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        },['Skeleton', 'minecraft:skeleton'], EntityLegacyIds::SKELETON);

        ItemFactory::getInstance()->register(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SKELETON), "Skeleton Spawn Egg") extends SpawnEgg{
            public function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Skeleton(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        });

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
