<?php

class Cwcl_Services_Inflector_Test extends PHPUnit_Framework_TestCase {
	
	/**
	 * Given the supplied word has no plural version in English
	 * The uncountable method should return true
	 */
	public function testUncountableFish()
	{
		$res = Cwcl_Services_Inflector::uncountable('fish');
		$this->assertTrue($res);
	}
	
	/**
	 * Given a supplied word
	 * And the count parameter is 0
	 * The singular method should return the word supplied
	 */
	public function testSingularZeroCount()
	{
		$data = 'phones';
		$res = Cwcl_Services_Inflector::singular($data, 0);
		
		$this->assertEquals($res, $data);
	}
	
	/**
	 * Given a supplied word
	 * And no count paramter
	 * The singular method should return the singular form of the word supplied
	 */
	public function testSingularNoCount()
	{
		$data = 'phones';
		$res = Cwcl_Services_Inflector::singular($data);
		
		$this->assertEquals($res, 'phone');
	}
	
	/**
	 * Given a supplied word
	 * And the count parameter is 1
	 * The plural method should return the supplied word
	 */
	public function testPluralCountOne()
	{
		$data = 'phone';
		$res = Cwcl_Services_Inflector::plural($data, 1);
		
		$this->assertEquals($res, $data);
	}
	
	/**
	 * Given a a supplied word
	 * And the count parameter is greater than 1
	 * The plural form of the word should be returned
	 */
	public function testPluralCountGreaterThanOne()
	{
		$data = 'activity';
		$res = Cwcl_Services_Inflector::plural($data, 3);
		
		$this->assertEquals($res, 'activities');
	}
}