<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopDataManager;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\entitys\Skeleton;
use ken_cir\outiserversensouplugin\forms\admin\database\ChestShopDatabaseForm;
use ken_cir\outiserversensouplugin\forms\chestshop\BuyChestShopForm;
use ken_cir\outiserversensouplugin\forms\chestshop\CreateChestShopForm;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\tasks\AutoUpdateWait;
use ken_cir\outiserversensouplugin\utilitys\OutiServerUtilitys;
use pocketmine\block\Chest;
use pocketmine\block\WallSign;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
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
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function count;
use function extension_loaded;
use function file_put_contents;
use function pcntl_exec;
use function register_shutdown_function;
use function rename;
use function strtolower;
use function unlink;
use const DIRECTORY_SEPARATOR;

/**
 * PMMPイベント処理クラス
 */
class EventListener implements Listener
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
            if (!extension_loaded('pcntl') or DIRECTORY_SEPARATOR !== '/') return;

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
            } else {
                Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！アップデートを待機しています...");
                Server::getInstance()->broadcastMessage("§a[システム] §e[警告] §fサーバーアップデートの準備が整いました！あと10分でサーバーは再起動されます");
                Main::getInstance()->getScheduler()->scheduleRepeatingTask(new AutoUpdateWait(), 20);
            }
        } catch (Error|Exception $exception) {
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

            // プレイヤーデータがあるか確認して、なければ作成
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            if (!$playerData) {
                $playerData = PlayerDataManager::getInstance()->create($player);
            }

            // もしプレイヤー名が変わっていたら新しい名前に更新
            if ($playerData->getName() !== strtolower($player->getName())) {
                $playerData->setName($playerData->getName());
            }

            // もしIPが追加されてないなら追加
            if (!$playerData->hasIp($player->getNetworkSession()->getIp())) {
                $playerData->addIp($player->getNetworkSession()->getIp());
            }

            // もしキャッシュデータがなければ作成する
            if (!PlayerCacheManager::getInstance()->getXuid($player->getXuid())) {
                PlayerCacheManager::getInstance()->create($player->getXuid(), $player->getName());
            }
        } catch (Error|Exception $error) {
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

            // 未読メール数を取得して1件以上あれば通知
            if (($mail_count = MailDataManager::getInstance()->unReadCount($player->getXuid())) > 0) {
                $player->sendMessage("§a[システム] 未読メールが{$mail_count}件あります");
            }
        } catch (Error|Exception $error) {
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

            // おうちウォッチのロック解除
            PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setLockOutiWatch(false);
        } catch (Error|Exception $error) {
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
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $playerCacheData = PlayerCacheManager::getInstance()->getXuid($player->getXuid());

            // 無所属
            if ($playerData->getFaction() === -1) {
                $event->setFormat("§f<無所属><{$player->getName()}>" . $playerCacheData->getDiscordUserTag() ? "<{$playerCacheData->getDiscordUserTag()}>" : " $message");
            } // どこかに所属してる
            else {
                $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());
                $color = OutiServerUtilitys::getChatColor($factionData->getColor());
                $event->setFormat("$color<{$factionData->getName()}>§f<{$player->getName()}>" . ($playerCacheData->getDiscordUserTag() ? "<{$playerCacheData->getDiscordUserTag()}>" : "") . " $message");
            }

            // 派閥専用チャットの場合は
            if ($playerData->getChatmode() !== -1) {
                $factionPlayers = PlayerDataManager::getInstance()->getFactionPlayers($playerData->getFaction());

                // 同じ派閥にメッセージを送る
                if ($factionPlayers) {
                    foreach ($factionPlayers as $factionPlayer) {
                        $factionPlayer = Server::getInstance()->getPlayerExact($factionPlayer->getName());
                        $factionPlayer->sendMessage($event->getFormat());
                    }
                }

                // OP持ちにもメッセージを送る
                foreach (Server::getInstance() as $onlinePlayer) {
                    if (!Server::getInstance()->isOp($onlinePlayer->getName())) continue;
                    // メッセージ送信済みの場合は
                    elseif (PlayerDataManager::getInstance()->getXuid($onlinePlayer->getXuid())->getFaction() === $playerData->getFaction()) continue;
                    $onlinePlayer->sendMessage($event->getFormat());
                }

                // ログに記録
                Main::getInstance()->getLogger()->info($event->getFormat());
                $event->cancel();
            }
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        try {
            $player = $event->getPlayer();
            $item = $event->getItem();
            $position = $event->getBlock()->getPosition();
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());

            if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                if (!PlayerCacheManager::getInstance()->getXuid($player->getXuid())->isLockOutiWatch() and $item->getName() === "OutiWatch") {
                    PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setLockOutiWatch(true);
                    $form = new OutiWatchForm();
                    $form->execute($player);
                }

                // ここから 土地保護処理
                $landConfigData = LandConfigDataManager::getInstance()->getPos($position->getFloorX(), $position->getFloorZ(), $player->getWorld()->getFolderName());
                // 土地保護データがあるなら
                if ($landConfigData !== null) {
                    $landFactionData = LandDataManager::getInstance()->get($landConfigData->getLandid());
                    // その土地の派閥のオーナーじゃない 派閥のオーナーは全権限持ちということで突破可能
                    if (FactionDataManager::getInstance()->get($landFactionData->getFactionId())->getOwnerXuid() !== $playerData->getXuid()) {
                        $permsManager = $landConfigData->getLandPermsManager();
                        $memberPerms = $permsManager->getMemberLandPerms($player->getName());
                        if ($memberPerms !== null and !$memberPerms->isBlockTap_Place()) {
                            $event->cancel();
                        } elseif ($memberPerms === null) {
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
                            } // ないならデフォルト
                            elseif ($rolePerms === null and !$permsManager->getDefalutLandPerms()->isBlockTap_Place()) {
                                $event->cancel();
                            }
                        }
                    }
                }
                // ここまで 土地保護処理

                // ここから チェストショップ処理
                $chestShopData = ChestShopDataManager::getInstance()->getPosition($player->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
                if (!$event->isCancelled() and $chestShopData) {
                    if ($playerData->getFaction() === -1) {
                        $player->sendMessage("§a[システム] チェストショップはどこかの派閥に所属していないと使えません");
                        $event->cancel();
                    } // チェストをタップしてOPでもなく貿易元の派閥所属でもないなら
                    elseif ($chestShopData->getChestX() === $position->getFloorX() and $chestShopData->getChestY() === $position->getFloorY() and $chestShopData->getChestZ() === $position->getFloorZ() and !Server::getInstance()->isOp($player->getName()) and $chestShopData->getFactionId() !== $playerData->getFaction()) {
                        $event->cancel();
                        $form = new BuyChestShopForm();
                        $form->execute($player, $chestShopData);
                    } // 看板をタップして
                    elseif ($chestShopData->getSignboardX() === $position->getFloorX() and $chestShopData->getSignboardY() === $position->getFloorY() and $chestShopData->getSignboardZ() === $position->getFloorZ()) {
                        // OP餅なら
                        if (Server::getInstance()->isOp($player->getName())) {
                            (new ChestShopDatabaseForm())->execute($player, $chestShopData);
                        }
                        // OP餅ではなくて貿易元と所属派閥が違うなら
                        elseif ($chestShopData->getFactionId() !== $playerData->getFaction()) {
                            $form = new BuyChestShopForm();
                            $form->execute($player, $chestShopData);
                        }
                    }
                }
                // ここまで チェストショップ処理
            }
        } catch (Error|Exception $exception) {
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
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $landConfigData = LandConfigDataManager::getInstance()->getPos($position->getFloorX(), $position->getFloorZ(), $position->getWorld()->getFolderName());
            // 土地保護データがあってその敷地内に移動前にいないなら
            if ($landConfigData !== null and !LandConfigDataManager::getInstance()->getPos($oldPostion->getFloorX(), $oldPostion->getFloorZ(), $oldPostion->getWorld()->getFolderName())) {
                $landFactionData = LandDataManager::getInstance()->get($landConfigData->getLandid());
                // その土地の派閥のオーナーじゃない 派閥のオーナーは全権限持ちということで突破可能
                if (FactionDataManager::getInstance()->get($landFactionData->getFactionId())->getOwnerXuid() !== $playerData->getXuid()) {
                    $permsManager = $landConfigData->getLandPermsManager();
                    $memberPerms = $permsManager->getMemberLandPerms($player->getName());
                    if ($memberPerms !== null and !$memberPerms->isEntry()) {
                        $event->cancel();
                    } elseif ($memberPerms === null) {
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
                        } // ないならデフォルト
                        elseif ($rolePerms === null and !$permsManager->getDefalutLandPerms()->isEntry()) {
                            $event->cancel();
                        }
                    }
                }
            }
        } catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }

    private array $searched = [];

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
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $position = $event->getBlock()->getPosition();

            $landConfigData = LandConfigDataManager::getInstance()->getPos($position->getFloorX(), $position->getFloorZ(), $position->getWorld()->getFolderName());
            // 土地保護データがあるなら
            if ($landConfigData !== null) {
                $landFactionData = LandDataManager::getInstance()->get($landConfigData->getLandid());
                // その土地の派閥のオーナーじゃない 派閥のオーナーは全権限持ちということで突破可能
                if (FactionDataManager::getInstance()->get($landFactionData->getFactionId())->getOwnerXuid() !== $playerData->getXuid()) {
                    $permsManager = $landConfigData->getLandPermsManager();
                    $memberPerms = $permsManager->getMemberLandPerms($player->getName());
                    if ($memberPerms !== null and !$memberPerms->isBlockBreak()) {
                        $event->cancel();
                    } elseif ($memberPerms === null) {
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
                        } // ないならデフォルト
                        elseif ($rolePerms === null and !$permsManager->getDefalutLandPerms()->isBlockBreak()) {
                            $event->cancel();
                        }
                    }
                }
            }

            $chestShopData = ChestShopDataManager::getInstance()->getPosition($player->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
            if ($chestShopData) {
                if (($chestShopData->getFactionId() === $playerData->getFaction() and !$event->isCancelled()) or Server::getInstance()->isOp($player->getName())) {
                    ChestShopDataManager::getInstance()->delete($chestShopData->getId());
                    $player->sendMessage("§a[システム] このチェストショップを閉店しました");
                }
                else {
                    $event->cancel();
                }
            }
        } catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }

    public function onDamage(EntityDamageEvent $ev)
    {
        $entity = $ev->getEntity();
        if ($ev instanceof EntityDamageByEntityEvent) {
            $damager = $ev->getDamager();
            if ($damager instanceof Player) {
                if ($entity instanceof Skeleton) {
                    if (!$entity->hasTarget()) {
                        $entity->setTarget($damager);
                    }
                }
            }
        }
    }

    /**
     * 多分看板の文字列が変更された時に呼び出されるイベント
     *
     * @param SignChangeEvent $event
     * @return void
     */
    public function SignChange(SignChangeEvent $event)
    {
        try {
            $player = $event->getPlayer();
            $sign = $event->getSign();
            $block = $event->getBlock();
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());

            if ($sign instanceof WallSign) {
                $signText = $event->getNewText();
                // もし1行目がshopだった場合は
                if ($signText->getLine(0) === "shop") {
                    $mainchest = $block->getSide(Facing::opposite($block->getFacing()));
                    if ($mainchest instanceof Chest) {
                        if ($playerData->getFaction() === -1) {
                            $player->sendMessage("§a[システム] チェストショップ(貿易所)は派閥に所属していないと使用できません");
                        } else {
                            $form = new CreateChestShopForm();
                            $form->execute($player, $sign, $sign->getPosition(), $mainchest->getPosition());
                        }
                    }

                }
            }
        } catch (Error|Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true);
        }
    }
}
