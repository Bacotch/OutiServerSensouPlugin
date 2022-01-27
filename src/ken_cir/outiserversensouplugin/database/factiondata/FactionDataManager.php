<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\factiondata;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\libs\poggit\libasynql\SqlError;
use function count;

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
     */
    private int $seq;

    public function __construct()
    {
        $this->faction_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("outiserver.factions.seq",
            [],
            function (array $row) {
                if (count($row) < 1) {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->executeSelect("outiserver.factions.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->faction_datas[$data["id"]] = new FactionData($data["id"], $data["name"], $data["owner"], $data["color"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
    }

    /**
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(FactionDataManager::class);
        self::$instance = new self();
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
     * @param string $owner_xuid
     * @param int $color
     * 派閥データを作成する
     * @return int
     */
    public function create(string $name, string $owner_xuid, int $color): int
    {
        Main::getInstance()->getDatabase()->executeInsert("outiserver.factions.create",
            [
                "name" => $name,
                "owner_xuid" => $owner_xuid,
                "color" => $color
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        $this->faction_datas[$this->seq] = new FactionData($this->seq, $name, $owner_xuid, $color);

        return $this->seq;
    }

    /**
     * @param int $id
     */
    public function delete(int $id): void
    {
        if (!$this->get($id)) return;
        Main::getInstance()->getDatabase()->executeGeneric("outiserver.factions.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->faction_datas[$id]);
    }
}