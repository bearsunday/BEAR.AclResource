<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AclResourceModule;

use BEAR\Resource\ResourceObject;
use Ray\Di\AbstractModule;
use Ray\RoleModule\RoleProviderInterface;
use Zend\Permissions\Acl\AclInterface;

class AclResourceModule extends AbstractModule
{
    /**
     * @var array
     */
    private $resources;

    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @var string
     */
    private $roleProvider;

    public function __construct(AclInterface $acl, array $resources, string $roleProvider, AbstractModule $module = null)
    {
        $this->resources = $resources;
        $this->acl = $acl;
        $this->roleProvider = $roleProvider;
        parent::__construct($module);
    }

    protected function configure()
    {
        $this->bind(AclInterface::class)->toInstance($this->acl);
        $this->bind(RoleProviderInterface::class)->to($this->roleProvider);
        $this->bind()->annotatedWith('resources')->toInstance($this->resources);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->startsWith('onGet'),
            [AclEmbedInterceptor::class]
        );
    }
}
