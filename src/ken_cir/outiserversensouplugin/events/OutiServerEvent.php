<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;

abstract class OutiServerEvent extends Event implements Cancellable
{

}