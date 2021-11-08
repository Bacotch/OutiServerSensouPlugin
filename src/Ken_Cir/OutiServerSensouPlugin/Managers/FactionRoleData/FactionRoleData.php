<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\FactionRoleData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

/**
 * 派閥のロールデータ
 */
class FactionRoleData
{
    /**
     * @var int
     * ロールID
     */
    private int $id;

    /**
     * @var int
     * 派閥ID
     */
    private int $faction_id;

    /**
     * @var string
     * ロール名
     */
    private string $name;

    /**
     * @var int
     * 宣戦布告権限
     */
    private int $sensen_hukoku;

    /**
     * @var int
     * 派閥にプレイヤー招待権限
     */
    private int $invite_player;

    /**
     * @var int
     * 派閥プレイヤー全員に一括でメール送信権限
     */
    private int $sendmail_all_faction_player;

    /**
     * @var int
     * 敵対派閥と友好派閥（制限あり）の設定権限
     */
    private int $freand_faction_manager;

    /**
     * @var int
     * 派閥からプレイヤーを追放権限
     */
    private int $kick_faction_player;

    /**
     * @var int
     * 派閥の土地管理権限
     */
    private int $land_manager;

    /**
     * @var int
     * 派閥銀行管理権限
     */
    private int $bank_manager;

    /**
     * @var int
     * 派閥ロール管理権限
     */
    private int $role_manager;

    /**
     * @param int $id
     * @param int $faction_id
     * @param string $name
     * @param int $sensen_hukoku
     * @param int $invite_player
     * @param int $sendmail_all_faction_player
     * @param int $freand_faction_manager
     * @param int $kick_faction_player
     * @param int $land_manager
     * @param int $bank_manager
     * @param int $role_manager
     */
    public function __construct(int $id, int $faction_id, string $name, int $sensen_hukoku, int $invite_player, int $sendmail_all_faction_player, int $freand_faction_manager, int $kick_faction_player, int $land_manager, int $bank_manager, int $role_manager)
    {
        $this->id = $id;
        $this->faction_id = $faction_id;
        $this->name = $name;
        $this->sensen_hukoku = $sensen_hukoku;
        $this->invite_player = $invite_player;
        $this->sendmail_all_faction_player = $sendmail_all_faction_player;
        $this->freand_faction_manager = $freand_faction_manager;
        $this->kick_faction_player = $kick_faction_player;
        $this->land_manager = $land_manager;
        $this->bank_manager = $bank_manager;
        $this->role_manager = $role_manager;
    }

    /**
     * データ保存
     */
    public function save()
    {
        try {
            Main::getInstance()->getDatabase()->executeChange("faction_roles.update",
                [
                    "faction_id" => $this->faction_id,
                    "name" => $this->name,
                    "sensen_hukoku" => $this->sensen_hukoku,
                    "invite_player" => $this->invite_player,
                    "sendmail_all_faction_player" => $this->sendmail_all_faction_player,
                    "freand_faction_manager" => $this->freand_faction_manager,
                    "kick_faction_player" => $this->kick_faction_player,
                    "land_manager" => $this->land_manager,
                    "bank_manager" => $this->bank_manager,
                    "role_manager" => $this->role_manager,
                    "id" => $this->id
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
     * @return int
     * ロールIDを返す
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     * 派閥IDを返す
     */
    public function getFactionId(): int
    {
        return $this->faction_id;
    }

    /**
     * @return string
     * ロール名取得
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * ロール名設定
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     * 宣戦布告権限があるかどうか
     */
    public function isSensenHukoku(): bool
    {
        return (bool)$this->sensen_hukoku;
    }

    /**
     * @param bool $sensen_hukoku
     * 宣戦布告権限設定
     */
    public function setSensenHukoku(bool $sensen_hukoku): void
    {
        $this->sensen_hukoku = (int)$sensen_hukoku;
    }

    /**
     * @return bool
     * 派閥にプレイヤー招待権限があるかどうか
     */
    public function isInvitePlayer(): bool
    {
        return (bool)$this->invite_player;
    }

    /**
     * @param bool $invite_player
     * 派閥にプレイヤー招待権限を設定
     */
    public function setInvitePlayer(bool $invite_player): void
    {
        $this->invite_player = (int)$invite_player;
    }

    /**
     * @return bool
     * 派閥プレイヤー全員に一括でメール送信権限があるかどうか
     */
    public function isSendmailAllFactionPlayer(): bool
    {
        return (bool)$this->sendmail_all_faction_player;
    }

    /**
     * @param bool $sendmail_all_faction_player
     * 敵対派閥と友好派閥（制限あり）の設定権限の設定
     */
    public function setSendmailAllFactionPlayer(bool $sendmail_all_faction_player): void
    {
        $this->sendmail_all_faction_player = (int)$sendmail_all_faction_player;
    }

    /**
     * @return bool
     * 敵対派閥と友好派閥（制限あり）の設定権限
     */
    public function isFreandFactionManager(): bool
    {
        return (bool)$this->freand_faction_manager;
    }

    /**
     * @param bool $freand_faction_manager
     * 敵対派閥と友好派閥（制限あり）の設定権限の設定
     */
    public function setFreandFactionManager(bool $freand_faction_manager): void
    {
        $this->freand_faction_manager = (int)$freand_faction_manager;
    }

    /**
     * @return bool
     * 派閥からプレイヤーを追放権限
     */
    public function isKickFactionPlayer(): bool
    {
        return (bool)$this->kick_faction_player;
    }

    /**
     * @param bool $kick_faction_player
     * 派閥からプレイヤーを追放権限
     */
    public function setKickFactionPlayer(bool $kick_faction_player): void
    {
        $this->kick_faction_player = (int)$kick_faction_player;
    }

    /**
     * @return bool
     * 派閥の土地管理権限
     */
    public function getLandManager(): bool
    {
        return (bool)$this->land_manager;
    }

    /**
     * @param bool $land_manager
     * 派閥の土地管理権限の設定
     */
    public function setLandManager(bool $land_manager): void
    {
        $this->land_manager = (int)$land_manager;
    }

    /**
     * @return bool
     * 派閥銀行管理権限があるかどうか
     */
    public function isBankManager(): bool
    {
        return (bool)$this->bank_manager;
    }

    /**
     * @param bool $bank_manager
     * 派閥銀行管理権限の設定
     */
    public function setBankManager(bool $bank_manager): void
    {
        $this->bank_manager = (int)$bank_manager;
    }

    /**
     * @return bool
     * 派閥ロール管理権限があるかどうか
     */
    public function getRoleManager(): bool
    {
        return (bool)$this->role_manager;
    }

    /**
     * @param bool $role_manager
     * 派閥ロール管理権限の設定
     */
    public function setRoleManager(bool $role_manager): void
    {
        $this->role_manager = (int)$role_manager;
    }
}