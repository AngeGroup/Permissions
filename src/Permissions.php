<?php
declare(strict_types=1);

namespace permissions;

use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use UnitEnum;

final class Permissions {
    private RegistryPermissionCache $cache;

    public function getRegistryCache(): RegistryPermissionCache {
        return $this->cache ?? ($this->cache = new RegistryPermissionCache());
    }

    public function registerPermission(Permission|string $permission, string $name = null, string $default = DefaultPermissionNames::GROUP_OPERATOR): void {
        $consoleRoot      = DefaultPermissions::registerPermission(new Permission(DefaultPermissions::ROOT_CONSOLE));
        $operatorRoot     = DefaultPermissions::registerPermission(new Permission(DefaultPermissions::ROOT_OPERATOR, '', [$consoleRoot]));
        $everyone         = DefaultPermissions::registerPermission(new Permission(DefaultPermissions::ROOT_USER, '', [$operatorRoot]));
        $permissionsCache = $this->getRegistryCache();

        if (in_array($name, $permissionsCache->getPermissions(), true)) {
            return;
        }

        if (is_string($permission)) {
            if ($name === null) {
                $name = str_replace('.', '_', $permission);
            }
            $permission = new Permission($permission);
        }
        $permissionsCache->addPermission($name, $permission);
        switch ($default) {
            case DefaultPermissions::ROOT_USER:
                DefaultPermissions::registerPermission($permission, [$everyone]);
                break;
            case DefaultPermissions::ROOT_OPERATOR:
                DefaultPermissions::registerPermission($permission, [$operatorRoot]);
                break;
            case DefaultPermissions::ROOT_CONSOLE:
                DefaultPermissions::registerPermission($permission, [$consoleRoot]);
                break;
            default:
                throw new MissingPermissionException("Invalid default group: {$default}");
        }
    }

    /**
     * @param UnitEnum[] $enums
     * @return void
     * @throws MissingPermissionException
     */
    public function registerPermissionClass(array $enums): void {
        foreach ($enums as $enum) {
            if ($enum instanceof UnitEnum) {
                $this->registerPermission($enum->value, $enum->name);
            }
        }
    }

    public function getPermission(string $name) : Permission {
        foreach ($this->getRegistryCache()->getPermissions() as $permission) {
            if($permission->getName() === $name) {
                return $permission;
            }
        }
        throw new MissingPermissionException("Invalid default group: $name");
    }
}
