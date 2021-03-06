<?php

/**
 * おうち鯖戦争プラグイン
 *
 * 開発者
 * Ken_Cir
 *
 * プログラミングヘルプ
 * SekiTonami
 * ばたすこ
 *
 * Special Thanks
 * おうち鯖の住人
 */

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\cache\warcache\WarCacheManager;
use ken_cir\outiserversensouplugin\commands\BanAllCOmmand;
use ken_cir\outiserversensouplugin\commands\ItemsCommand;
use ken_cir\outiserversensouplugin\commands\OutiServerCommand;
use ken_cir\outiserversensouplugin\commands\StartWarCommand;
use ken_cir\outiserversensouplugin\commands\WorldBackupCommand;
use ken_cir\outiserversensouplugin\database\adminshopdata\AdminShopDataManager;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopDataManager;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\database\schedulemessagedata\ScheduleMessageDataManager;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use ken_cir\outiserversensouplugin\entitys\Skeleton;
use ken_cir\outiserversensouplugin\entitys\Zombie;
use ken_cir\outiserversensouplugin\network\OutiServerSocket;
use ken_cir\outiserversensouplugin\tasks\AdminShopFluctuation;
use ken_cir\outiserversensouplugin\tasks\Backup;
use ken_cir\outiserversensouplugin\tasks\PlayerInfoScoreBoard;
use ken_cir\outiserversensouplugin\tasks\ScheduleMessage;
use ken_cir\outiserversensouplugin\tasks\WarCheckerTask;
use ken_cir\outiserversensouplugin\utilitys\OutiServerLogger;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\SpawnEgg;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
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
     * DB接続
     * @var DataConnector
     */
    private DataConnector $database;

    /**
     * プラグインがロードされた時に呼び出される
     */
    protected function onLoad(): void
    {
        self::$instance = $this;
    }

    /**
     * プラグインが有効化された時に呼び出される
     */
    protected function onEnable(): void
    {
        // アイテム名翻訳用
        //  @unlink(Main::getInstance()->getDataFolder() . "test.json");

        // ---バックアップ用のフォルダがなければ作成する---
        if (!file_exists(Main::getInstance()->getDataFolder() . "backups/")) {
            mkdir(Main::getInstance()->getDataFolder() . "backups/");
        }

        // ワールドバックアップ用のフォルダがなければ作成する
        if (!file_exists(Main::getInstance()->getDataFolder() . "worldBackups/")) {
            mkdir(Main::getInstance()->getDataFolder() . "worldBackups/");
        }

        // Commandoを機能させるために必要らしい
        if (!PacketHooker::isRegistered()) {
            try {
                PacketHooker::register($this);
            } catch (HookAlreadyRegistered) {
            }
        }

        // ---リソースを保存---
        $this->saveResource("config.yml");
        $this->saveResource("database.yml");
        $this->saveResource("data.yml");
        $this->saveResource("items_map.json");
        $this->saveResource("test.json");

        // ---プラグインコンフィグを読み込む---
        $this->config = new Config("{$this->getDataFolder()}config.yml", Config::YAML);
        $this->pluginData = new Config("{$this->getDataFolder()}data.yml", Config::YAML);

        // ---イベント処理クラスを登録--
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new LoggerHandler($this), $this);

        // ---Logger初期化---
        $this->outiServerLogger = new OutiServerLogger();

        // ---データベース初期化---
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
        $this->database->executeGeneric("outiserver.chestshops.init");
        $this->database->executeGeneric("outiserver.adminshops.init");
        $this->database->executeGeneric("outiserver.wars.init");
        $this->database->executeGeneric("outiserver.war_historys.init");
        $this->database->waitAll();
        (new PlayerDataManager($this->database));
        (new FactionDataManager($this->database));
        (new MailDataManager($this->database));
        (new RoleDataManager($this->database));
        (new LandDataManager($this->database));
        (new LandConfigDataManager($this->database));
        (new ScheduleMessageDataManager($this->database));
        (new ChestShopDataManager($this->database));
        (new AdminShopDataManager($this->database));
        (new WarDataManager($this->database));
        $this->database->waitAll();

        // --- キャッシュ初期化 ---
        PlayerCacheManager::createInstance();
        (new WarCacheManager());

        // --- Task登録 ---
        // プレイヤーのスコアボード表示Task
        $this->getScheduler()->scheduleRepeatingTask(new PlayerInfoScoreBoard(), 5);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                    // おうちウォッチを持っていなかったら渡す
                    $item = ItemFactory::getInstance()->get(1002);
                    $item->setCustomName("OutiWatch");
                    if (!$onlinePlayer->getInventory()->contains($item)) {
                        $onlinePlayer->getInventory()->addItem($item);
                    }
                }
            }
        ), 10);

        // 自動アップデートチェックTask
        if ($this->config->get("plugin_auto_update_enable", true)) {
            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(
                function (): void {
                    Server::getInstance()->getUpdater()->doCheck();
                }
            ), $this->config->get("autoUpdateCheckDealy", 600) * 20, $this->config->get("autoUpdateCheckDealy", 600) * 20);
        }

        // 定期メッセージ
        $this->getScheduler()->scheduleDelayedRepeatingTask(new ScheduleMessage(),
            $this->config->get("scheduleMessageDelay", 300) * 20,
            $this->config->get("scheduleMessageDelay", 300) * 20);

        // バックアップ
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                Server::getInstance()->getAsyncPool()->submitTask(new Backup());
            }
        ), $this->config->get("backup_delay", 3600) * 20);

        // アドミンショップの値段変動
        $this->getScheduler()->scheduleRepeatingTask(new AdminShopFluctuation($this->config->get("adminshop_fluctuation_count", 64)),
            $this->config->get("adminshop_fluctuation_delay", 3600) * 20);

        $this->getScheduler()->scheduleRepeatingTask(new WarCheckerTask(), $this->config->get("war_check_delay", 20) * 20);

        // --- コマンド登録 ---
        $this->getServer()->getCommandMap()->registerAll($this->getName(),
            [
                new BanAllCOmmand(),
                new ItemsCommand(),
                new OutiServerCommand($this),
                new WorldBackupCommand($this),
                new StartWarCommand($this)
            ]);

        // ---エンティティ系登録---
        EntityFactory::getInstance()->register(Skeleton::class, function (World $world, CompoundTag $nbt): Skeleton {
            return new Skeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['Skeleton', 'minecraft:skeleton'], EntityLegacyIds::SKELETON);
        EntityFactory::getInstance()->register(Zombie::class, function (World $world, CompoundTag $nbt): Zombie {
            return new Zombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['Zombie', 'minecraft:zombie'], EntityLegacyIds::ZOMBIE);

        // ---アイテム系登録---
        ItemFactory::getInstance()->register(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SKELETON), "Skeleton Spawn Egg") extends SpawnEgg {
            public function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch): Entity
            {
                return new Skeleton(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        },
            true);
        ItemFactory::getInstance()->register(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::ZOMBIE), "Zombie Spawn Egg") extends SpawnEgg {
            public function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch): Entity
            {
                return new Zombie(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        },
            true);

        // API
        Server::getInstance()->getNetwork()->registerInterface(new OutiServerSocket(
            Server::getInstance()->getIp(),
            19132,
            Server::getInstance()->getTickSleeper()
        ));
    }

    /**
     * プラグインが無効化された時に呼び出される
     */
    protected function onDisable(): void
    {
        if (isset($this->database)) {
            $this->database->waitAll();
            $this->database->close();
        }

        if (isset($this->pluginData)) {
            try {
                $this->pluginData->save();
            } catch (\JsonException) {
            }
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
     * @return DataConnector
     * db接続オブジェクトを返す
     */
    public function getDatabase(): DataConnector
    {
        return $this->database;
    }
}
