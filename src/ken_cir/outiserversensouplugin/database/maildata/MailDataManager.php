<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\maildata;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function array_values;
use function count;
use function array_reverse;

/**
 * メールデータマネージャー
 *
 * MailData -> PlayerData
 */
class MailDataManager
{
    private DataConnector $connector;

    /**
     * @var MailDataManager $this
     */
    private static self $instance;

    private int $seq;

    /**
     * @var MailData[]
     */
    private array $mailDatas;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->mailDatas = [];

        $this->connector->executeSelect(
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
        $this->connector->executeSelect(
            "outiserver.mails.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->mailDatas[$data["id"]] = new MailData($data["id"],
                        $data["sendto_xuid"],
                        $data["title"],
                        $data["content"],
                        $data["author_xuid"],
                        $data["date"],
                        $data["read"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
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
        if (!isset($this->mailDatas[$id])) return false;
        return $this->mailDatas[$id];
    }

    /**
     * @return MailData[]
     */
    public function getAll(?bool $keyValue = false): array
    {
        if ($keyValue) return array_values($this->mailDatas);
        return $this->mailDatas;
    }

    /**
     * @param string $playerXuid
     * @param bool|null $keyValue
     * @return MailData[]
     */
    public function getPlayerMailDatas(string $playerXuid, ?bool $keyValue = false): array
    {
        $factionLands = array_filter($this->mailDatas, function (MailData $mailData) use ($playerXuid) {
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
    public function getPlayerXuid(string $xuid, ?bool $keyValue = false): array
    {
        $mail = array_filter($this->mailDatas, function (MailData $mailData) use ($xuid) {
            return $mailData->getSendtoXuid() === $xuid;
        });

        if ($keyValue) return array_values($mail);
        return array_reverse($mail, true);
    }

    /**
     * @param string $xuid
     * @return MailData[]
     *
     * $xuidが送信したメールデータを取得する
     */
    public function getPlayerAuthorXuid(string $xuid, ?bool $keyValue = false): array
    {
        $mail = array_filter($this->mailDatas, function (MailData $mailData) use ($xuid) {
            return $mailData->getAuthorXuid() === $xuid;
        });

        if ($keyValue) return array_values($mail);
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
        $this->connector->executeInsert(
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
        $this->mailDatas[$this->seq] = new MailData($this->seq,
            $sendto_xuid,
            $title,
            $content,
            $author_xuid,
            $date,
            0);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        $this->connector->executeGeneric(
            "outiserver.mails.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->mailDatas[$id]);
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
