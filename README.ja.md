# BEAR.AclResourceModule

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.AclResourceModule.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.AclResourceModule)

An ACL embedded resources module for BEAR.Sunday

このモジュールは指定したpageリソースにACLに応じたappリソースを埋め込みます。
`@Embedded`アノテーションがappリソースをハードコードして埋め込むのに対して、このモジュールではACLと設定に基づいてリソースを埋め込みます。
ソースコードに変更を加えることなくpageのリソースツリーを変更することができます。

# Install

## Composer install

    $ composer require bearsunday/acl-resource-module 1.x-dev
    
## Module install

```php
use Ray\Di\AbstractModule;
use Ray\TestDouble\TestDoubleModule;
use Ray\RoleModule\RoleProviderInterface;

class DevRoleProvider implements RoleProviderInterface
{
    // provide role
    public function get()
    {
        return 'guest';
    }
}

class AppModule extends AbstractModule
{
    protected function configure()
    {
        // configure ACL
        $acl = new Acl();
        $roleGuest = new Role('guest');
        $acl->addRole($roleGuest);
        $acl->addRole(new Role('owner'), $roleGuest);
        $acl->addResource(new Resource('entries'));
        $acl->addResource(new Resource('users'));
        $acl->addResource(new Resource('comments'));
        $acl->allow('guest', ['entries', 'comments']);
        $acl->allow('admin', 'friends');
        // configure embedded resource list
        $resources = [
            '/blog' => ['entries', 'comments', 'friends'],
        ];
        // define provider
        $roleProviderClass = DevRoleProvider::class;
        // install module        
        $this->install(new AclResourceModule($acl, $resources, $roleProviderClass));
    }
}
```

 * `$acl`には[Zend\Permaissions\Acl](https://framework.zend.com/manual/2.2/en/modules/zend.permissions.acl.intro.html)のACL(access control list)を指定します。
`$acl`に利用可能な`app`リソースのURのpathを全て`addResource`し、ロールに対してアクセス可能/不可能なappリソースを`allow()/disallow()`メソッドで指定します。
 * `$resources`はそれぞれのpageリソースがどのappリソースをembeddedできるかのリストです。
 * `$roleProviderClass`には現在のユーザーのロールを(ログイン状態などから)返すクラス名を指定します。`RoleProviderInterface`を実装する必要があります。

上記に例では`guest`権限で`/blog`ページをアクセスすると`app://self/entries`と`app://self/comments`の**リクエスト**がそれぞれ`$body['entries]`に`$body['comments]`にセットされます。