<?php

declare(strict_types=1);

namespace OutiServerPlugin\Commands;

use Error;
use Exception;
use OutiServerPlugin\Main;
use OutiServerPlugin\Tasks\LogDiscordSend;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SetChatModeCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "setchatmode", "チャットモードを設定する", "/setchatmode [モード]");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            if (!isset($args[0])) {
                $sender->sendMessage($this->usageMessage);
                return;
            }

            $player = $sender->getPlayer();

            if ($args[0] === "全体") {
                $this->plugin->database->set_Player_ChatMode($player->getName(), -1);
                $sender->sendMessage("チャットモードを 全体 に切り替えました");
                $this->plugin->getServer()->getAsyncPool()->submitTask(
                    new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()} がチャットモードを 全体 に切り替えました", LogDiscordSend::PLUGIN)
                );
            } else {
                $faction = $this->plugin->database->get_faction_byName($args[0]);
                if (!$faction) {
                    $sender->sendMessage("派閥 $args[0] は存在しません");
                } elseif ($this->plugin->database->get_Player($player->getName())["faction"] !== $faction["id"]) {
                    $sender->sendMessage("あなたは派閥 $args[0] に所属していません");
                } else {
                    $this->plugin->database->set_Player_faction($player->getName(), $faction["id"]);
                    $sender->sendMessage("チャットモードを派閥 $args[0] のチャットに切り替えました");
                    $this->plugin->getServer()->getAsyncPool()->submitTask(
                        new LogDiscordSend($this->plugin->config, "\nPlayer {$player->getName()} がチャットモードを 派閥 $args[0] に切り替えました", LogDiscordSend::PLUGIN)
                    );
                }
            }
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error, $sender);
        }
    }
}