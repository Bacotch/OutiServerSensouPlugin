<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\RoleData;

final class RoleData
{
    private string $name;
    private RolePermsData $permsData;

    /**
     * @param string $name
     * @param array $permsData
     */
    public function __construct(string $name, array $permsData)
    {
        $this->name = $name;
        $this->permsData = new RolePermsData($permsData);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array(
            "name" => $this->name,
            "perms" => $this->permsData->toArray()
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return RolePermsData
     */
    public function getPermsData(): RolePermsData
    {
        return $this->permsData;
    }
}