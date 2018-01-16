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
            '/index' => [],
        ];
        $page = $this->getFakePage($aclList);
        $ro = $page();
        $this->assertNull($ro->body);
    }

    public function testBadResource()
    {
        $this->expectException(InvalidResourceException::class);
        $aclList = [
            '/index' => ['__INVALID_RESOURCE__'],
        ];
        $page = $this->getFakePage($aclList);
        $page();
        $this->assertNull($page->body);
    }

    public function testBadRequestResource()
    {
        $this->expectException(NotFoundResourceException::class);
        $this->expectExceptionMessage('app://self/not_exsists');
        $aclList = [
            '/index' => ['not_exsists'],
        ];
        $page = $this->getFakePage($aclList);
        $ro = $page();
        $this->assertNull($ro->body);
    }

    public function testEmbededRoleResource()
    {
        $aclList = [
            '/index' => ['entries', 'users'],
        ];
        $page = $this->getFakePage($aclList);
        $page();
        $expected = '{
    "entries": [
        "<entry1>",
        "<entry2>"
    ]
}
';
        $this->assertInstanceOf(Request::class, $page->body['entries']);
        $view = (string) $ro;
        $this->assertSame($expected, $view);
    }

    public function testEmbededRoleResourceWithQuery()
    {
        $aclList = [
            '/index' => ['entries{?name}', 'users'],
        ];
        $page = $this->getFakePage($aclList);
        $page();
        $request = $page['entries'];
        /* @var $request AbstractRequest */
        $this->assertSame('app://self/entries?name=BEAR', $request->toUri());
    }

    public function testNestedDirectoryResource()
    {
        $aclList = [
            '/index' => ['admin/entries{?name}', 'users'],
        ];
        $page = $this->getFakePage($aclList);
        $page();
        $request = $page['admin/entries'];
        /* @var $request AbstractRequest */
        $this->assertSame('app://self/entries?name=BEAR', $request->toUri());
    }

    private function getFakePage(array $resources) : Request
    {
        $acl = new Acl();
        $roleGuest = new Role('guest');
        $acl->addRole($roleGuest);
        $acl->addRole(new Role('admin'), $roleGuest);
        $acl->addResource(new Resource('entries'));
        $acl->addResource(new Resource('not_exsists'));
        $acl->addResource(new Resource('users'));
        $acl->allow('guest', ['entries', 'not_exsists']);
        $acl->allow('admin', 'users');
        $module = new ResourceModule(__NAMESPACE__);
        $module->install(new AclResourceModule($acl, $resources, FakeRoleProvider::class));
        $resource = (new Injector($module, __DIR__ . '/tmp'))->getInstance(ResourceInterface::class);
        /* @var $resource ResourceInterface */

        return $resource->uri('page://self/index')->withQuery(['name' => 'BEAR']);
    }
}
