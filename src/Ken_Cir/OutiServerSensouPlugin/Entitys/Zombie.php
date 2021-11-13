<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Entitys;

/**
 * ゾンビ
 */
class Zombie extends EntityBase
{
    public const NETWORK_ID = self::ZOMBIE;

    /**
     * @return string
     * エンティティ名を取得する
     */
    public function getName() : string{
        return "ゾンビ";
    }
}