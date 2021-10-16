<?php

declare(strict_types=1);

namespace OutiServerPlugin;

use Error;
use Exception;
use OutiServerPlugin\Database\PlayerDatabase;
use OutiServerPlugin\Tasks\LogDiscordSend;
use OutiServerPlugin\Utils\PluginUtils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

/**
 * PMMPイベント処理クラス
 */
class EventListener implements Listener
{
    /**
     * @var Main
     */
    private Main $plugin;

    /**
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerLoginEvent $event
     * プレイヤーログインイベント
     */
    public function onPlayerLogin(PlayerLoginEvent $event)
    {
        try {
            $player = $event->getPlayer();
            $this->plugin->getServer()->getAsyncPool()->submitTask(
                new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()} が\nワールド: {$player->getLevel()->getName()}\nX座標: {$player->getX()}\nY座標: {$player->getY()}\nZ座標: {$player->getZ()}\nにログインしました", LogDiscordSend::PLAYER)
            );
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
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
            $this->plugin->database->addPlayer($player->getName(), $player->getAddress());
            if (($mail_count = $this->plugin->database->getPlayerNewMail($player->getName())) > 0) {
                $player->sendMessage("§a未読メールが{$mail_count}件あります");
            }
            $this->plugin->discord_client->sendChatMessage("{$player->getName()}がサーバーに参加しました");
            $this->plugin->getServer()->getAsyncPool()->submitTask(
                new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()}\nIP {$player->getAddress()} がサーバーに参加しました", LogDiscordSend::PLAYER)
            );
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
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
            $this->plugin->discord_client->sendChatMessage("{$player->getName()}がサーバーから退出しました");
            $this->plugin->getServer()->getAsyncPool()->submitTask(
                new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()}\nIP {$player->getAddress()} がサーバーから退出しました", LogDiscordSend::PLAYER)
            );
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error);
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
            $player_data = $this->plugin->database->getPlayer($player->getName());
            if ($player_data["faction"]) {
                $faction = $this->plugin->database->getFactionById($player_data["faction"]);
                $color = PluginUtils::getChatColor((int)$faction["color"]);
                $event->setFormat("{$color}[{$faction["name"]}]§f[{$player->getName()}] $message");
            } else {
                $event->setFormat("§f[無所属]§f[{$player->getName()}] $message");
            }

            // 派閥専用チャットの場合は
            if ($player_data["chatmode"] !== -1) {
                $faction = $this->plugin->database->getFactionById($player_data["faction"]);
                $faction_players = $this->plugin->database->getPlayerfaction($faction["id"]);
                $server = Server::getInstance();

                // 同じ派閥にメッセージを送る
                if ($faction_players) {
                    foreach ($faction_players as $f_p) {
                        $faction_player = $server->getPlayer($f_p["name"]);
                        $faction_player->sendMessage($event->getMessage());
                    }
                }

                // OP持ちにもメッセージを送る
                foreach ($server->getOnlinePlayers() as $onlinePlayer) {
                    if (!$onlinePlayer->isOp()) continue;
                    // メッセージ送信済みの場合は
                    elseif ($this->plugin->database->getPlayer($onlinePlayer->getName())["faction"] === $faction["id"]) continue;
                    $onlinePlayer->sendMessage($event->getMessage());
                }

                // ログに記録
                $this->plugin->getServer()->getAsyncPool()->submitTask(
                    new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()}のチャットメッセージ\n範囲: {$faction["name"]} 派閥\nメッセージ: $message", LogDiscordSend::SERVER)
                );

                $event->setCancelled();
                return;
            }

            $this->plugin->discord_client->sendChatMessage("{$event->getFormat()} >> {$event->getMessage()}");
            $this->plugin->getServer()->getAsyncPool()->submitTask(
                new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()}のチャットメッセージ\n範囲: 全体\nメッセージ: $message", LogDiscordSend::SERVER)
            );
        } catch (Error | Exception $e) {
            $this->plugin->logger->error($e);
        }
    }
}