<?php

declare(strict_types=1);

namespace OutiServerPlugin\API;

use OutiServerPlugin\Main;
use pocketmine\Player;

/**
 * おうち鯖プラグインAPIのベース部分
 */
abstract class APIBase
{
    /**
     * @var Main
     */
    protected Main $plugin;

    abstract public function __construct(Main $plugin);

    /**
     * @param Player $player
     * API実行
     */
    abstract public function execute(Player $player);
}