<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\RoleData;


final class RolePermsData
{
    /**
     * @var bool
     * 宣戦布告権限
     */
    private bool $sensen_hukoku;

    /**
     * @var bool
     * プレイヤーを派閥に招待権限
     */
    private bool $invite_player;

    /**
     * @var bool
     * 派閥に所属しているプレイヤー全員にメール送信権限
     */
    private bool $sendmail_all_faction_player;

    /**
     * @var bool
     * 敵対派閥と友好派閥（制限あり）の設定権限
     */
    private bool $freand_faction_manager;

    /**
     * @var bool
     * 派閥からプレイヤーを追放権限
     */
    private bool $kick_faction_player;

    /**
     * @var bool
     * 土地管理権限
     */
    private bool $land_manager;

    /**
     * @var bool
     * 銀行管理権限
     */
    private bool $bank_manager;

    /**
     * @var bool
     * ロール管理権限
     */
    private bool $role_manager;

    public function __construct(array $perms)
    {
        $this->sensen_hukoku = $perms["sensen_hukoku"];
        $this->invite_player = $perms["invite_player"];
        $this->sendmail_all_faction_player = $perms["sendmail_all_faction_player"];
        $this->freand_faction_manager = $perms["freand_faction_manager"];
        $this->kick_faction_player = $perms["kick_faction_player"];
        $this->land_manager = $perms["land_manager"];
        $this->bank_manager = $perms["bank_manager"];
        $this->role_manager = $perms["role_manager"];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array(
            "sensen_hukoku" => $this->sensen_hukoku,
            "invite_player" => $this->invite_player,
            "sendmail_all_faction_player" => $this->sendmail_all_faction_player,
            "freand_faction_manager" => $this->freand_faction_manager,
            "kick_faction_player" => $this->kick_faction_player,
            "land_manager" => $this->land_manager,
            "bank_manager" => $this->bank_manager,
            "role_manager" => $this->role_manager
        );
    }

    /**
     * @return bool
     * 宣戦布告権限
     */
    public function isSensenHukoku(): bool
    {
        return $this->sensen_hukoku;
    }

    /**
     * @param bool $sensen_hukoku
     * 宣戦布告権限設定
     */
    public function setSensenHukoku(bool $sensen_hukoku): void
    {
        $this->sensen_hukoku = $sensen_hukoku;
    }

    /**
     * @return bool
     * プレイヤー招待権限
     */
    public function isInvitePlayer(): bool
    {
        return $this->invite_player;
    }

    /**
     * @param bool $invite_player
     * プレイヤー招待権限設定
     */
    public function setInvitePlayer(bool $invite_player): void
    {
        $this->invite_player = $invite_player;
    }

    /**
     * @return bool
     * 派閥に所属しているプレイヤー全員にメール送信権限
     */
    public function isSendmailAllFactionPlayer(): bool
    {
        return $this->sendmail_all_faction_player;
    }

    /**
     * @param bool $sendmail_all_faction_player
     * 派閥に所属しているプレイヤー全員にメール送信権限設定
     */
    public function setSendmailAllFactionPlayer(mixed $sendmail_all_faction_player): void
    {
        $this->sendmail_all_faction_player = $sendmail_all_faction_player;
    }

    /**
     * @return bool
     * 敵対派閥と友好派閥（制限あり）の設定権限
     */
    public function isFreandFactionManager(): bool
    {
        return $this->freand_faction_manager;
    }

    /**
     * @param bool $freand_faction_manager
     * 敵対派閥と友好派閥（制限あり）の設定権限設定
     */
    public function setFreandFactionManager(bool $freand_faction_manager): void
    {
        $this->freand_faction_manager = $freand_faction_manager;
    }

    /**
     * @return bool
     * 派閥からプレイヤーを追放権限
     */
    public function isKickFactionPlayer(): bool
    {
        return $this->kick_faction_player;
    }

    /**
     * @param bool $kick_faction_player
     * 派閥からプレイヤーを追放権限
     */
    public function setKickFactionPlayer(bool $kick_faction_player): void
    {
        $this->kick_faction_player = $kick_faction_player;
    }

    /**
     * @return bool
     * 土地管理権限
     */
    public function isLandManager(): bool
    {
        return $this->land_manager;
    }

    /**
     * @param bool $land_manager
     * 土地管理権限設定
     */
    public function setLandManager(bool $land_manager): void
    {
        $this->land_manager = $land_manager;
    }

    /**
     * @return bool
     * 銀行管理権限
     */
    public function isBankManager(): bool
    {
        return $this->bank_manager;
    }

    /**
     * @param bool $bank_manager
     * 銀行管理権限設定
     */
    public function setBankManager(bool $bank_manager): void
    {
        $this->bank_manager = $bank_manager;
    }

    /**
     * @return bool
     * ロール管理権限
     */
    public function isRoleManager(): bool
    {
        return $this->role_manager;
    }

    /**
     * @param bool $role_manager
     * ロール管理権限
     */
    public function setRoleManager(bool $role_manager): void
    {
        $this->role_manager = $role_manager;
    }
}