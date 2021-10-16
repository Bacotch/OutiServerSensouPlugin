<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Form;

use Ken_Cir\OutiServerSensouPlugin\Main;

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