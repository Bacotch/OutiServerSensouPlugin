<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Forms\OutiWatchForm;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Threads\AutoUpdateWait;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\UpdateNotifyEvent;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function str_starts_with;
use function file_put_contents;
use function extension_loaded;
use function register_shutdown_function;
use function unlink;
use function rename;
use function count;
use const DIRECTORY_SEPARATOR;

/**
 * PMMPイベント処理クラス
 */
class EventListener implements Listener
{
    /**
     * @var array
     * おうちウォッチを二重で表示させない用
     */
    private array $check;

    public function __construct()
    {
        $this->check = [];
    }

    /**
     * PMMPアップデート通知イベント
     * @param UpdateNotifyEvent $event
     * @return void
     */
    public function onUpdateNotify(UpdateNotifyEvent $event): void
    {
        if (!Main::getInstance()->getPluginConfig()->get("pmmp_auto_update_enable", true)) return;
        elseif (!extension_loaded('pcntl') or DIRECTORY_SEPARATOR !== '/') return;

        $updateInfos = $event->getUpdater()->getUpdateInfo();
        if ($updateInfos->git_commit === Main::getInstance()->getPluginData()->get("pmmpLastUpdateCommitHash", "")) return;
        elseif ($updateInfos->is_dev) return;
        elseif (!str_starts_with($updateInfos->base_version, "4")) {
            Main::getInstance()->getLogger()->warning("PMMP自動アップデートに失敗しました、4x以外のPMMP");
            return;
        }
        elseif (!str_starts_with($updateInfos->php_version, "8.0")) {
            Main::getInstance()->getLogger()->warning("PMMP自動アップデートに失敗しました、PHPのバージョンが8.0以外");
            return;
        }

        Main::getInstance()->getLogger()->alert("PMMPの自動アップデートの準備をしています...");
        Main::getInstance()->getLogger()->alert("全てのプラグインを無効化しています");
        foreach (Server::getInstance()->getPluginManager()->getPlugins() as $plugin) {
            if ($plugin->getName() !== Main::getInstance()->getName()) {
                Server::getInstance()->getPluginManager()->disablePlugin($plugin);
            }
        }

        $result = Internet::getURL($updateInfos->download_url);
        file_put_contents(Server::getInstance()->getDataPath() . "PocketMine-MP1.phar", $result->getBody());
        Main::getInstance()->getPluginData()->set("pmmpLastUpdateCommitHash", $updateInfos->git_commit);

        // シャットダウン関数を登録
        register_shutdown_function(function(){
            unlink(Server::getInstance()->getDataPath() . "PocketMine-MP.phar");
            rename(Server::getInstance()->getDataPath() . "PocketMine-MP1.phar",Server::getInstance()->getDataPath() . "PocketMine-MP.phar");
            pcntl_exec("./start.sh");
        });

        if (count(Server::getInstance()->getOnlinePlayers()) < 1) {
            Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！サーバーを再起動しています...");
            Server::getInstance()->shutdown();
        }
        else {
            Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！アップデートを待機しています...");
            Server::getInstance()->broadcastMessage("§a[システム] §e[警告] §fサーバーアップデートの準備が整いました！あと10分でサーバーは再起動されます");
            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new AutoUpdateWait(), 20);
        }
    }

    /**
     * @param PlayerLoginEvent $event
     * プレイヤーログインイベント
     */
    public function onPlayerLogin(PlayerLoginEvent $event)
    {
        try {
            Server::getInstance()->getUpdater()->doCheck();
            $player = $event->getPlayer();
            PlayerDataManager::getInstance()->create($player);
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $player_data->addIp($player->getNetworkSession()->getIp());
            OutiServerPluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Player_Webhook", ""), "Player {$player->getName()} が\nワールド: {$player->getWorld()->getDisplayName()}\nX座標: {$player->getPosition()->getX()}\nY座標: {$player->getPosition()->getY()}\nZ座標: {$player->getPosition()->getZ()}\nにログインしました");
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * @param PlayerJoinEvent $event
     * プレイヤー参加イベント
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        try {
            $player = $event->getPlayer();
            if (($mail_count = MailManager::getInstance()->unReadCount($player->getName())) > 0) {
                $player->sendMessage("§a未読メールが{$mail_count}件あります");
            }

            Main::getInstance()->getDiscordClient()->sendChatMessage("{$player->getName()}がサーバーに参加しました");
            OutiServerPluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Player_Webhook", ""), "Player {$player->getName()}\nIP {$player->getNetworkSession()->getIp()} がサーバーに参加しました");
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     * プレイヤー退出イベント
     */
    public function onPlayerQuit(PlayerQuitEvent $event)
    {
        try {
            $player = $event->getPlayer();
            unset($this->check[$player->getName()]);
            Main::getInstance()->getDiscordClient()->sendChatMessage("{$player->getName()}がサーバーから退出しました");
            OutiServerPluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Player_Webhook", ""), "Player {$player->getName()}\nIP {$player->getNetworkSession()->getIp()} がサーバーから退出しました");
        } catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * @param PlayerChatEvent $event
     * プレイヤーチャットイベント
     */
    public function onPlayerChat(PlayerChatEvent $event)
    {
        try {
            $player = $event->getPlayer();
            $message = $event->getMessage();
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            if ($player_data->getFaction() === -1) {
                $event->setFormat("§f[無所属][{$player->getName()}] $message");
            } else {
                $faction = FactionDataManager::getInstance()->get($player_data->getFaction());
                $color = OutiServerPluginUtils::getChatColor($faction->getColor());
                $event->setFormat("{$color}[{$faction->getName()}]§f[{$player->getName()}] $message");
            }

            // 派閥専用チャットの場合は
            if ($player_data->getChatmode() !== -1) {
                $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($player_data->getFaction());
                $server = Server::getInstance();

                // 同じ派閥にメッセージを送る
                if ($faction_players) {
                    foreach ($faction_players as $f_p) {
                        $faction_player = $server->getPlayerExact($f_p->getName());
                        $faction_player->sendMessage($event->getFormat());
                    }
                }

                // OP持ちにもメッセージを送る
                foreach ($server->getOnlinePlayers() as $onlinePlayer) {
                    if (!$server->getOps()->get($onlinePlayer->getName())) continue;
                    // メッセージ送信済みの場合は
                    elseif (PlayerDataManager::getInstance()->get($onlinePlayer->getName())->getFaction() === $player_data->getFaction()) continue;
                    $onlinePlayer->sendMessage($event->getFormat());
                }

                // ログに記録
                Main::getInstance()->getLogger()->info($event->getFormat());
                $event->cancel();
                return;
            }

            Main::getInstance()->getDiscordClient()->sendChatMessage($event->getFormat());
        } catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * プレイヤーがブロック（空気を含む？）を操作またはタッチしたときに呼び出される
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($event->getAction() === 1) {
            if (!isset($this->check[$player->getName()]) and $item->getName() === "OutiWatch") {
                $this->check[$player->getName()] = true;
                $form = new OutiWatchForm();

                $form->execute($player, $this);
            }
        }

    }

    /**
     * @param string $name
     */
    public function unsetCheck(string $name): void
    {
        unset($this->check[$name]);
    }
}