<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\entitys\Skeleton;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\threads\AutoUpdateWait;
use ken_cir\outiserversensouplugin\utilitys\OutiServerPluginUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\UpdateNotifyEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Internet;
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
final class EventListener implements Listener
{
    public function __construct()
    {
    }

    /**
     * PMMPアップデート通知イベント
     * @param UpdateNotifyEvent $event
     * @return void
     */
    public function onUpdateNotify(UpdateNotifyEvent $event): void
    {
        try {
            if (!Main::getInstance()->getPluginConfig()->get("pmmp_auto_update_enable", true)) return;
            elseif (!extension_loaded('pcntl') or DIRECTORY_SEPARATOR !== '/') return;

            $updateInfos = $event->getUpdater()->getUpdateInfo();
            if ($updateInfos->git_commit === Main::getInstance()->getPluginData()->get("pmmpLastUpdateCommitHash", "")) return;
            elseif ($updateInfos->is_dev) return;

            Main::getInstance()->getLogger()->alert("PMMPの自動アップデートの準備をしています...");

            $result = Internet::getURL($updateInfos->download_url);
            file_put_contents(Server::getInstance()->getDataPath() . "PocketMine-MP1.phar", $result->getBody());
            Main::getInstance()->getPluginData()->set("pmmpLastUpdateCommitHash", $updateInfos->git_commit);

            // シャットダウン関数を登録
            register_shutdown_function(function () {
                unlink(Server::getInstance()->getDataPath() . "PocketMine-MP.phar");
                rename(Server::getInstance()->getDataPath() . "PocketMine-MP1.phar", Server::getInstance()->getDataPath() . "PocketMine-MP.phar");
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
        catch (Error | Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }

    /**
     * @param PlayerLoginEvent $event
     * プレイヤーログインイベント
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            $player->releaseHeldItem();
            PlayerDataManager::getInstance()->create($player);
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $player_data->addIp($player->getNetworkSession()->getIp());
            PlayerCacheManager::getInstance()->create($player->getName());
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true);
        }
    }

    /**
     * @param PlayerJoinEvent $event
     * プレイヤー参加イベント
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            if (($mail_count = MailDataManager::getInstance()->unReadCount($player->getName())) > 0) {
                $player->sendMessage("§a未読メールが{$mail_count}件あります");
            }

            Main::getInstance()->getDiscordClient()->sendChatMessage("{$player->getName()}がサーバーに参加しました");
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     * プレイヤー退出イベント
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            Main::getInstance()->getDiscordClient()->sendChatMessage("{$player->getName()}がサーバーから退出しました");
            PlayerCacheManager::getInstance()->get($player->getName())->setLockOutiWatch(false);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true);
        }
    }

    /**
     * @param PlayerChatEvent $event
     * プレイヤーチャットイベント
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            $message = $event->getMessage();
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            if ($player_data->getFaction() === -1) {
                $event->setFormat("§f[無所属][{$player->getName()}] $message");
            }
            else {
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
                    if (!$server->isOp($onlinePlayer->getName())) continue;
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
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            $item = $event->getItem();
            $position = $event->getBlock()->getPosition();
            $playerData = PlayerDataManager::getInstance()->get($player->getName());

            if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                if (!PlayerCacheManager::getInstance()->get($player->getName())->isLockOutiWatch() and $item->getName() === "OutiWatch") {
                    PlayerCacheManager::getInstance()->get($player->getName())->setLockOutiWatch(true);
                    $form = new OutiWatchForm();
                    $form->execute($player);
                }

                $landConfigData = LandConfigDataManager::getInstance()->getPos($position->getFloorX(), $position->getFloorZ(), $position->getWorld()->getFolderName());
                // 土地保護データがあるなら
                if ($landConfigData !== null) {
                    $landFactionData = LandDataManager::getInstance()->get($landConfigData->getLandid());
                    // その土地の派閥のオーナーじゃない 派閥のオーナーは全権限持ちということで突破可能
                    if (FactionDataManager::getInstance()->get($landFactionData->getFactionId())->getOwner() !== $playerData->getName()) {
                        $permsManager = $landConfigData->getLandPermsManager();
                        $memberPerms = $permsManager->getMemberLandPerms($player->getName());
                        if ($memberPerms !== null and !$memberPerms->isBlockTap_Place()) {
                            $event->cancel();
                        }
                        elseif ($memberPerms === null) {
                            // position順にソートした所持ロールIDを取得する
                            $roles = $playerData->getRoles();
                            $rolePerms = null;
                            // 所持ロールの中に設定されているロールがあるか foreachで回して確認する
                            foreach ($roles as $role) {
                                if (($rolePerms = $permsManager->getRoleLandPerms($role)) !== null) break;
                            }
                            // もしあってfalseなら
                            if ($rolePerms !== null and !$rolePerms->isBlockTap_Place()) {
                                $event->cancel();
                            }
                            // ないならデフォルト
                            elseif ($rolePerms === null and !$permsManager->getDefalutLandPerms()->isBlockTap_Place()) {
                                $event->cancel();
                            }
                        }
                    }
                }
            }
        }
        catch (Error | Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }

    /**
     * プレイヤー移動イベント
     *
     * @param PlayerMoveEvent $event
     * @return void
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            $position = $event->getTo();
            $oldPostion = $event->getFrom();
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $landConfigData = LandConfigDataManager::getInstance()->getPos($position->getFloorX(), $position->getFloorZ(), $position->getWorld()->getFolderName());
            // 土地保護データがあってその敷地内に移動前にいないなら
            if ($landConfigData !== null and !LandConfigDataManager::getInstance()->getPos($oldPostion->getFloorX(), $oldPostion->getFloorZ(), $oldPostion->getWorld()->getFolderName())) {
                $landFactionData = LandDataManager::getInstance()->get($landConfigData->getLandid());
                // その土地の派閥のオーナーじゃない 派閥のオーナーは全権限持ちということで突破可能
                if (FactionDataManager::getInstance()->get($landFactionData->getFactionId())->getOwner() !== $playerData->getName()) {
                    $permsManager = $landConfigData->getLandPermsManager();
                    $memberPerms = $permsManager->getMemberLandPerms($player->getName());
                    if ($memberPerms !== null and !$memberPerms->isEntry()) {
                        $event->cancel();
                    }
                    elseif ($memberPerms === null) {
                        // position順にソートした所持ロールIDを取得する
                        $roles = $playerData->getRoles();
                        $rolePerms = null;
                        // 所持ロールの中に設定されているロールがあるか foreachで回して確認する
                        foreach ($roles as $role) {
                            if (($rolePerms = $permsManager->getRoleLandPerms($role)) !== null) break;
                        }
                        // もしあってfalseなら
                        if ($rolePerms !== null and !$rolePerms->isEntry()) {
                            $event->cancel();
                        }
                        // ないならデフォルト
                        elseif ($rolePerms === null and !$permsManager->getDefalutLandPerms()->isEntry()) {
                            $event->cancel();
                        }
                    }
                }
            }
        }
        catch (Error | Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }

    /**
     * ブロック破壊イベント
     *
     * @param BlockBreakEvent $event
     * @return void
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $position = $event->getBlock()->getPosition();

            $landConfigData = LandConfigDataManager::getInstance()->getPos($position->getFloorX(), $position->getFloorZ(), $position->getWorld()->getFolderName());
            // 土地保護データがあるなら
            if ($landConfigData !== null) {
                $landFactionData = LandDataManager::getInstance()->get($landConfigData->getLandid());
                // その土地の派閥のオーナーじゃない 派閥のオーナーは全権限持ちということで突破可能
                if (FactionDataManager::getInstance()->get($landFactionData->getFactionId())->getOwner() !== $playerData->getName()) {
                    $permsManager = $landConfigData->getLandPermsManager();
                    $memberPerms = $permsManager->getMemberLandPerms($player->getName());
                    if ($memberPerms !== null and !$memberPerms->isBlockBreak()) {
                        $event->cancel();
                    }
                    elseif ($memberPerms === null) {
                        // position順にソートした所持ロールIDを取得する
                        $roles = $playerData->getRoles();
                        $rolePerms = null;
                        // 所持ロールの中に設定されているロールがあるか foreachで回して確認する
                        foreach ($roles as $role) {
                            if (($rolePerms = $permsManager->getRoleLandPerms($role)) !== null) break;
                        }
                        // もしあってfalseなら
                        if ($rolePerms !== null and !$rolePerms->isBlockBreak()) {
                            $event->cancel();
                        }
                        // ないならデフォルト
                        elseif ($rolePerms === null and !$permsManager->getDefalutLandPerms()->isBlockBreak()) {
                            $event->cancel();
                        }
                    }
                }
            }
        }
        catch (Error | Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }

    public function onDamage(EntityDamageEvent $ev)
    {
        $entity = $ev->getEntity();
        if($ev instanceof EntityDamageByEntityEvent)
        {
            $damager = $ev->getDamager();
            if($damager instanceof Player)
            {
                if($entity instanceof Skeleton)
                {
                    if(!$entity->hasTarget()){
                       $entity->setTarget($damager);
                    }
                }
            }
        }
    }
}
