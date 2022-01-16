<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\network;

use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\exception\OutiServerSocketException;
use ken_cir\outiserversensouplugin\threads\OutiServerSocketThread;
use pocketmine\network\NetworkInterface;
use pocketmine\snooze\SleeperHandler;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\TextFormat;
use Socket;

final class OutiServerSocket implements NetworkInterface
{
    /**
     * ソケット本体
     *
     * @var Socket|false
     */
    private Socket|false $socket;

    /**
     * @var Socket
     */
    private Socket $ipcMainSocket;

    private Socket $ipcThreadSocket;
    private OutiServerSocketThread $thread;

    public function __construct(string $ip, int $port, SleeperHandler $sleeper)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new OutiServerSocketException("ソケットの作成に失敗しました: " . socket_strerror(socket_last_error()));
        }

        if (!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            throw new OutiServerSocketException("ソケットのオプション設定に失敗しました: " . socket_strerror(socket_last_error()));
        }

        if (!@socket_bind($this->socket, $ip, $port) or !@socket_listen($this->socket, 5)) {
            throw new OutiServerSocketException("メインのソケットを開くことができませんでした: " . socket_strerror(socket_last_error()));
        }

        socket_set_block($this->socket);

        $ret = @socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $ipc);
        if (!$ret) {
            $err = socket_last_error();
            if (($err !== SOCKET_EPROTONOSUPPORT and $err !== SOCKET_ENOPROTOOPT) or !@socket_create_pair(AF_INET, SOCK_STREAM, 0, $ipc)) {
                throw new OutiServerSocketException('IPCソケットを開くことができませんでした' . trim(socket_strerror(socket_last_error())));
            }
        }

        [$this->ipcMainSocket, $this->ipcThreadSocket] = $ipc;

        $notifier = new SleeperNotifier();
        $sleeper->addNotifier($notifier, function() : void{
            if (str_starts_with($this->thread->cmd, "data:")) {
                $factionData = FactionDataManager::getInstance()->get(6);
                $this->thread->response = match ($this->thread->cmd) {
                    "data:faction:6" => json_encode(array(
                        "id" => $factionData->getId(),
                        "owner" => $factionData->getOwner(),
                        "name" => $factionData->getName()
                    )),
                    default => TextFormat::clean("不明なdata: {$this->thread->cmd}"),
                };
            }
            else {
                $this->thread->response = TextFormat::clean("OK");
            }
            $this->thread->synchronized(function(OutiServerSocketThread $thread) : void{
                $thread->notify();
            }, $this->thread);
        });

        $this->thread = new OutiServerSocketThread($this->socket, $this->ipcThreadSocket, 50,"FYScZ6wuaak=", $notifier);
    }

    public function start(): void
    {
        $this->thread->start(PTHREADS_INHERIT_NONE);
    }

    public function setName(string $name): void
    {
    }

    public function tick(): void
    {
    }

    public function shutdown(): void
    {
        $this->thread->close();
        socket_write($this->ipcMainSocket, "\x00");
        $this->thread->quit();

        @socket_close($this->socket);
        @socket_close($this->ipcMainSocket);
        @socket_close($this->ipcThreadSocket);
    }
}