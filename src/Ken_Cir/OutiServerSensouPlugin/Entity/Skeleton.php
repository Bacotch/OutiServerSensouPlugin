<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Entity;

class Skeleton extends EntityBase
{
    public const NETWORK_ID = self::SKELETON;

    public function getName() : string{
        return "スケルトン";
    }
}