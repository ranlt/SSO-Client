<?php
/**
 * Response payload template
 *
 * @package Cwcl Services Response
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Response_Payload implements Serializable
{
	/**
	 * Standard factory method for creating new
	 * payloads
	 *
	 * @param string $type [Optional]
	 * @param array $properties [Optional]
	 * @return Cwcl_Services_Response_Payload
	 * @access public
	 * @static
	 */
	static public function factory($type = NULL, array $properties = NULL)
	{
		if (NULL === $type) {
			return new Cwcl_Services_Response_Payload($properties);
		}

		$class = 'Cwcl_Services_Response_Payload_'.ucfirst($type);
		return new $class($properties);
	}

	/**
	 * The actual payload of data
	 *
	 * @var mixed
	 */
	protected $_payload;

	/**
	 * Metadata to go with the payload
	 *
	 * @var array
	 */
	protected $_metadata;

	/**
	 * The content type of the data
	 *
	 * @var string
	 */
	protected $_contentType;

	/**
	 * Creates a new response payload
	 *
	 * @param array $properties [Optional]
	 * @access public
	 */
	public function __construct(array $properties = NULL)
	{
		if (is_array($properties)) {
			foreach ($properties as $key => $value) {
				$method = 'set'.ucfirst($key);
				$this->$method($value);
			}
		}
	}

	/**
	 * Set the content type for this payload
	 *
	 * @param string $contentType 
	 * @return self
	 * @access public
	 */
	public function setContentType($contentType)
	{
		$this->_contentType = (string) $contentType;
		return $this;
	}

	/**
	 * Get the content type for this payload
	 *
	 * @return string
	 * @access public
	 */
	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * Set the payload to this model
	 *
	 * @param scalar|array|object $payload 
	 * @return self
	 * @access public
	 */
	public function setPayload($payload)
	{
		$this->_payload = is_object($payload) ? (array) $payload : $payload;
		return $this;
	}

	/**
	 * Get the payload from this payload object
	 *
	 * @return mixed
	 * @access public
	 */
	public function getPayload()
	{
		return $this->_payload;
	}

	/**
	 * Set the metadata to this payload
	 *
	 * @param array|object $metadata 
	 * @return self
	 * @access public
	 */
	public function setMetadata($metadata)
	{
		$this->_metadata = (array) $metadata;
		return $this;
	}

	/**
	 * Get the metadata from this payload
	 *
	 * @return array
	 * @access public
	 */
	public function getMetadata()
	{
		return $this->_metadata;
	}

	/**
	 * Generates a string representation of the payload
	 * for transmission
	 *
	 * @return string
	 * @access public
	 */
	public function __toString()
	{
		return $this->serialize();
	}

	/**
	 * Serialise the payload into string form
	 *
	 * @return string
	 * @access public
	 */
	public function serialize()
	{
		return serialize(array(
			'_contentType'  => $this->getContentType(),
			'_payload'      => $this->getPayload(),
			'_metadata'     => $this->getMetadata(),
		));
	}

	/**
	 * Unserialise the payload from a string
	 *
	 * @param string $serialised 
	 * @return void
	 * @access public
	 */
	public function unserialize($serialized)
	{
		$unserialized = unserialize($serialized);

		foreach ($unserialized as $key => $value) {
			$this->$key = $value;
		}
	}
}