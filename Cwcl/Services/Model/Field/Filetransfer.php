<?php

class Cwcl_Services_Model_Field_Filetransfer extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * @var   array      stores all moved files for future reference
	 */
	static protected $_files = array();

	/**
	 * @var   string     temporary location to store files
	 */
	protected $_tmpDirectory;

	/**
	 * @var   array      $_FILES super-global information for this file
	 */
	protected $_file;

	/**
	 * @var   boolean    controls whether files are autoloaded into the field (default to FALSE)
	 */
	protected $_autoload = FALSE;

	/**
	 * Setup the field ready for use, pre initialise the file handling
	 * processes.
	 *
	 * @return  void
	 */
	public function __init()
	{
		// Run the rest of the initialisation phase
		parent::__init();

		if ($this->_autoload) {
			// Prepare the file
			$this->_initFile();
		}

	}

	/**
	 * Remove the temporary file as soon as this class shuts down
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		// Check for the existence of the file (minus any post filtering)
		if (file_exists($this->_value)) {
			unlink($this->_value);
		}
	}

	/**
	 * Set the autoload state of this field. Autoload will try and parse
	 * the file into field automatically
	 *
	 * @param   boolean  $value 
	 * @return  self
	 */
	public function setAutoload($value)
	{
		$this->_autoload = (bool) $value;
		return $this;
	}

	/**
	 * Gets the autoload state of this field
	 *
	 * @return  boolean
	 */
	public function getAutoload()
	{
		return $this->_autoload;
	}

	/**
	 * Sets the temporary directory to this field
	 *
	 * @param   string   $directory 
	 * @return  self
	 */
	public function setTmpDirectory($directory)
	{
		$this->_tmpDirectory = realpath($directory);
		return $this;
	}

	/**
	 * Gets the temporary directory set to this field
	 *
	 * @return  string
	 */
	public function getTmpDirectory()
	{
		return $this->_tmpDirectory;
	}

	/**
	 * Loads the File Transfer into this field manually
	 *
	 * @return  self
	 */
	public function loadFile()
	{
		$this->_initFile();
		return $this;
	}

	/**
	 * Creates a correctly formatted filename based on the temporary directory
	 *
	 * @param   string $filename 
	 * @return  string
	 */
	protected function _createFilename($filename)
	{
		return $this->_tmpDirectory.DIRECTORY_SEPARATOR.$filename;
	}

	/**
	 * Initialises the file to the field, checking for it's existence and moving
	 * it to a new location (for security, using it's original filename).
	 *
	 * @return  void
	 */
	protected function _initFile()
	{
		// Get the log
		$log = Zend_Registry::isRegistered('log') ? Zend_Registry::get('log') : new Zend_Log_Writer_Null;

		// Get the id
		$id = $this->getId();

		$log->log('Cwcl_Services_Model_Field_Filetransfer: Looking for File with ID of ;'.$id , Zend_Log::INFO);

		// If this file has already been parsed in this session
		if (isset(Cwcl_Services_Model_Field_FileTransfer::$_files[$id])) {
			$log->log("_FILES({$id}) has already been processed, skipping!", Zend_Log::INFO);
			$this->_file  = Cwcl_Services_Model_Field_FileTransfer::$_files[$id];
			$this->_value = $this->_file['tmp_name'];
			return;
		}
		// If there is no upload or the upload is corrupted
		else if ( ! isset($_FILES[$id]) or $_FILES[$id]['error'] !== UPLOAD_ERR_OK) {
			$log->log("_FILES({$id}) is not valid, aborting! Reason : ".$_FILES[$id]['error'], Zend_Log::INFO);
			// Get out of here, reset the value and file to NULL
			$this->_value = NULL;
			$this->_file  = NULL;
			return;
		}

		// Check for tmp directory or use the PHP default
		($this->_tmpDirectory === NULL) and $this->_tmpDirectory = ini_get('upload_tmp_dir');

		// If the tmpDirectory is still empty
		if ($this->_tmpDirectory === NULL) {
			// Huston we have a problem
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' unable to detect a temporary directory for uploaded files. Unsure the \'upload_tmp_dir\' setting within php.ini is set correctly!');
		}

		// Create a random folder
		$this->_tmpDirectory = realpath($this->_tmpDirectory).DIRECTORY_SEPARATOR.rand(0,99);

		$log->log("_FILES({$id}): Temporary dir set to; {$this->_tmpDirectory}", Zend_Log::INFO);

		// If the directory does not exist, create it
		if ( ! is_dir($this->_tmpDirectory)) {
			$log->log("_FILES({$id}): Creating temporary dir at; {$this->_tmpDirectory}", Zend_Log::INFO);
			if ( ! mkdir($this->_tmpDirectory, 0700, TRUE)) {
				throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' unable to create temporary directory : '.$this->_tmpDirectory.' check permissions or existing resources.');
			}
		}

		// Get the full path of the file
		$filename = $this->_createFilename($_FILES[$id]['name']);
		$log->log("_FILES({$id}): Created filename for file; {$filename}", Zend_Log::INFO);

		// Lets move the file to a new home restoring it's original name
		if (move_uploaded_file($_FILES[$id]['tmp_name'], $filename)) {
			$log->log("_FILES({$id}): Moved uploaded file; {$_FILES[$id]['tmp_name']} to; {$filename}", Zend_Log::INFO);
			// Tidy up the file upload globals and augment data
			$this->_file = $_FILES[$id];
			$this->_file['tmp_name'] = $filename;
			$this->setValue($filename);
			Cwcl_Services_Model_Field_FileTransfer::$_files[$id] = $this->_file;
			unset($_FILES[$id]);
			$log->log("_FILES({$id}): Removed file entry at; {$id}", Zend_Log::INFO);
		}
		else {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' unable to move uploaded file into temporary location : '.$this->_tmpDirectory);
		}

		// Return
		return;
	}

	/**
	 * Parses a value. Takes the input value
	 * and converts it to the Field type
	 *
	 * @param mixed $value 
	 * @return mixed
	 * @access protected
	 */
	protected function parseValue($value)
	{
		return (NULL === $value) ? NULL : (string) $value;
	}
}