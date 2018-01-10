<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AclResourceModule;

use BEAR\AclResourceModule\Resource\Page\Index;
use Ray\Di\AbstractModule;
use Ray\RoleModule\RoleProviderInterface;
use Zend\Permissions\Acl\AclInterface;

class FakeModule extends AbstractModule
{
    private $resources;
    private $acl;

    public function __construct(AclInterface $acl, array $resources)
    {
        $this->resources = $resources;
        $this->acl = $acl;
    }

    protected function configure()
    {
        $this->bind(AclInterface::class)->toInstance($this->acl);
        $this->bind(RoleProviderInterface::class)->to(FakeRoleProvider::class);
        $this->bind()->annotatedWith('resources')->toInstance($this->resources);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(Index::class),
            $this->matcher->startsWith('onGet'),
            [AclEmbedInterceptor::class]
        );
    }
}
