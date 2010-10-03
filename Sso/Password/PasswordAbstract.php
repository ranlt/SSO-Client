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
abstract class Sso_Password_PasswordAbstract
{
    
    /**
     * The pasword itself.
     *
     * @var string
     */
    protected $_string = '';
    
    
    /**
     * An array of symbols to use in password generation by this instance.
     *
     * @var array
     */
    protected $_symbols = array();
    
    /**
     * The array of letters used in constructiong a new password.
     *
     * @var string
     */
    protected $_letters;
    
    /**
     * The maximum length of the password
     *
     * @var integer
     */
    protected $_maxLength;
    
    
    /**
     * The actual length of this password
     *
     * @var integer
     */
    protected $_length;    
    
    
    /**
     * Constructor
     *
     * @param integer $length
     */
    public function __construct($length)
    {
        $this->_length = $length;
    }
    
    public function getLength()
    {
        if (!$this->_length) {
            require_once 'Sso/Password/PasswordException.php';
            throw new Sso_Password_PasswordException(
                'No length set', 
                Sso_Password_PasswordException::LENGTH_NOT_SET
            );
        }
        return $this->_length;
    }


    
    
    /**
     * Set the letters to use in password generation
     *
     * @param string $letters
     */
    public function setLetters($letters)
    {
        $this->_letters = str_shuffle($letters);
    }
    
    
    /**
     * Return the main letter string.  Mainly used for testing.
     *
     * @return unknown
     */
    public function getLetters()
    {
        return $this->_letters;
    }
    

    
    /**
     * Returns a string of the password, autogenerating if none is present.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->getPasswordString();
        } catch (Sso_Password_PasswordException $e) {
            return '';
        }
    }

    /**
     * To allow users to create their own passwords, we allow them to inject it here.
     *
     * @param unknown_type $string
     */
    
    public function setPasswordString($string)
    {
        $this->_string = $string;
    }
   
    /**
     * Returns the password, lazily creating one if one has not been generated before.
     *
     * @return string
     */
    public function getPasswordString()
    {
        if (!$this->_string) {
            $this->_string = $this->_generatePasswordString();
        }

        return $this->_string;
    } 


    /**
     * Set the symbols to use in password creation.
     *
     * @param array $symbols
     */
    public function setSymbols(array $symbols)
    {
        $this->_symbols = $symbols;
    }
    
    /**
     * Get the list of symbols used by the password generator
     *
     * @return unknown
     */
    public function getSymbols()
    {
        return $this->_symbols;
    }
    
    /**
     * Add symbols to a string.
     *
     * @param array|string $string
     */
    public function addSymbolsToString($string)
    {
        if (!is_array($string)) {
            $string = explode('', $string);
        }
        // Get a random number between one and the length and insert that many 
        // numerical digits at random places in the string. Note that the same 
        // position may be chosen more than once, due to the random function, 
        // so we may get less numbers than the number chosen
        $symbols = $this->getSymbols();
        
        $numberOfSymbolsToReplace = mt_rand(1, count($string));
        for ($i = 0; $i<$numberOfSymbolsToReplace; $i++) {
            $positionForSymbolInString = mt_rand(0, count($string));
            $symbol = $symbols[mt_rand(0, count($symbols) -1)];
            $string[$positionForSymbolInString] = $symbol;
        }
        
        return implode('', $string);
    }
    
    /**
     * Generate a string of random characters to the length specified.
     *
     * @param boolean $useSymbols Whether or not to use symbols
     */
    protected function _generatePasswordString($useSymbols = true)
    {
        $passwordArray = array();
        
        for ($i = 0; $i < $this->getLength(); $i++) {
            $capitalize = fmod(mt_rand(), 2);
            $letter = $this->_letters[mt_rand(0, strlen($this->getLetters()) - 1)];

            $passwordArray[$i] = (0 == $capitalize) ? strtoupper($letter) : $letter;
        }
        

        
        $digitCount = mt_rand(1, $this->getLength());
        
        for ($j = 0; $j < $digitCount; $j++) {
            // choose a position for this number, less than the length of the password
            $positionForDigit = mt_rand(0, $digitCount - 1);
            // Note that the same position may be chosen more than once,
            // due to the random function, so we may get less numbers than the number chosen
            // choose a number from 0 to 9
            $theNumber = mt_rand(0, 9);
            $passwordArray[$positionForDigit] = $theNumber;       
        }
        
        // at this point, the password is going to definetely have a letter and a number.
        // its the final part that may kill that, because a symbol could remove the
        // remaining letters and numbers and leave it as invalid.

        
        if (!$useSymbols) {
            $passwordString = implode($passwordArray);
        } else {
            require_once 'Sso/Password/Validate.php';
            $validator = new Sso_Password_Validate($this->getLength(), true);
            
            do {
                // Now put in random symbols
                // Get a random number between one and the length and insert that 
                // many numerical digits at random places in the string.
                // Note that the same position may be chosen more than once, 
                // due to the random function, so we may get less numbers than the number chosen
                $passwordString = $this->addSymbolsToString($passwordArray);
            } while (!$validator->isValid($passwordString, $this->getSymbols()));

        }
        
        return $passwordString ;
    }
}
?>