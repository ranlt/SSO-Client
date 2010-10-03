<?php

require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_Base extends PHPUnit_Framework_TestCase {

	public $client;

	public $original_ns;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array
	(
		'models'     => array
		(
			'Sso_Model_Base' => array
			(
				'model'  => 'Base',
				'id'     => null,
				'loaded' => FALSE,
			),
			'Sso_Model_User' => array
			(
				'model'  => 'User',
				'id'     => null,
				'loaded' => FALSE,
			),
			'Sso_Model_User' => array
			(
				'model'  => 'User',
				'id'     => 'admin@cw',
				'loaded' => TRUE,
			)
		),
		'namespace' => 'testNS',
		'stringUri' => 'http://sso.dev.alterededge.co.uk:81/',
		'options' => array(
			'uri' => 'http://sso.dev.alterededge.co.uk:81/',
			'resultType' => Sso_Request::RESULT_OBJECT,
			'classBase' => 'Sso_Test_'
		),
		'credentials' => array('username' => 'admin@cw', 'password' => 'password'),
		'users'    => array
		(
			'admin@cw',
			'admin@cw',
			'nonexistent@nowhere',
		),
		'createUser' => array
		(
			'username'  => 'test@client',
			'organisation' => 'CW',
			'fullName' => 'Test Case',
			'location' => 'Nowhere',
			'phoneNumber' => '020333444555',
			'password' => 'something'
		),
		'ssoError' => array('errorCode' => 999, 'errorMessage' => 'test error'),
	);

	public function setup()
	{
		$this->config = Zend_Registry::get('ssoConfig');
		$this->fixtures['options']['uri'] = $this->config->url;
		$this->client = Sso_Client::factory(array('url' => $this->config->url));
		$this->login($this->fixtures['credentials']);

		// Get the standard NS
		$this->original_ns = Sso_Model_Base::getNamespace();
	}

	public function tearDown()
	{
		// logout
		$session = Zend_Registry::get('session');
		unset($session->cookies);
	}

	public function login($username, $password = null)
	{
		if (is_array($username)) {
			$password = $username['password'];
			$username = $username['username'];
		}
		
		return $this->client->authenticate($username, $password);
	}

	/**
	 * Tests the factory creates the correct
	 * model type
	 *
	 * @return void
	 * @author Sam Clark
	 */
	public function testFactoryMethod()
	{
		foreach ($this->fixtures['models'] as $class => $params) {
			$model = Sso_Model_Base::factory($params['model'], $params['id']);

			// Test the type
			$this->assertType($class, $model);

			// Test the id
			$this->assertEquals($params['id'], $model->id);

			// Test the loaded state
			$this->assertEquals($params['loaded'], $model->isLoaded());

			// Remove the model
			unset($model);
		}
	}

	public function testNamespace()
	{
		// Set the new namespace
		Sso_Model_Base::setNamespace($this->fixtures['namespace']);
		$this->assertEquals($this->fixtures['namespace'], Sso_Model_Base::getNamespace());

		// Test empty string
		Sso_Model_Base::setNamespace('');
		$this->assertEquals(FALSE, Sso_Model_Base::getNamespace());

		// Return the NS back to original setting
		Sso_Model_Base::setNamespace($this->original_ns);
	}

	public function testGetModelName()
	{
		// Test name resolution with ns
		Sso_Model_Base::setNamespace($this->fixtures['namespace']);
		$this->assertEquals($this->fixtures['namespace'].'_Model_Base', Sso_Model_Base::getModelName('base'));

		// Test model resolution without ns
		Sso_Model_Base::setNamespace(FALSE);
		$this->assertEquals('Model_Base', Sso_Model_Base::getModelName('base'));

		// Return the NS back to original setting
		Sso_Model_Base::setNamespace($this->original_ns);
	}

	public function testFind()
	{
		// Setup two methods for finding model
		$model1 = new Sso_Model_User($this->fixtures['users'][0]);
		$model2 = new Sso_Model_User;
		$model2->find($this->fixtures['users'][0]);

		// Test model 1 loaded properly
		$this->assertEquals(TRUE, $model1->loaded);
		$this->assertEquals($this->fixtures['users'][0], $model1->id);

		// Test model 2 loaded properly
		$this->assertEquals(TRUE, $model2->loaded);
		$this->assertEquals($this->fixtures['users'][0], $model2->id);

	}

	public function testSetAndSave()
	{
		// Setup a model for saving
		$model = new Sso_Model_User($this->fixtures['users'][0]);
		$this->assertEquals(TRUE, $model->isLoaded());

		$model->location = $location = md5(time());

		// Test if the value was set and model state changed
		$this->assertEquals(TRUE, in_array('location', $model->changed));
		$this->assertEquals(FALSE, $model->saved);

		// Save the model
		$model->save();

		// Test save state
		$this->assertEquals(TRUE, $model->isSaved());
		$this->assertEquals(TRUE, $model->isLoaded());
		$this->assertEquals($location, $model->location);
	}

	public function testFindAll()
	{
		// Get a result set
		$result = Sso_Model_Base::factory('user')->findAll();

		// Test the result
		$this->assertType('Sso_Model_Iterator', $result);

		// Test adding params (limit/offset)
		$result = Sso_Model_Base::factory('user')->limit(1,0)->findAll();

		// Check that the result is an iterator and that there is only one result
		$this->assertType('Sso_Model_Iterator', $result);
		$this->assertEquals(1, $result->count());
	}

	/**
	 * @expectedException Sso_Client_Exception
	 */
	public function testFindChildren()
	{
		// Setup an organisation
		$organisation = Sso_Model_Base::factory('organisation', 'CW');
		// Run the method
		$results = $organisation->findChildren('user');

		// Test the result
		$this->assertType('Sso_Model_Iterator', $results);

		// Test the contents
		$this->assertType('Sso_Model_User', $results->current());

		// Test a false assumption and catch exception
		$results = $organisation->findChildren('apples');
	}

//	public function testFindParent()

	public function testMultiCurl()
	{
		// Setup four models
		$model1 = new Sso_Model_User($this->fixtures['users'][0], TRUE);
		$model2 = new Sso_Model_User($this->fixtures['users'][1], TRUE);
		$model3 = Sso_Model_Base::factory('user')->setMultiCurl(TRUE)->find($this->fixtures['users'][0]);
		$model4 = Sso_Model_Base::factory('user');
		$model5 = new Sso_Model_User($this->fixtures['users'][1]);

		// Ensure they didn't load
		$this->assertEquals(FALSE, $model1->isLoaded());
		$this->assertEquals(FALSE, $model2->isLoaded());
		$this->assertEquals(FALSE, $model3->isLoaded());
		$this->assertEquals(TRUE, $model5->isLoaded());

		// Add some data to the user
		$model5->location = 'Testing123';
		$model5->setMultiCurl(TRUE);

		// Test the model does not save
		$model5->save();
		$this->assertEquals(FALSE, $model5->isSaved());

		// Test attach returns self
		$this->assertType('Sso_Model_Base', Sso_Model_Base::factory()->attach($model1));

		// Add them to a multicurl client and execute
		$this->assertEquals(TRUE, Sso_Model_Base::factory()->attach($model2)->attach($model3)->attach($model4)->attach($model5)->exec());

		// Test that exec() won't run when the pool is empty
		$this->assertEquals(FALSE, Sso_Model_Base::factory()->exec());
	}

	public function testCreate()
	{
		// Create a new empty model
		$model = new Sso_Model_User;

		// Set values
		$model->setValues($this->fixtures['createUser']);
		
		// Save the model
		$model->save();		

		// Test the model was saved
		$this->assertEquals(TRUE, $model->isSaved());
		$this->assertEquals(TRUE, $model->isLoaded());

		// Check it really really does exist
		$testsave = new Sso_Model_User($this->fixtures['createUser']['username']);
		$this->assertEquals(TRUE, $testsave->isLoaded());
	}

	public function testDelete()
	{
		// Load the model
		$model = new Sso_Model_User($this->fixtures['createUser']['username']);

		// Test the model loaded
		$this->assertEquals(TRUE, $model->isLoaded());

		// Delete the model
		$model->delete();

		// Ensure the model is deleted internally
		$this->assertEquals(FALSE, $model->isLoaded());

		// Ensure the model is loaded at API
		$this->assertEquals(FALSE, Sso_Model_Base::factory('user', $this->fixtures['createUser']['username'])->isLoaded());

	}

	public function testGetValues()
	{
		// Get the values
		$values = Sso_Model_Base::factory('user', $this->fixtures['users'][0])->getValues();

		$this->assertType('array', $values);
		$this->assertGreaterThan(0, count($values));
	}

	public function testSleepWakeup()
	{
		// Load a model
		$model = Sso_Model_Base::factory('user', $this->fixtures['users'][0]);

		// Serialise the model
		$sleeping = serialize($model);

		$this->assertType('string', $sleeping);

		// Reinitialise from sleep
		$woken = unserialize($sleeping);

		$this->assertType('Sso_Model_User', $woken);
	}

	public function testReset()
	{
		// Test the model
		$model = new Sso_Model_User($this->fixtures['users'][0]);

		// Test the model loaded
		$this->assertEquals(TRUE, $model->loaded);

		// Reset the model
		$model->reset();

		// Test the model is empty
		$this->assertEquals(FALSE, $model->loaded);
		$this->assertEquals(NULL, $model->id);
	}

	public function testIsset()
	{
		// Load a user
		$user = Sso_Model_Base::factory('user', $this->fixtures['users'][0]);

		// Test that users id is set
		$this->assertEquals(TRUE, isset($user->id));

		// Test that a bogus property is not set
		$this->assertEquals(FALSE, isset($user->foobar));
	}

	public function testUnset()
	{
		$user = Sso_Model_Base::factory('user', $this->fixtures['users'][0]);
		unset($user->username);

		$this->assertEquals(NULL, $user->username);
	}

	public function testClient()
	{
		// Load a model
		$model = new Sso_Model_Base;

		// Test the client is there (no exception thrown)
		try
		{
			$model->checkClient();
		}
		catch (Sso_Model_Exception $e)
		{
			$this->fail('Sso_Model_Exception should not be thrown');
		}
		$this->assertEquals(NULL, $model->checkClient());

		// Unset the client
		$model->setClient(NULL);

		try
		{
			// Test there is no client, will throw exception
			$model->checkClient();
		}
		catch (Sso_Model_Exception $e)
		{
			return;
		}

		$this->fail('The expected exception Sso_Model_Exception was not thrown');
	}
}