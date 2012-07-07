Savant3-Plugin-Cache
================

A simple cache plugin to store compiled templates in disk

Installation
------------

Put `src/Savant3_Plugin_cache.php` under Savant3 plugin directory `Savant3/resources`

Usage
-----

Load plugin and configure it

    $tpl = new Savant3();

    $tpl->setPluginConf('cache', array('cachePath' => "./cache/", 'defaultExpiration' => '48h'));
    $tpl->setException(true);
    $tpl->plugin("cache");

Use 'cache' magic method instead of Savant3's display:

    $tpl->cache('books.tpl.php');

A working example is in 'tests' folder.

Configuration
-------------

This plugins admits two configuration variables:

    cachePath: Folder to store cached templates, defaults to '.' (same as rendered file)
    defaultExpiration: expiration time for cached files. Details on this are in the class comments