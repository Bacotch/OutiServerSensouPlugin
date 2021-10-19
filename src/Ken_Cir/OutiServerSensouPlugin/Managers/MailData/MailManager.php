<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\MailData;

use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

final class MailManager
{
    /**
     * @var MailManager $this
     */
    private static self $instance;

    private int $seq;

    /**
     * @var MailData[]
     */
    private array $mail_datas;

    public function __construct()
    {
        self::$instance = $this;
        $this->mail_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("mails.seq",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->waitAll();
        Main::getInstance()->getDatabase()->executeSelect("mails.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->mail_datas[$data["id"]] = new MailData($data["id"], $data["name"], $data["title"], $data["content"], $data["author"], $data["date"], $data["read"]);
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
    }

    /**
     * @return MailManager
     */
    public static function getInstance(): MailManager
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
     * @param string $name
     * @return MailData[]
     * nameに届いているメールデータを取得する
     */
    public function getPlayerName(string $name): array
    {
        $mail = array_filter($this->mail_datas, function (MailData $mailData) use ($name) {
            return $mailData->getName() === strtolower($name);
        });

        return array_reverse($mail, true);
    }

    /**
     * @param string $name
     * @param string $title
     * @param string $content
     * @param string $author
     * @param string $date
     * メールを作成する
     */
    public function create(string $name, string $title, string $content, string $author, string $date)
    {
        Main::getInstance()->getDatabase()->executeInsert("mails.create",
            [
                "name" => strtolower($name),
                "title" => $title,
                "content" => $content,
                "author" => $author,
                "date" => $date
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        $this->seq++;
        $this->mail_datas[$this->seq] = new MailData($this->seq, $name, $title, $content, $author, $date, 0);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        Main::getInstance()->getDatabase()->executeInsert("mails.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->mail_datas[$id]);
    }

    /**
     * @return int
     * 未読のメール数を返す
     */
    public function unReadCount(string $name): int
    {
        $count = 0;
        $mails = $this->getPlayerName($name);
        foreach ($mails as $mail) {
            if (!$mail->isRead()) {
                $count++;
            }
        }

        return $count;
    }
}