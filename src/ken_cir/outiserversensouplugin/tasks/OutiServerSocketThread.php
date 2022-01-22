<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use pocketmine\utils\Binary;
use Socket;
use function socket_getpeername;
use function socket_read;
use function socket_select;
use function socket_write;
use function strlen;
use function substr;

final class OutiServerSocketThread extends Thread
{
    /**
     * ソケット
     *
     * @var Socket
     */
    private Socket $socket;

    /**
     * このスレッドが停止しているか
     *
     * @var bool
     */
    private bool $stop;

    private Socket $ipcSocket;

    private int $maxClients;

    private string $password;

    public string $cmd;

    public string $response;

    private SleeperNotifier $notifier;

    public function __construct(Socket $socket, Socket $ipcSocket, int $maxClients, string $password, SleeperNotifier $notifier)
    {
        $this->socket = $socket;
        $this->stop = false;
        $this->ipcSocket = $ipcSocket;
        $this->maxClients = $maxClients;
        $this->password = $password;
        $this->cmd = "";
        $this->response = "";
        $this->notifier = $notifier;
    }

    protected function onRun(): void
    {
        /**
         * @var Socket
         */
        $sockets = [];

        $socketAuthenticated = [];

        $socketTimeouts = [];

        $nextClientId = 0;


        while (!$this->stop) {
            $read = $sockets;
            $read["main"] = $this->socket; //this is ugly, but we need to be able to mass-select()
            $read["ipc"] = $this->ipcSocket;
            $write = null;
            $except = null;
            $socketDisconnects = [];

            if (socket_select($read, $write, $except, 5) > 0) {
                foreach ($read as $id => $socket) {
                    if ($socket === $this->socket) {
                        if (($client = socket_accept($this->socket)) !== false) {
                            if (count($sockets) >= $this->maxClients) {
                                @socket_close($client);
                            }
                            else {
                                socket_set_nonblock($client);
                                socket_set_option($client, SOL_SOCKET, SO_KEEPALIVE, 1);

                                $id = $nextClientId++;
                                $sockets[$id] = $client;
                                $socketAuthenticated[$id] = false;
                                $socketTimeouts[$id] = microtime(true) + 5;
                            }
                        }
                    }
                    elseif ($socket === $this->ipcSocket) {
                        //read dummy data
                        socket_read($socket, 65535);
                    }
                    else {
                        $p = $this->readPacket($socket, $requestID, $packetType, $payload);
                        if ($p === false) {
                            $socketDisconnects[$id] = $socket;
                            continue;
                        }

                        switch ($packetType) {
                            case 3: // ログイン
                                if ($socketAuthenticated[$id]) {
                                    $socketDisconnects[$id] = $socket;
                                    break;
                                }
                                socket_getpeername($socket, $addr);
                                if ($payload === $this->password) {
                                    $this->writePacket($socket, $requestID, 2, "");
                                    $socketAuthenticated[$id] = true;

                                }
                                else {
                                    $socketDisconnects[$id] = $socket;
                                    $this->writePacket($socket, -1, 2, "");
                                }
                                break;
                            case 2: // なんか
                                if (!$socketAuthenticated[$id]) {
                                    break;
                                }
                                if ($payload !== "") {
                                    $this->cmd = ltrim($payload);
                                    $this->synchronized(function (): void {
                                        $this->notifier->wakeupSleeper();
                                        $this->wait();
                                    });
                                    $this->writePacket($socket, $requestID, 0, str_replace("\n", "\r\n", trim($this->response)));
                                    $this->response = "";
                                    $this->cmd = "";
                                }
                                break;
                        }
                    }
                }

                foreach ($socketAuthenticated as $id => $status) {
                    if (!isset($disconnect[$id]) and !$status and $socketTimeouts[$id] < microtime(true)) { //Timeout
                        $disconnect[$id] = $sockets[$id];
                    }
                }

                foreach ($socketDisconnects as $id => $client) {
                    $this->disconnectClient($client);
                    unset($sockets[$id], $socketAuthenticated[$id], $socketTimeouts[$id]);
                }
            }
        }
    }

    private function readPacket(Socket $client, ?int &$requestID, ?int &$packetType, ?string &$payload): bool
    {
        $d = @socket_read($client, 4);

        socket_getpeername($client, $ip);
        if ($d === false) {
            return false;
        }
        if (strlen($d) !== 4) {
            return false;
        }
        $size = Binary::readLInt($d);
        if ($size < 0 or $size > 65535) {
            return false;
        }
        $buf = @socket_read($client, $size);
        if ($buf === false) {
            return false;
        }
        if (strlen($buf) !== $size) {
            return false;
        }
        $requestID = Binary::readLInt(substr($buf, 0, 4));
        $packetType = Binary::readLInt(substr($buf, 4, 4));
        $payload = substr($buf, 8, -2);
        return true;
    }

    private function writePacket(Socket $client, int $requestID, int $packetType, string $payload): void
    {
        $pk = Binary::writeLInt($requestID)
            . Binary::writeLInt($packetType)
            . $payload
            . "\x00\x00";
        socket_write($client, Binary::writeLInt(strlen($pk)) . $pk);
    }

    private function disconnectClient(Socket $client): void
    {
        socket_getpeername($client, $ip);
        @socket_set_option($client, SOL_SOCKET, SO_LINGER, ["l_onoff" => 1, "l_linger" => 1]);
        @socket_shutdown($client);
        @socket_set_block($client);
        @socket_read($client, 1);
        @socket_close($client);
    }

    public function close(): void
    {
        $this->stop = true;
    }
}