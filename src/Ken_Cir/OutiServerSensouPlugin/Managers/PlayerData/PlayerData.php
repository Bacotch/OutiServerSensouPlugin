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
     * @var string
     * 所属派閥名
     */
    private string $faction;

    /**
     * @var string
     * チャットモード
     */
    private string $chatmode;

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
    public function __construct(string $name, string $ip, string $faction, string $chatmode, int $drawscoreboard)
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
     * @param string[]
     */
    public function setIp(array $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getFaction(): string
    {
        return $this->faction;
    }

    /**
     * @param string $faction
     */
    public function setFaction(string $faction): void
    {
        $this->faction = $faction;
    }

    /**
     * @return string
     */
    public function getChatmode(): string
    {
        return $this->chatmode;
    }

    /**
     * @param string $chatmode
     */
    public function setChatmode(string $chatmode): void
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