<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\FactionData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;
use function strtolower;

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
        $this->faction_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("outiserver.factions.seq",
            [],
            function (array $row) {
                try {
                    if (count($row) < 1) {
                        $this->seq = 0;
                        return;
                    }
                    foreach ($row as $data) {
                        $this->seq = $data["seq"];
                    }
                } catch (Error|Exception $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->waitAll();
        Main::getInstance()->getDatabase()->executeSelect("outiserver.factions.load",
            [],
            function (array $row) {
                try {
                    foreach ($row as $data) {
                        $this->faction_datas[$data["id"]] = new FactionData($data["id"], $data["name"], $data["owner"], $data["color"]);
                    }
                } catch (Error|Exception $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
    }

    /**
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) return;
        self::$instance = new FactionDataManager();
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
        try {
            Main::getInstance()->getDatabase()->executeInsert("outiserver.factions.create",
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
            $this->faction_datas[$this->seq] = new FactionData($this->seq, $name, $owner, $color);
        } catch (Error|Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }

        return $this->seq;
    }

    /**
     * @param int $id
     */
    public function delete(int $id): void
    {
        try {
            if (!$this->get($id)) return;
            Main::getInstance()->getDatabase()->executeGeneric("outiserver.factions.delete",
                [
                    "id" => $id
                ],
                null,
                function (SqlError $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            );
            unset($this->faction_datas[$id]);
        } catch (Error|Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}