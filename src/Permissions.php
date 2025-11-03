<?php
declare(strict_types=1);

namespace permissions;

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use UnitEnum;

final class Permissions {
    private RegistryPermissionCache $cache;

    private Permission $consoleRoot;
    private Permission $operatorRoot;
    private Permission $everyoneRoot;

    public function __construct() {
        $this->consoleRoot = DefaultPermissions::registerPermission(
            new Permission(DefaultPermissions::ROOT_CONSOLE, 'Console root')
        );
        $this->operatorRoot = DefaultPermissions::registerPermission(
            new Permission(DefaultPermissions::ROOT_OPERATOR, 'Operator root')
        );
        $this->everyoneRoot = DefaultPermissions::registerPermission(
            new Permission(DefaultPermissions::ROOT_USER, 'User root')
        );
        $this->operatorRoot->addChild($this->consoleRoot->getName(), true);
        $this->everyoneRoot->addChild($this->operatorRoot->getName(), true);
    }


    public function getRegistryCache() : RegistryPermissionCache {
        return $this->cache ??= new RegistryPermissionCache();
    }

    /**
     * @param Permission|string $permission Permission ou nom de permission (ex: "centurion.kit.use")
     * @param string|null $name Nom interne (clé unique dans le cache)
     * @param string $defaultGroup Groupe par défaut (ROOT_USER, ROOT_OPERATOR, ROOT_CONSOLE)
     * @throws MissingPermissionException
     */
    public function registerPermission(
        Permission|string $permission,
        ?string           $name = null,
        string            $defaultGroup = DefaultPermissions::ROOT_OPERATOR
    ) : void {
        $permissionsCache = $this->getRegistryCache();
        if(is_string($permission)) {
            $permName = $permission;
            $permKey = $name ?? str_replace('.', '_', $permission);
            $permission = new Permission($permName);
        } else {
            $permKey = $name ?? $permission->getName();
        }
        if($permissionsCache->hasPermission($permKey)) {
            return;
        }
        $permissionsCache->addPermission($permKey, $permission);
        switch ($defaultGroup) {
            case DefaultPermissions::ROOT_USER:
                DefaultPermissions::registerPermission($permission, [$this->everyoneRoot]);
                break;
            case DefaultPermissions::ROOT_OPERATOR:
                DefaultPermissions::registerPermission($permission, [$this->operatorRoot]);
                break;
            case DefaultPermissions::ROOT_CONSOLE:
                DefaultPermissions::registerPermission($permission, [$this->consoleRoot]);
                break;
            default:
                throw new MissingPermissionException("Invalid default permission group: $defaultGroup");
        }
    }

    /**
     * @param UnitEnum[] $enums
     * @throws MissingPermissionException
     */
    public function registerPermissionClass(array $enums) : void {
        foreach ($enums as $enum) {
            if($enum instanceof UnitEnum && property_exists($enum, 'value')) {
                $this->registerPermission($enum->value, $enum->name);
            }
        }
    }

    /**
     * @throws MissingPermissionException
     */
    public function getPermission(string $name) : Permission {
        foreach ($this->getRegistryCache()->getPermissions() as $perm) {
            if($perm instanceof Permission && $perm->getName() === $name) {
                return $perm;
            }
        }
        throw new MissingPermissionException("Unknown permission: $name");
    }

}
