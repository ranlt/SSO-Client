<?php
/**
 * Provides singular/plural translation
 *
 * @package Sso
 */
class Sso_Inflector
{

	/**
	 * Cached inflections
	 *
	 * @var array
	 * @static
	 */
	static protected $cache = array();

	/**
	 * Uncountable words
	 *
	 * @var array
	 * @static
	 */
	static protected $uncountable = array
	(
		'access',
		'advice',
		'art',
		'baggage',
		'dances',
		'equipment',
		'feedback',
		'fish',
		'fuel',
		'furniture',
		'food',
		'heat',
		'honey',
		'homework',
		'impatience',
		'information',
		'knowledge',
		'luggage',
		'money',
		'music',
		'news',
		'patience',
		'progress',
		'pollution',
		'research',
		'rice',
		'sand',
		'series',
		'sheep',
		'sms',
		'species',
		'staff',
		'toothpaste',
		'traffic',
		'understanding',
		'water',
		'weather',
		'work',
	);

	/**
	 * Irregular words
	 *
	 * @var string
	 * @static
	 */
	static protected $irregular = array
	(
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
	);

	/**
	 * Checks if a word is defined as uncountable.
	 *
	 * @param string $str word to check
	 * @return boolean
	 * @static
	 */
	public static function uncountable($str)
	{
		return in_array(strtolower($str), Sso_Inflector::$uncountable);
	}

	/**
	 * Makes a plural word singular.
	 *
	 * @param string $str word to singularize
	 * @param integer $count number of things
	 * @return string
	 * @static
	 */
	public static function singular($str, $count = NULL)
	{
		// Remove garbage
		$str = strtolower(trim($str));

		if (is_string($count)) {
			// Convert to integer when using a digit string
			$count = (int) $count;
		}

		// Do nothing with a single count
		if ($count === 0 OR $count > 1) {
			return $str;
		}

		// Cache key name
		$key = 'singular_'.$str.$count;

		if (isset(Sso_Inflector::$cache[$key])) {
			return Sso_Inflector::$cache[$key];
		}

		if (Sso_Inflector::uncountable($str)) {
			return Sso_Inflector::$cache[$key] = $str;
		}

		if ($irregular = array_search($str, Sso_Inflector::$irregular)) {
			$str = $irregular;
		}
		elseif (preg_match('/[sxz]es$/', $str) OR preg_match('/[^aeioudgkprt]hes$/', $str)) {
			// Remove "es"
			$str = substr($str, 0, -2);
		}
		elseif (preg_match('/[^aeiou]ies$/', $str)) {
			$str = substr($str, 0, -3).'y';
		}
		elseif (substr($str, -1) === 's' AND substr($str, -2) !== 'ss') {
			$str = substr($str, 0, -1);
		}

		return Sso_Inflector::$cache[$key] = $str;
	}

	/**
	 * Makes a singular word plural.
	 *
	 * @param string $str word to pluralise
	 * @param integer $count
	 * @return string plural version of word
	 * @static
	 */
	public static function plural($str, $count = NULL)
	{
		// Remove garbage
		$str = strtolower(trim($str));

		if (is_string($count)) {
			// Convert to integer when using a digit string
			$count = (int) $count;
		}

		// Do nothing with singular
		if ($count === 1) {
			return $str;
		}

		// Cache key name
		$key = 'plural_'.$str.$count;

		if (isset(Sso_Inflector::$cache[$key])) {
			return Sso_Inflector::$cache[$key];
		}

		if (Sso_Inflector::uncountable($str)) {
			return Sso_Inflector::$cache[$key] = $str;
		}

		if (isset(Sso_Inflector::$irregular[$str])) {
			$str = Sso_Inflector::$irregular[$str];
		}
		elseif (preg_match('/[sxz]$/', $str) OR preg_match('/[^aeioudgkprt]h$/', $str)) {
			$str .= 'es';
		}
		elseif (preg_match('/[^aeiou]y$/', $str)) {
			// Change "y" to "ies"
			$str = substr_replace($str, 'ies', -1);
		}
		else {
			$str .= 's';
		}

		// Set the cache and return
		return Sso_Inflector::$cache[$key] = $str;
	}

	/**
	 * Maintains this classes static status
	 * @final
	 */
	final private function __construct() { }
}