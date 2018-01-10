<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AclResourceModule;

use BEAR\AclResourceModule\Exception\InvalidResourceException;
use BEAR\AclResourceModule\Exception\NotFoundResourceException;
use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Named;
use Ray\RoleModule\RoleProviderInterface;
use Zend\Permissions\Acl\AclInterface;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;

final class AclEmbedInterceptor implements MethodInterceptor
{
    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @var array
     */
    private $resources;

    /**
     * @var RoleProviderInterface
     */
    private $roleProvider;

    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @Named("resources=resources")
     */
    public function __construct(
        AclInterface $acl,
        array $resources,
        ResourceInterface $resource,
        RoleProviderInterface $provider)
    {
        $this->acl = $acl;
        $this->resources = $resources;
        $this->roleProvider = $provider;
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(MethodInvocation $invocation)
    {
        $ro = $invocation->getThis();
        /* @var $ro \BEAR\Resource\ResourceObject */

        $isTarget = array_key_exists($ro->uri->path, $this->resources);
        if (! $isTarget) {
            return $invocation->proceed();
        }
        $page = $ro->uri->path;
        $resources = $this->resources[$page];
        $role = $this->roleProvider->get();
        $this->embedded($resources, $role, $ro);

        return $ro;
    }

    private function embedded(array $resources, string $role, ResourceObject $ro)
    {
        foreach ($resources as $resource) {
            try {
                $isAllowed = $this->acl->isAllowed($role, $resource);
            } catch (InvalidArgumentException $e) {
                throw new InvalidResourceException($resource);
            }
            if (!$isAllowed) {
                continue;
            }
            $uri = sprintf('app://self/%s', $resource);
            try {
                $ro->body[$resource] = clone $this->resource->uri($uri);
            } catch (BadRequestException $e) {
                throw new NotFoundResourceException($uri, 500, $e);
            }
        }
    }
}
