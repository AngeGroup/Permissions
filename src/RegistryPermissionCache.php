<?php
declare(strict_types=1);

namespace permissions;

use pocketmine\permission\Permission;

final class RegistryPermissionCache {

    /** @var array<string, Permission>|null */
    private ?array $permissions = null;

    public function addPermission(string $name, Permission $permission): void {
        if ($this->permissions === null) {
            $this->permissions = [];
        }
        $this->permissions[$name] = $permission;
    }

    /**
     * @return array|Permission[]
     */
    public function getPermissions(): array {
        return $this->permissions ?? [];
    }

    public function hasPermission(string $name): bool {
        return isset($this->permissions[$name]);
    }

}