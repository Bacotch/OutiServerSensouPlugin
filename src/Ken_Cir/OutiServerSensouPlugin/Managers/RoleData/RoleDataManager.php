<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\RoleData;

final class RoleDataManager
{
    /**
     * @var RoleData[]
     */
    private array $role_datas;

    /**
     * @param array $roles
     */
    public function __construct(array $roles)
    {
        if (count($roles) < 1) {
            $this->role_datas = [];
            return;
        }

        foreach ($roles as $role) {
            $this->role_datas[$role["name"]] = new RoleData($role["name"], $role["perms"]);
        }
    }

    /**
     * @return array[]
     */
    public function getRoleDatas(): array
    {
        $roles = [];
        foreach ($this->role_datas as $roleData) {
            $roles[] = $roleData->toArray();
        }
        return $roles;
    }

    /**
     * @param string $name
     * @return false|RoleData
     */
    public function get(string $name): bool|RoleData
    {
        if (!isset($this->role_datas[$name])) return false;
        return $this->role_datas[$name];
    }

    /**
     * @param string $name
     * @param array $perms
     * ロール作成
     */
    public function create(string $name, array $perms)
    {
        if ($this->get($name)) return;
        $this->role_datas[$name] = new RoleData($name, $perms);
    }

    /**
     * @param string $name
     * ロール削除
     */
    public function delete(string $name)
    {
        unset($this->role_datas[$name]);
    }
}