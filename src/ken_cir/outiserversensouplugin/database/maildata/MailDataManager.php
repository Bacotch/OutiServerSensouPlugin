<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\maildata;

use ken_cir\outiserversensouplugin\database\landdata\LandData;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;
use function count;
use function array_values;

/**
 * メールデータマネージャー
 *
 * MailData -> PlayerData
 */
class MailDataManager
{
    /**
     * @var MailDataManager $this
     */
    private static self $instance;

    private int $seq;

    /**
     * @var MailData[]
     */
    private array $mail_datas;

    public function __construct()
    {
        $this->mail_datas = [];
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.mails.seq",
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
            }
        );
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.mails.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->mail_datas[$data["id"]] = new MailData($data["id"], $data["sendto_xuid"], $data["title"], $data["content"], $data["author_xuid"], $data["date"], $data["read"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(MailDataManager::class);
        self::$instance = new self();
    }

    /**
     * @return MailDataManager
     */
    public static function getInstance(): MailDataManager
    {
        return self::$instance;
    }

    /**
     * @param int $id
     * @return bool|MailData
     * メールデータを取得する
     */
    public function get(int $id): bool|MailData
    {
        if (!isset($this->mail_datas[$id])) return false;
        return $this->mail_datas[$id];
    }

    /**
     * @return MailData[]
     */
    public function getAll(?bool $keyValue = false): array
    {
        if ($keyValue) return array_values($this->mail_datas);
        return $this->mail_datas;
    }

    /**
     * @param string $playerXuid
     * @param bool|null $keyValue
     * @return MailData[]
     */
    public function getPlayerMailDatas(string $playerXuid, ?bool $keyValue = false): array
    {
        $factionLands = array_filter($this->mail_datas, function (MailData $mailData) use ($playerXuid) {
            return $mailData->getSendtoXuid() === $playerXuid or $mailData->getAuthorXuid() === $playerXuid;
        });

        if ($keyValue) return array_values($factionLands);
        return $factionLands;
    }

    /**
     * @param string $xuid
     * @return MailData[]
     * nameに届いているメールデータを取得する
     */
    public function getPlayerXuid(string $xuid): array
    {
        $mail = array_filter($this->mail_datas, function (MailData $mailData) use ($xuid) {
            return $mailData->getSendtoXuid() === $xuid;
        });

        return array_reverse($mail, true);
    }

    /**
     * @param string $sendto_xuid
     * @param string $title
     * @param string $content
     * @param string $author_xuid
     * @param string $date
     * メールを作成する
     */
    public function create(string $sendto_xuid, string $title, string $content, string $author_xuid, string $date)
    {
        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.mails.create",
            [
                "sendto_xuid" => $sendto_xuid,
                "title" => $title,
                "content" => $content,
                "author_xuid" => $author_xuid,
                "date" => $date
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->seq++;
        $this->mail_datas[$this->seq] = new MailData($this->seq, $sendto_xuid, $title, $content, $author_xuid, $date, 0);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.mails.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->mail_datas[$id]);
    }

    /**
     * @return int
     * 未読のメール数を返す
     */
    public function unReadCount(string $xuid): int
    {
        $count = 0;
        $mails = $this->getPlayerXuid($xuid);
        foreach ($mails as $mail) {
            if (!$mail->isRead()) {
                $count++;
            }
        }

        return $count;
    }
}
