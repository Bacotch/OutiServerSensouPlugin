<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\roledata;

use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;

/**
 * 派閥のロールデータ
 */
class RoleData
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
     * ロールカラー
     */
    private int $color;

    /**
     * ロールの位置
     * @var int
     */
    private int $position;

    /**
     * @var int
     * 宣戦布告権限
     */
    private int $sensen_hukoku;

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
     * 派閥メンバーの管理権限
     *
     * @var int
     */
    private int $member_manager;

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

    public function __construct(int $id, int $faction_id, string $name, int $color, int $position, int $sensen_hukoku, int $sendmail_all_faction_player, int $freand_faction_manager, int $member_manager, int $land_manager, int $bank_manager, int $role_manager)
    {
        $this->id = $id;
        $this->faction_id = $faction_id;
        $this->name = $name;
        $this->color = $color;
        $this->position = $position;
        $this->sensen_hukoku = $sensen_hukoku;
        $this->sendmail_all_faction_player = $sendmail_all_faction_player;
        $this->freand_faction_manager = $freand_faction_manager;
        $this->member_manager = $member_manager;
        $this->land_manager = $land_manager;
        $this->bank_manager = $bank_manager;
        $this->role_manager = $role_manager;
    }

    /**
     * データ保存
     */
    private function update()
    {
        Main::getInstance()->getDatabase()->executeChange(
            "outiserver.roles.update",
            [
                "name" => $this->name,
                "color" => $this->color,
                "position" => $this->position,
                "sensen_hukoku" => $this->sensen_hukoku,
                "sendmail_all_faction_player" => $this->sendmail_all_faction_player,
                "freand_faction_manager" => $this->freand_faction_manager,
                "member_manager" => $this->member_manager,
                "land_manager" => $this->land_manager,
                "bank_manager" => $this->bank_manager,
                "role_manager" => $this->role_manager,
                "id" => $this->id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
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
     * @param int $faction_id
     */
    public function setFactionId(int $faction_id): void
    {
        $this->faction_id = $faction_id;
        $this->update();
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
        $this->update();
    }

    /**
     * @return int
     */
    public function getColor(): int
    {
        return $this->color;
    }

    /**
     * @param int $color
     */
    public function setColor(int $color): void
    {
        $this->color = $color;
        $this->update();
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
        $this->update();
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
        $this->update();
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
        $this->update();
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
        $this->update();
    }

    /**
     * 派閥メンバー管理権限
     *
     * @return bool
     */
    public function isMemberManager(): bool
    {
        return (bool)$this->member_manager;
    }

    /**
     * 派閥メンバー管理権限の設定
     *
     * @param bool $member_manager
     */
    public function setMemberManager(bool $member_manager): void
    {
        $this->member_manager = (int)$member_manager;
    }

    /**
     * @return bool
     * 派閥の土地管理権限
     */
    public function isLandManager(): bool
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
        $this->update();
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
        $this->update();
    }

    /**
     * @return bool
     * 派閥ロール管理権限があるかどうか
     */
    public function isRoleManager(): bool
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
        $this->update();
    }
}
