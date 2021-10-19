<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin;

use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Utils\PluginUtils;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

/**
 * PMMPイベント処理クラス
 */
final class EventListener implements Listener
{
    public function __construct()
    {
    }

    /**
     * @param PlayerLoginEvent $event
     * プレイヤーログインイベント
     */
    public function onPlayerLogin(PlayerLoginEvent $event)
    {
        try {
            $player = $event->getPlayer();
            PlayerDataManager::getInstance()->create($player);
            PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Player_Webhook", ""), "Player {$player->getName()} が\nワールド: {$player->getLevel()->getName()}\nX座標: {$player->getX()}\nY座標: {$player->getY()}\nZ座標: {$player->getZ()}\nにログインしました");
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
            if (($mail_count = PlayerDataManager::getInstance()->get($player->getName())->getMailManager()->unReadCount()) > 0) {
                $player->sendMessage("§a未読メールが{$mail_count}件あります");
            }

            Main::getInstance()->getDiscordClient()->sendChatMessage("{$player->getName()}がサーバーに参加しました");
            PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Player_Webhook", ""), "Player {$player->getName()}\nIP {$player->getAddress()} がサーバーに参加しました");
        } catch (Error | Exception $error) {
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
            Main::getInstance()->getDiscordClient()->sendChatMessage("{$player->getName()}がサーバーから退出しました");
            PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Player_Webhook", ""), "Player {$player->getName()}\nIP {$player->getAddress()} がサーバーから退出しました");
        }
        catch (Error | Exception $error) {
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
            if ($player_data->getFaction() === "") {
                $event->setFormat("§f[無所属][{$player->getName()}] $message");
            }
            else {
                $faction = FactionDataManager::getInstance()->get($player_data->getFaction());
                $color = PluginUtils::getChatColor($faction->getColor());
                $event->setFormat("{$color}[{$player_data->getFaction()}]§f[{$player->getName()}] $message");
            }

            // 派閥専用チャットの場合は
            if ($player_data->getChatmode() !== "全体") {
                $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($player_data->getFaction());
                $server = Server::getInstance();

                // 同じ派閥にメッセージを送る
                if ($faction_players) {
                    foreach ($faction_players as $f_p) {
                        $faction_player = $server->getPlayer($f_p->getName());
                        $faction_player->sendMessage($event->getFormat());
                    }
                }

                // OP持ちにもメッセージを送る
                foreach ($server->getOnlinePlayers() as $onlinePlayer) {
                    if (!$onlinePlayer->isOp()) continue;
                    // メッセージ送信済みの場合は
                    elseif (PlayerDataManager::getInstance()->get($onlinePlayer->getName())->getFaction() === $player_data->getFaction()) continue;
                    $onlinePlayer->sendMessage($event->getFormat());
                }

                // ログに記録
                Main::getInstance()->getLogger()->info($event->getFormat());
                $event->setCancelled();
                return;
            }

            Main::getInstance()->getDiscordClient()->sendChatMessage($event->getFormat());
        } catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e);
        }
    }
}