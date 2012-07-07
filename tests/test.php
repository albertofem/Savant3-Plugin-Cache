<?php

// Load the Savant3 class file and create an instance.
require_once '../vendor/Savant3-3.0.1/Savant3.php';
$tpl = new Savant3();

$tpl->setPluginConf('cache', array('cachePath' => "./cache/", 'defaultExpiration' => '48h'));
$tpl->setException(true);
$tpl->plugin("cache");

// Create a title.
$name = "Some Of My Favorite Books";

// Generate an array of book authors and titles.
$booklist = array(
	array(
		'author' => 'Hernando de Soto',
		'title' => 'The Mystery of Capitalism'
	),
	array(
		'author' => 'Neal Stephenson',
		'title' => 'Cryptonomicon'
	),
	array(
		'author' => 'Milton Friedman',
		'title' => 'Free to Choose'
	)
);

// Assign values to the Savant instance.
$tpl->title = $name;
$tpl->books = $booklist;

// Display a template using the assigned values.
$tpl->cache('books.tpl.php');
?>