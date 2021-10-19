<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Utils;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use SQLite3;
use SQLiteException;

/**
 * db操作系クラス
 */
class Database
{
    /**
     * @var SQLite3
     * SQLite3オブジェクト
     */
    public SQLite3 $db;

    /**
     * @var Main
     */
    private Main $plugin;

    /**
     * @param Main $plugin
     * @param string $dir
     */
    public function __construct(Main $plugin, string $dir)
    {
        $this->plugin = $plugin;

        try {
            $this->db = new SQLite3($dir);
            # $this->db->exec("DROP TABLE players");
            # $this->db->exec("DROP TABLE factions");
            $this->db->exec("CREATE TABLE IF NOT EXISTS players (name TEXT PRIMARY KEY, ip TEXT, faction INTEGER, chatmode INTEGER, drawscoreboard INTEGER, mails TEXT)");
            $this->db->exec("CREATE TABLE IF NOT EXISTS factions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, owner TEXT, color INTEGER)");
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * db接続を閉じる
     */
    public function close()
    {
        try {
            $this->db->close();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * @return bool|array
     * プレイヤーデータを取得する
     */
    public function getPlayer(string $name): bool|array
    {
        try {
            $sql = $this->db->prepare("SELECT * FROM players WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $result = $sql->execute();
            $data = $result->fetchArray();
            if (!$data) return false;
            $data["mails"] = unserialize($data["mails"]);
            return $data;
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }

        return false;
    }

    public function getAllPlayer()
    {
        $data = [];
        $sql = $this->db->prepare("SELECT * FROM players");
        $result = $sql->execute();
        while ($d = $result->fetchArray(SQLITE3_ASSOC)) {
            $d["mails"] = unserialize($d["mails"]);
            $data[] = $d;
        }

        if (count($data) < 1) return false;
        return $data;
    }

    /**
     * @param int $id
     * idの派閥に入っているプレイヤーデータを取得する
     */
    public function getPlayerFaction(int $id): bool|array
    {
        try {
            $all_data = [];
            $sql = $this->db->prepare("SELECT * FROM players WHERE faction = :faction");
            $sql->bindValue(":faction", $id, SQLITE3_INTEGER);
            $result = $sql->execute();
            while ($d = $result->fetchArray(SQLITE3_ASSOC)) {
                $all_data[] = $d;
            }

            if (count($all_data) < 1) return false;

            return $all_data;
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }

        return false;
    }

    /**
     * @param string $name
     * プレイヤーの未読メールの数を取得
     */
    public function getPlayerNewMail(string $name): int
    {
        try {
            $data = $this->getPlayer($name);
            $count = 0;
            var_dump($data["mails"]);
            foreach ($data["mails"] as $mail) {
                if (!$mail["read"]) $count++;
            }

            return $count;
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }

        return 0;
    }

    /**
     * @param string $name
     * @param string $ip
     * プレイヤーデータを追加する
     */
    public function addPlayer(string $name, string $ip)
    {
        try {
            if ($this->getPlayer($name)) return;
            $sql = $this->db->prepare("INSERT INTO players VALUES (:name, :ip, :faction, :chatmode, :drawscoreboard, :mails)");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":ip", $ip, SQLITE3_TEXT);
            $sql->bindValue(":faction", null, SQLITE3_NULL);
            $sql->bindValue(":chatmode", -1, SQLITE3_INTEGER);
            $sql->bindValue(":drawscoreboard", (int)true, SQLITE3_INTEGER);
            $sql->bindValue(":mails", serialize(array()), SQLITE3_TEXT);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * Playerデータを削除する
     */
    public function deletePlayer(string $name)
    {
        try {
            if (!$this->getPlayer($name)) return;
            $sql = $this->db->prepare("DELETE FROM players WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    public function setPlayer(string $name, string $ip, int $faction, int $chatmode, bool $drawscoreboard, array $mails)
    {
        try {
            if (!$this->getPlayer($name)) return;
            $sql = $this->db->prepare("UPDATE players SET ip = :ip, faction = :faction, chatmode = :chatmode, drawscoreboard = :drawscoreboard, mails = :mails  WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":ip", $ip, SQLITE3_TEXT);
            $sql->bindValue(":faction", $faction, SQLITE3_INTEGER);
            $sql->bindValue(":chatmode", $chatmode, SQLITE3_INTEGER);
            $sql->bindValue(":drawscoreboard", (int)$drawscoreboard, SQLITE3_INTEGER);
            $sql->bindValue(":mails", serialize($mails), SQLITE3_TEXT);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * @param int $id
     * プレイヤーデータのfactionを設定する
     */
    public function setPlayerfaction(string $name, int $id)
    {
        try {
            if (!$this->getPlayer($name)) return;
            $sql = $this->db->prepare("UPDATE players SET faction = :faction WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":faction", $id, SQLITE3_INTEGER);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * @param int $mode
     * プレイヤーデータのchatmodeを設定する
     */
    public function setPlayerChatMode(string $name, int $mode)
    {
        try {
            if (!$this->getPlayer($name)) return;
            $sql = $this->db->prepare("UPDATE players SET chatmode = :chatmode WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":chatmode", $mode, SQLITE3_INTEGER);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * @param bool $draw_scoreboard
     * プレイヤーデータのdrawscoreboardを設定する
     */
    public function setPlayerDrawScoreBoard(string $name, bool $draw_scoreboard)
    {
        try {
            if (!$this->getPlayer($name)) return;
            $sql = $this->db->prepare("UPDATE players SET drawscoreboard = :drawscoreboard WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":drawscoreboard", (int)$draw_scoreboard, SQLITE3_INTEGER);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * @param string $title
     * @param string $content
     * @param string $author
     * @param string $date
     * Playerデータにメールを追加する
     */
    public function addPlayerMail(string $name, string $title, string $content, string $author, string $date)
    {
        try {
            if (!$player_data = $this->getPlayer($name)) return;
            $player_data["mails"][] = array(
                "title" => $title,
                "content" => $content,
                "author" => $author,
                "date" => $date,
                "read" => false
            );
            $sql = $this->db->prepare("UPDATE players SET mails = :mails WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":mails", serialize($player_data["mails"]), SQLITE3_TEXT);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $title
     * @param string $content
     * @param string $author
     * @param string $date
     * プレイヤー全員にメールを追加する
     */
    public function addAllPlayerMail(string $title, string $content, string $author, string $date)
    {
        $all_player = $this->getAllPlayer();
        if (!$all_player) return;
        foreach ($all_player as $player) {
            $this->addPlayerMail($player["name"], $title, $content, $author, $date);
        }
    }

    /**
     * @param string $name
     * @param int $key
     * @param array $mail
     * プレイヤーデータのメール情報を更新する
     */
    public function setPlayerMail(string $name, int $key, array $mail)
    {
        try {
            if (!$this->getPlayer($name)) return;
            $sql = $this->db->prepare("UPDATE players SET mails = :mails WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":mails", serialize($mail), SQLITE3_TEXT);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param string $name
     * @param int $key
     * プレイヤーデータのメールを削除する
     */
    public function deletePlayerMail(string $name, int $key)
    {
        try {
            if (!$player_data = $this->getPlayer($name)) return;
            unset($player_data["mails"][$key]);
            $sql = $this->db->prepare("UPDATE players SET mails = :mails WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":mails", serialize($player_data["mails"]), SQLITE3_TEXT);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param int $id
     * 派閥データをIDで取得
     */
    public function getFactionById(int $id): bool|array
    {
        try {
            $sql = $this->db->prepare("SELECT * FROM factions WHERE  id = :id");
            $sql->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $sql->execute();
            return $result->fetchArray();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }

        return false;
    }

    /**
     * @param string $name
     * 派閥データを派閥名で取得
     */
    public function getFactionByName(string $name): bool|array
    {
        try {
            $sql = $this->db->prepare("SELECT * FROM factions WHERE name = :name");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $result = $sql->execute();
            return $result->fetchArray();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }

        return false;
    }

    /**
     * @param string $owner
     * 派閥データをowner名で取得
     */
    public function getFactionByOwner(string $owner): bool|array
    {
        try {
            $sql = $this->db->prepare("SELECT * FROM factions WHERE owner = :owner");
            $sql->bindValue(':owner', strtolower($owner), SQLITE3_TEXT);
            $result = $sql->execute();
            return $result->fetchArray();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }

        return false;
    }

    /**
     * @param string $name
     * @param string $owner
     * 派閥を追加する
     */
    public function addFaction(string $name, string $owner, int $color)
    {
        try {
            if ($this->getFactionByName($name)) return;
            $sql = $this->db->prepare("INSERT INTO factions (name, owner, color) VALUES (:name, :owner, :color)");
            $sql->bindValue(':name', strtolower($name), SQLITE3_TEXT);
            $sql->bindValue(":owner", strtolower($owner), SQLITE3_TEXT);
            $sql->bindValue(":color", $color, SQLITE3_INTEGER);
            $sql->execute();
            $id = $this->db->query("SELECT seq FROM sqlite_sequence WHERE name = 'factions'")->fetchArray()["seq"];
            $this->set_Player_faction($owner, $id);
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }

    /**
     * @param int $id
     * 派閥データを削除する
     */
    public function deleteFaction(int $id)
    {
        try {
            if (!$this->getFactionById($id)) return;
            $sql = $this->db->prepare("DELETE FROM factions WHERE id = :id");
            $sql->bindValue(":id", $id, SQLITE3_INTEGER);
            $sql->execute();
        } catch (SQLiteException | Error | Exception $error) {
            $this->plugin->logger->error($error);
        }
    }
}