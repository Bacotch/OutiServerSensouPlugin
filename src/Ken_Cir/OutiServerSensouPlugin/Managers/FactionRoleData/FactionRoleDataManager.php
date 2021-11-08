<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\FactionRoleData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

/**
 * 派閥のロール系管理クラス
 */
class FactionRoleDataManager
{
    /**
     * @var FactionRoleDataManager $this
     */
    private static self $instance;

    /**
     * @var FactionRoleData[]
     */
    private array $faction_role_datas;

    /**
     * @var int
     */
    private int $seq;

    /**
     */
    public function __construct()
    {
        self::$instance = $this;
        $this->faction_role_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("faction_roles.seq",
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
        Main::getInstance()->getDatabase()->executeSelect("faction_roles.load",
            [],
            function (array $row) {
                try {
                    foreach ($row as $data) {
                        $this->faction_role_datas[$data["id"]] = new FactionRoleData($data["id"], $data["faction_id"], $data["name"], $data["sensen_hukoku"], $data["invite_player"], $data["sendmail_all_faction_player"], $data["freand_faction_manager"], $data["kick_faction_player"], $data["land_manager"], $data["bank_manager"], $data["role_manager"]);
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
     * @return FactionRoleDataManager
     */
    public static function getInstance(): FactionRoleDataManager
    {
        return self::$instance;
    }

    /**
     * @param int $id
     * @return false|FactionRoleData
     */
    public function get(int $id): bool|FactionRoleData
    {
        if (!isset($this->faction_role_datas[$id])) return false;
        return$this->faction_role_datas[$id];
    }

    /**
     * @param int $faction_id
     * @param string $name
     * @param bool $sensen_hukoku
     * @param bool $invite_player
     * @param bool $sendmail_all_faction_player
     * @param bool $freand_faction_manager
     * @param bool $kick_faction_player
     * @param bool $land_manager
     * @param bool $bank_manager
     * @param bool $role_manager
     * ロール作成
     */
    public function create(int $faction_id, string $name, bool $sensen_hukoku, bool $invite_player, bool $sendmail_all_faction_player, bool $freand_faction_manager, bool $kick_faction_player, bool $land_manager, bool $bank_manager, bool $role_manager)
    {
        Main::getInstance()->getDatabase()->executeInsert("faction_roles.create",
            [
                "faction_id" => $faction_id,
                "name" => $name,
                "sensen_hukoku" => (int)$sensen_hukoku,
                "invite_player" => (int)$invite_player,
                "sendmail_all_faction_player" => (int)$sendmail_all_faction_player,
                "freand_faction_manager" => (int)$freand_faction_manager,
                "kick_faction_player" => (int)$kick_faction_player,
                "land_manager" => (int)$land_manager,
                "bank_manager" => (int)$bank_manager,
                "role_manager" => (int)$role_manager,
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        $this->seq++;
        $this->faction_role_datas[$this->seq] = new FactionRoleData($faction_id, $name, (int)$sensen_hukoku, (int)$invite_player, (int)$invite_player, (int)$sendmail_all_faction_player, (int)$freand_faction_manager, (int)$kick_faction_player, (int)$land_manager, (int)$bank_manager, (int)$role_manager);
    }

    /**
     * @param int $id
     * ロール削除
     */
    public function delete(int $id)
    {
        Main::getInstance()->getDatabase()->executeInsert("faction_roles.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->faction_role_datas[$id]);
    }
}