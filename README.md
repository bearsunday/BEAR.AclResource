# BEAR.AclResource

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AclResourceModule/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.AclResourceModule.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.AclResourceModule)

An ACL embedded resources module for BEAR.Sunday

This module embeds the app resource corresponding to the ACL in the specified page resource.
Whereas the `@Embedded` annotation hard-codes and embeds app resources, this module embeds resources based on configuration.
You can change the resource tree of page without changing the source code.

# Install

## Composer install

    $ composer require bearsunday/acl-resource 1.x-dev
    
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
        $acl->addResource(new Resource('app://self/entries'));
        $acl->addResource(new Resource('app://self/users'));
        $acl->addResource(new Resource('app://self/comments'));
        $acl->allow('guest', ['app://self/entries', 'app://self/comments']);
        $acl->allow('admin', 'app://self/friends');
        // configure embedded resource list
        $resources = [
            'page://self//blog' => [
                 'app://self/entries',
                 'app://self/comments',
                 'app://self/friends'
             ],
            'page://self//admin/setting' => [
                'app://self/user{?id}',
                'app://self/freinds?user_id={id}'
            ]
        ];
        // define provider
        $roleProviderClass = DevRoleProvider::class;
        // install module        
        $this->install(new AclResourceModule($acl, $resources, $roleProviderClass));
    }
}
```

 * `$acl` has an ACL (access control list) of [Zend\Permaissions\Acl](https://framework.zend.com/manual/2.2/en/modules/zend.permissions.acl.intro.html) Specify.
`addResource` all the URI path of the `app` resources available for `$acl` and specify an app resource that is accessible / impossible to the role with the `allow()` / `disallow()` method.
 * `$resources` is a list of which app resources can be embedded by each page resource.
 * `$roleProviderClass` specifies the class name to return the current user's role (eg from login status). You need to implement `RoleProviderInterface`.

In the example above, when accessing the `/blog` page with `guest` authority, **request objects** of `app://self/entries` and ` app://self/comments` are set to `$body['entries']`, `$body['comments]`.
