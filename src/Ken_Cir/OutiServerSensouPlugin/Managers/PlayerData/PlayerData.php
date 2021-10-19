<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData;

use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

final class PlayerData
{
    /**
     * @var string
     * PlayerName
     */
    private string $name;

    /**
     * @var array|mixed
     * PlayerのログインIPアドレス配列
     */
    private array $ip;

    /**
     * @var int
     * 所属派閥名
     */
    private int $faction;

    /**
     * @var int
     * チャットモード
     */
    private int $chatmode;

    /**
     * @var int
     * スコアボードを描写するか
     */
    private int $drawscoreboard;

    /**
     * @param string $name
     * @param string $ip
     * @param string $faction
     * @param string $chatmode
     * @param int $drawscoreboard
     */
    public function __construct(string $name, string $ip, int $faction, int $chatmode, int $drawscoreboard)
    {
        $this->name = $name;
        $this->ip = unserialize($ip);
        $this->faction = $faction;
        $this->chatmode = $chatmode;
        $this->drawscoreboard = $drawscoreboard;
    }

    /**
     * データをdb上にupdateする
     */
    public function save()
    {
        try {
            Main::getInstance()->getDatabase()->executeChange("players.update",
                [
                    "ip" => serialize($this->ip),
                    "faction" => $this->faction,
                    "chatmode" => $this->chatmode,
                    "drawscoreboard" => $this->drawscoreboard,
                    "name" => $this->name
                ],
                null,
                function (SqlError $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            );
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = strtolower($name);
    }

    /**
     * @return string[]
     */
    public function getIp(): array
    {
        return $this->ip;
    }

    /**
     * @param string
     * IPを追加
     */
    public function addIp(string $ip): void
    {
        if (isset($this->getIp()[$ip])) return;
        $this->ip[] = $ip;
    }

    /**
     * @return int
     */
    public function getFaction(): int
    {
        return $this->faction;
    }

    /**
     * @param int $faction
     */
    public function setFaction(int $faction): void
    {
        $this->faction = $faction;
    }

    /**
     * @return int
     */
    public function getChatmode(): int
    {
        return $this->chatmode;
    }

    /**
     * @param int $chatmode
     */
    public function setChatmode(int $chatmode): void
    {
        $this->chatmode = $chatmode;
    }

    /**
     * @return int
     */
    public function getDrawscoreboard(): int
    {
        return $this->drawscoreboard;
    }

    /**
     * @param int $drawscoreboard
     */
    public function setDrawscoreboard(int $drawscoreboard): void
    {
        $this->drawscoreboard = $drawscoreboard;
    }
}