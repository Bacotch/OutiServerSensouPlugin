<?php

declare(strict_types=1);

namespace OutiServerPlugin\Form;

use OutiServerPlugin\Main;
use pocketmine\Player;

abstract class FormBase
{
    /**
     * @var Main
     */
    protected Main $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
}