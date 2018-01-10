<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AclResourceModule\Resource\App;

use BEAR\Resource\ResourceObject;

class Entries extends ResourceObject
{
    public $body = [
        '<entry1>',
        '<entry2>'
    ];

    public function onGet()
    {
        return $this;
    }
}
