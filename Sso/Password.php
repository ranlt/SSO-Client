<?php
/**
 * Class for creating user passwords to a decent level of integrity
 * @category        Sso
 * @package         Sso_Password
 * @copyright       Copyright (c) 2010 Cable&Wireless
 * @author          B Hanlon <barney@ibuildings.com>
 * @version         Revision $LastChangedRevision$ by $LastChangedBy$ on $LastChangedDate$  
 * @access          public
 */


/**
 * Class for creating user passwords to a decent level of integrity
 * @category        Sso
 * @package         Sso_Password
 * @copyright       Copyright (c) 2010 Cable&Wireless
 * @author          B Hanlon <barney@ibuildings.com>
 * @version         Revision $LastChangedRevision$ by $LastChangedBy$ on $LastChangedDate$  
 * @access          public
 */

class Sso_Password extends Sso_Password_PasswordAbstract 
{
    /**
     * The default minimum characters required for the password.
     * @var integer
     */
    const CHAR_MIN_DEFAULT = 8;
    
    /**
     * The default maximum characters required for the password.
     *
     */
    const CHAR_MAX_DEFAULT = 16;
    

    /**
     * Whether or not the randomizer has been seeded
     *
     * @var boolean
     */
    protected static $_seed;
        
    /**
     * An array of characters we wish to use.  
     * @var string
     */
  
    protected static $_letterList = 'abcdefghijklmnopqrstuvwxyz';
    
    /**
     * A list of symbols we will seed the password with. Can't use '¬', '¦', or '£' 
     * as it won't display in Firefox,
     *
     * @var array
     */
    protected static $_symbolList = array(
        '!', '\'', '$', '%', '^', '&', '*', '(', ')', '-', '_', '=', '+', 
        '[', '{', ']', '}', ';', ':', "'", '@', '#', '~', 
        '\\', '|', ',', '<', '.', '>', '/', '?'
    );
    
    /**
     * Used (optionally) to assist in helping people remember their passwords
     *
     * @var array
     * @todo Run this list past security to check they're happy
     */
    
    protected static $_easyToRememberNames = array(
        'amazon','britney','china','disney','elvis','firefox','google','harry',
        'ipod','jessica','kmart','laptop','microsoft','nokia','oprah','paris',
        'quick','radio','sony','target','usher','virgin','weather','xbox','yellow',
        'zodiac'
    );
    
    /**
     * Set the global symbol list
     *
     * @param array $symbols
     */
    public static function setSymbolList(array $symbols = array())
    {
        self::$_symbolList = $symbols;
    }
    
    /**
     * Fetch the global symbol list
     *
     * @return array
     */
    public static function getSymbolList()
    {
        return self::$_symbolList;
    }


    /**
     * Static factory-style method to help create passwords automagically
     *
     * @param integer $minChars
     * @param integer $maxChars
     * @return Sso_Password_PasswordAbstract
     */
    public static function generate ($minChars = null, $maxChars = null)
    {
        self::_seedRandom();
        
        if (is_null($minChars)) {
            $minChars = self::CHAR_MIN_DEFAULT;
        }
        
        if (is_null($maxChars)) {
            $maxChars = self::CHAR_MAX_DEFAULT;
        }
        $length = mt_rand($minChars, $maxChars);

        $password = new self($length);
        $password->setSymbols(self::getSymbolList());
        $password->setLetters(self::$_letterList);
        return $password;
        
    }
   
    /**
     * Seeds the random number generator. This is static as it is good practice
     * to seed the Mersenne Twist only once per request.  We also put this into a separate
     * function in case we feel that this isn't somehow random enough.
     * 
     * @see http://www.php.net/manual/en/function.mt-srand.php
     */
    protected static function _seedRandom()
    {
        if (!self::$_seed) {
            $hash   = crc32( (double)(microtime() ^ posix_getpid()) );
            $seed   = hexdec($hash);
            $seed &= 0x7fffffff;
            mt_srand($seed);
            self::$_seed = true;
        }
    }
}
?>