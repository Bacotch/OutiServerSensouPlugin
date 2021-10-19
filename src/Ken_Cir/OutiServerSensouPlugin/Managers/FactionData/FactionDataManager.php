<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\FactionData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

/**
 * 派閥データマネージャー
 */
final class FactionDataManager
{
    /**
     * @var FactionDataManager $this
     */
    private static self $instance;

    /**
     * @var FactionData[]
     */
    private array $faction_datas;

    /**
     * @var int
     * ID
     */
    private int $seq;

    public function __construct()
    {
        self::$instance = $this;
        Main::getInstance()->getDatabase()->executeSelect("factions.load",
            [],
            function (array $row) {
                try {
                    foreach ($row as $data) {
                        $this->faction_datas[$data["name"]] = new FactionData($data["name"], $data["owner"], $data["color"], $data["roles"]);
                    }
                }
                catch (Error | Exception $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
    }

    /**
     * @return FactionDataManager
     */
    public static function getInstance(): FactionDataManager
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @return bool|FactionData
     * 派閥データの取得
     */
    public function get(string $name): bool|FactionData
    {
        if (!isset($this->faction_datas[$name])) return false;
        return $this->faction_datas[$name];
    }

    /**
     * @param string $name
     * @param string $owner
     * @param int $color
     * 派閥データを作成する
     */
    public function create(string $name, string $owner, int $color)
    {
        Main::getInstance()->getDatabase()->executeInsert("factions.create",
            [
                "name" => $name,
                "owner" => strtolower($owner),
                "color" => $color,
                "roles" => serialize([])
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        $this->faction_datas[$name] = new FactionData($name, strtolower($owner), $color, serialize([]));
    }

    /**
     * @param string $name
     * 派閥データを削除する
     */
    public function delete(string $name)
    {
        if (!$this->get($name)) return;
        Main::getInstance()->getDatabase()->executeGeneric("factions.delete",
            [
                "name" => $name
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->faction_datas[$name]);
    }
}