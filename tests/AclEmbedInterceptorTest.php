<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AclResourceModule;

use BEAR\AclResourceModule\Exception\InvalidResourceException;
use BEAR\AclResourceModule\Exception\NotFoundResourceException;
use BEAR\AclResourceModule\Resource\Page\Index;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Permissions\Acl\Role\GenericRole as Role;

class AclEmbedInterceptorTest extends TestCase
{
    public function testNoResource()
    {
        $aclList = [
            'page://self/index' => [],
        ];
        $page = $this->getFakePage($aclList);
        $this->assertNull($page->body);
    }

    public function testBadResource()
    {
        $this->expectException(InvalidResourceException::class);
        $aclList = [
            'page://self/index' => [
                '__INVALID_RESOURCE__'
            ]
        ];
        $page = $this->getFakePage($aclList);
        $this->assertNull($page->body);
    }

    public function testBadRequestResource()
    {
        $this->expectException(NotFoundResourceException::class);
        $this->expectExceptionMessage('app://self/not_exsists');
        $aclList = [
            'page://self/index' => [
                'app://self/not_exsists'
            ],
        ];
        $page = $this->getFakePage($aclList);
        $this->assertNull($page->body);
    }

    public function testEmbededRoleResource()
    {
        $aclList = [
            'page://self/index' => [
                'app://self/entries',
                'app://self/users'
            ]
        ];
        $page = $this->getFakePage($aclList);
        $expected = '{
    "entries": [
        "<entry1>",
        "<entry2>"
    ]
}
';
        $this->assertInstanceOf(Request::class, $page->body['entries']);
        $view = (string) $page;
        $this->assertSame($expected, $view);
    }

    public function testEmbededRoleResourceWithQuery()
    {
        $aclList = [
            'page://self/index' => [
                'app://self/entries{?name}',
                'app://self/users'
            ]
        ];
        $page = $this->getFakePage($aclList);
        $request = $page['entries'];
        /* @var $request AbstractRequest */
        $this->assertSame('app://self/entries?name=BEAR', $request->toUri());
    }

    public function testNestedDirectoryResource()
    {
        $aclList = [
            'page://self/index' => [
                'app://self/admin/entries{?name}',
                'app://self/admin/entries?user_name={name}',
                'app://self/users'
            ]
        ];
        $page = $this->getFakePage($aclList);
        $request = $page['admin_entries'];
        /* @var $request AbstractRequest */
        $this->assertSame('app://self/admin/entries?user_name=BEAR', $request->toUri());
    }

    private function getFakePage(array $resources) : ResourceObject
    {
        $acl = new Acl();
        $roleGuest = new Role('guest');
        $acl->addRole($roleGuest);
        $acl->addRole(new Role('admin'), $roleGuest);
        $acl->addResource(new Resource('app://self/entries'));
        $acl->addResource(new Resource('app://self/not_exsists'));
        $acl->addResource(new Resource('app://self/users'));
        $acl->addResource(new Resource('app://self/admin/entries'));
        $acl->allow('guest', [
            'app://self/entries',
            'app://self/admin/entries',
            'app://self/not_exsists'
        ]);
        $acl->allow('admin', 'app://self/users');
        $module = new ResourceModule(__NAMESPACE__);
        $module->install(new AclResourceModule($acl, $resources, FakeRoleProvider::class));
        $resource = (new Injector($module, __DIR__ . '/tmp'))->getInstance(ResourceInterface::class);
        /* @var $resource ResourceInterface */

        return $resource->uri('page://self/index')->withQuery(['name' => 'BEAR'])();
    }
}
