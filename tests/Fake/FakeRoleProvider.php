<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AclResourceModule;

use Ray\RoleModule\RoleProviderInterface;

class FakeRoleProvider implements RoleProviderInterface
{
    public function get()
    {
        return 'guest';
    }
}
