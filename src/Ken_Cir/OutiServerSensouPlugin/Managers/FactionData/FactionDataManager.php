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
class FactionDataManager
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
     */
    private int $seq;

    public function __construct()
    {
        self::$instance = $this;
        $this->faction_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("factions.seq",
            [],
            function (array $row) {
            if (count($row) < 1)  {
                $this->seq = 0;
                return;
            }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->waitAll();
        Main::getInstance()->getDatabase()->executeSelect("factions.load",
            [],
            function (array $row) {
                try {
                    foreach ($row as $data) {
                        $this->faction_datas[$data["id"]] = new FactionData($data["id"], $data["name"], $data["owner"], $data["color"]);
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
     * @param int $id
     * @return bool|FactionData
     * 派閥データの取得
     */
    public function get(int $id): bool|FactionData
    {
        if (!isset($this->faction_datas[$id])) return false;
        return $this->faction_datas[$id];
    }

    /**
     * @param string $name
     * @param string $owner
     * @param int $color
     * 派閥データを作成する
     * @return int
     */
    public function create(string $name, string $owner, int $color): int
    {
        Main::getInstance()->getDatabase()->executeInsert("factions.create",
            [
                "name" => $name,
                "owner" => strtolower($owner),
                "color" => $color
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );

        $this->seq++;
        $this->faction_datas[$this->seq] = new FactionData($this->seq, $name, strtolower($owner), $color);
        return $this->seq;
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        if (!$this->get($id)) return;
        Main::getInstance()->getDatabase()->executeGeneric("factions.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->faction_datas[$id]);
    }
}