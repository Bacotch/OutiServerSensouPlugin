<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Ken_Cir\OutiServerSensouPlugin\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

abstract class CommandBase extends Command
{
    /**
     * @var Main
     * Pluginオブジェクト
     */
    protected Main $plugin;

    /**
     * コマンドを作成する
     * @param Main $plugin
     * @param string $name
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(Main $plugin, string $name, string $description = "", ?string $usageMessage = null, array $aliases = [])
    {
        $this->plugin = $plugin;
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    /**
     * @param CommandSender $sender
     * コマンド実行者がPlayerでないといけない場合
     */
    protected function CommandNotPlayer(CommandSender $sender)
    {
        $sender->sendMessage("§cこのコマンドはサーバー内で実行してください");
    }
}