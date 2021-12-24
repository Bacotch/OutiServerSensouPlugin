<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\LandConfigData;

class LandConfigDataPerms
{
    private array $defaultPerms;

    private array $rolePerms;

    private array $memberPerms;

    public function __construct(array $perms)
    {
        $this->defaultPerms = $perms["default"];
        $this->rolePerms =  $perms["roles"];
        $this->memberPerms =  $perms["members"];
    }


}