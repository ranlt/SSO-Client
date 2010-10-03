<?php

require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_Inflector extends PHPUnit_Framework_TestCase {
	public $client;
	
	public $words = array(
		'child' => 'children',                                                               
		'clothes' => 'clothing',                                                             
		'man' => 'men',                                                                      
		'movie' => 'movies',                                                                 
		'person' => 'people',                                                                
		'woman' => 'women',                                                                  
		'mouse' => 'mice',                                                                   
		'goose' => 'geese',                                                                  
		'ox' => 'oxen',                                                                      
		'leaf' => 'leaves',                                                                  
		'course' => 'courses',                                                               
		'size' => 'sizes',
//		'cactus' => 'cacti', // known to not work
		'bus' => 'buses',
		'tree' => 'trees',
		'currency' => 'currencies',
//		'axis' => 'axes', // known to not work
		'size' => 'sizes',
		'movie' => 'movies'
    );   
	
	public function setup()
	{
	}
	
	public function tearDown()
	{
	}
	
	public function testUncountable()
	{
		$this->assertTrue(Sso_Inflector::uncountable('fish'));
		$this->assertTrue(Sso_Inflector::uncountable('patience'));
		$this->assertTrue(Sso_Inflector::uncountable('sheep'));
		$this->assertTrue(Sso_Inflector::uncountable('rice'));
		$this->assertTrue(Sso_Inflector::uncountable('species'));
		$this->assertFalse(Sso_Inflector::uncountable('tree'));
		$this->assertFalse(Sso_Inflector::uncountable('cat'));
		$this->assertFalse(Sso_Inflector::uncountable('grass'));
		$this->assertFalse(Sso_Inflector::uncountable('chair'));
		$this->assertFalse(Sso_Inflector::uncountable('apple'));
	}
	
	public function testSingularToPlural()
	{
		foreach ($this->words as $singular => $plural) {
			$this->assertEquals(Sso_Inflector::plural($singular), $plural);
		}
	}

	public function testPluralToSingular()
	{
		foreach ($this->words as $singular => $plural) {
			$this->assertEquals(Sso_Inflector::singular($plural), $singular);
		}
	}
}

