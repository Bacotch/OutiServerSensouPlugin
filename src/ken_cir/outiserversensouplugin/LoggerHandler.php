<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin;

use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use DateTime;
use InvalidArgumentException;
use ken_cir\pmmpoutiserverbot\PMMPOutiServerBot;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;

class LoggerHandler implements Listener
{
    private Main $plugin;

    private int $playerCount;

    private int $blockCount;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->playerCount = 0;
        $this->blockCount = 0;
    }

    private function sendPlayerLog(Player $player, string $content): void
    {
        if ($content === "") throw new InvalidArgumentException("\$content a cannot be an empty string");

        if (count((array)$this->plugin->getPluginConfig()->get("Discord_Player_Webhook", [])) < 10) return;
        $webhookURLs = (array)$this->plugin->getPluginConfig()->get("Discord_Player_Webhook", []);
        $webhook = new Webhook($webhookURLs[$this->playerCount]);
        $time = new DateTime('NOW');
        $msg = new Message();
        $msg->setContent("```プレイヤー: {$player->getName()}(XUID: {$player->getXuid()}\n時間{$time->format('Y-m-d H:i:sP')}\n$content```");
        $webhook->send($msg);
        $this->playerCount++;
        if ($this->playerCount > 10) {
            $this->playerCount = 0;
        }
    }

    private function sendBlockLog(Block $block, Position $pos, string $content): void
    {
        if ($content === "") throw new InvalidArgumentException("\$content a cannot be an empty string");

        if (count((array)$this->plugin->getPluginConfig()->get("Discord_Block_Webhook", [])) < 10) return;
        $webhookURLs = (array)$this->plugin->getPluginConfig()->get("Discord_Block_Webhook", []);
        $webhook = new Webhook($webhookURLs[$this->blockCount]);
        $time = new DateTime('NOW');
        $msg = new Message();
        $msg->setContent("```ブロック: {$block->getName()}({$block->getId()}:{$block->getMeta()})\n座標: {$pos->getWorld()->getFolderName()}:{$pos->getX()}:{$pos->getY()}:{$pos->getZ()}\n時間{$time->format('Y-m-d H:i:sP')}\n$content```");
        $webhook->send($msg);
        $this->blockCount++;
        if ($this->blockCount > 10) {
            $this->blockCount = 0;
        }
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerLoginEvent $event
     * @return void
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $this->sendPlayerLog($player, "{$player->getName()}が{$player->getWorld()->getFolderName()}:{$player->getPosition()->getX()}:{$player->getPosition()->getY()}:{$player->getPosition()->getZ()}ログインしました");
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $this->sendPlayerLog($player, "{$player->getName()}が参加しました");
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerQuitEvent $event
     * @return void
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $this->sendPlayerLog($player, "{$player->getName()}が退出しました");
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerDeathEvent $event
     * @return void
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $killer = $player->getLastDamageCause();
        if (!$killer instanceof EntityDamageByEntityEvent) return;
        $killer = $killer->getDamager();
        if (!$killer instanceof Player) {
            $this->sendPlayerLog($player, "{$player->getName()}が{$player->getWorld()->getFolderName()}:{$player->getPosition()->getX()}:{$player->getPosition()->getY()}:{$player->getPosition()->getZ()}で死亡しました");
        }
        else {
            $this->sendPlayerLog($player, "{$player->getName()}が{$player->getWorld()->getFolderName()}:{$player->getPosition()->getX()}:{$player->getPosition()->getY()}:{$player->getPosition()->getZ()}で{$killer->getName()}に殺されました");
        }
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerRespawnEvent $event
     * @return void
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();
        $this->sendPlayerLog($player, "{$player->getName()}が{$player->getWorld()->getFolderName()}:{$player->getPosition()->getX()}:{$player->getPosition()->getY()}:{$player->getPosition()->getZ()}にリスポーンしました");
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $pos = $block->getPosition();
        $this->sendBlockLog($block, $pos, "{$player->getName()}が{$pos->getWorld()->getFolderName()}:{$pos->getX()}:{$pos->getY()}:{$pos->getZ()}の{$block->getName()}ブロックをタップしました");
    }

    /**
     * @priority MONITOR
     *
     * @param BlockBreakEvent $event
     * @return void
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $pos = $block->getPosition();
        $this->sendBlockLog($block, $pos, "{$player->getName()}が{$pos->getWorld()->getFolderName()}:{$pos->getX()}:{$pos->getY()}:{$pos->getZ()}の{$block->getName()}ブロックを破壊しました");
    }

    /**
     * @priority MONITOR
     *
     * @param BlockPlaceEvent $event
     * @return void
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $pos = $block->getPosition();
        $this->sendBlockLog($block, $pos, "{$player->getName()}が{$pos->getWorld()->getFolderName()}:{$pos->getX()}:{$pos->getY()}:{$pos->getZ()}に{$block->getName()}ブロックを設置しました");
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerChatEvent $event
     * @return void
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $this->sendPlayerLog($player, "{$player->getName()}のチャット: {$event->getFormat()}");
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerKickEvent $event
     * @return void
     */
    public function onPlayerKick(PlayerKickEvent $event): void
    {
        $player = $event->getPlayer();
        PMMPOutiServerBot::getInstance()->getDiscordBotThread()->sendChatMessage("{$player->getName()}が{$event->getReason()}でゲームを追放されました");
        $this->sendPlayerLog($player, "{$player->getName()}が{$event->getReason()}でゲームを追放されました");
    }
}