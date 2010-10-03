<?php

class Cwcl_Services_Model_Decorator_Form_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		// TODO Set up model methods
		$this->model = $this->getMock('Cwcl_Services_Model_Abstract', array('getFieldsAsContainer'), array(), '', false);
		$this->decorator = new Cwcl_Services_Model_Decorator_Form;
	}
	
	public function testCreateModelFormFields()
	{
		$fields = array(
			// 'iterator',
			'bool',
			// 'set',
			// 'enum',
			'auto',
			'string',
			'float',
			'integer'
		);
		$form = array();
		$mocks = array();
		
		$formElMap = array(
			'bool' => 'Zend_Form_Element_MultiCheckbox',
			'set' => 'Zend_Form_Element_MultiSelect',
			'enum' => 'Zend_Form_Element_MultiSelect',
			'auto' => 'Zend_Form_Element_Hidden',
			'string' => 'Zend_Form_Element_Text',
			'float' => 'Zend_Form_Element_Text',
			'integer' => 'Zend_Form_Element_Text'
		);
		
		foreach($fields as $field) {
			// create a mock object
			$class = 'Cwcl_Services_Model_Field_' . ucfirst($field);
			// need to create instances of actual fields, as the class name is used in code
			$mock = $this->getMock($class, array('getId', 'getDescription', 'getValue'), array(), '', false);
			$mock->expects($this->once())->method('getId')->will($this->returnValue($field));
			
			if ($field == 'bool') {
				$mock->expects($this->once())->method('getValue')->will($this->returnValue(TRUE));
			} else {
				$mock->expects($this->once())->method('getValue')->will($this->returnValue($field . '_val'));
			}
			
			if ($field == 'set' || $field == 'enum') {
				$mock->expects($this->once())->method('getEnumeration')->will($this->returnValue(array('something', 'something else')));
			}
			
			$mock->expects($this->once())->method('getDescription')->will($this->returnValue($field . '_desc'));
			$mocks[] = $mock;
		
			// create a form element
			$elType = $formElMap[$field];
			$options = array(
				'name' => $field,
				'id' => $field,
				'value' => $field . '_val',
				'description' => $field . '_desc',
				'label' => $field
			);
			
			// add additional options
			if ($field == 'string') {
				$options['maxlength'] = '';
			}
			
			if ($field == 'auto') {
				$options['readonly'] = 'readonly';
				unset($options['label']);
			}
			
			if ($field == 'bool') {
				$options['options'] = array(
					'yes' => 'Yes',
					'no' => 'No'
				);
				
				$options['value'] = 'yes';
			}
			
			if ($field == 'set' || $field == 'enum') {
				$options['options'] = array('something', 'something else');
			}
			
			$formEl = new $elType($options);
			$form[] = $formEl;
		}
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue($mocks));
		$this->decorator->setModel($this->model);
		$res = $this->decorator->createModelFormFields();
		
		$this->assertEquals($form, $res);
	}
	
	public function testCreateModelFormFieldsSpecifiedFields()
	{
		$stringFld = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getDescription', 'getValue'), array(), '', false);
		$stringFld->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$stringFld->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$stringFld->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));

		$intFld = $this->getMock('Cwcl_Services_Model_Field_Integer', array('getId', 'getDescription', 'getValue'), array(), '', false);
		$intFld->expects($this->never())->method('getId');
		$intFld->expects($this->never())->method('getValue');
		$intFld->expects($this->never())->method('getDescription');
		
		$storage = array('name' => $stringFld, 'age' => $intFld);
		
		$form = array(
			'name' => new Zend_Form_Element_Text(array(
				'id' => 'name',
				'name' => 'name',
				'value' => 'James',
				'description' => 'My name',
				'label' => 'name',
				'maxlength' => ''
			))
		);
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue($storage));
		$this->decorator->setModel($this->model);
		$this->decorator->setSelectSpecificFields(array('name'));
		$res = $this->decorator->createModelFormFields();
		
		$this->assertEquals($form, $res);
	}
	
	public function testCreateModelFormFieldsNoModel()
	{
		try {
			$this->decorator->createModelFormFields();
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testAddFeildDecoratorNoFields()
	{
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array()));
		$this->decorator->setModel($this->model);
		$this->decorator->createFields();
		
		try {
			$this->decorator->addFieldDecorator('name', 'li');
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testAddFieldDecoratorNoModel()
	{
		try {
			$this->decorator->addFieldDecorator('name', 'li');
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testAddFieldDecorator()
	{
		$field = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getValue', 'getDescription'), array(), '', false);
		$field->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$field->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$field->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array('name' => $field)));
		
		$decorator = $this->getMock('Zend_Form_Decorator_HtmlTag');
		
		$this->decorator->setModel($this->model);
		$this->decorator->createFields();
		$this->decorator->addFieldDecorator('name', $decorator);
		$res = $this->decorator->getFieldDecorators('name');
		
		$this->assertSame($decorator, array_pop($res));
	}
	
	public function testRemoveFieldDecorator()
	{
		$field = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getValue', 'getDescription'), array(), '', false);
		$field->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$field->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$field->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array('name' => $field)));
		
		$this->decorator->setModel($this->model);
		$this->decorator->createFields();
		// remove standard decorator
		$this->decorator->removeFieldDecorator('name', 'Zend_Form_Decorator_HtmlTag');
		$res = $this->decorator->getFieldDecorators('name');
		
		$this->assertFalse(in_array('Zend_Form_Decorator_HtmlTag', $res));
	}
	
	public function testSetFieldDecorators()
	{
		$field = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getValue', 'getDescription'), array(), '', false);
		$field->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$field->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$field->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array('name' => $field)));
		
		$decorators = array(
			'Mr_Decorator' => $this->getMock('Zend_Form_Decorator_HtmlTag', array(), array(), 'Mr_Decorator')
		);
		
		$this->decorator->setModel($this->model);
		$this->decorator->createFields();
		$this->decorator->setFieldDecorators('name', $decorators);
		$res = $this->decorator->getFieldDecorators('name');
		
		// test that the decorators only contain our mock
		$this->assertEquals($decorators, $res);
	}
	
	public function testSetAttribNoModel()
	{
		try {
			$this->decorator->setAttrib('name', 'maxlength', '8');
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testSetAttrib()
	{
		$field = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getValue', 'getDescription'), array(), '', false);
		$field->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$field->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$field->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array('name' => $field)));
		
		$this->decorator->setModel($this->model);
		$this->decorator->createFields();
		$this->decorator->setAttrib('name', 'maxlength', '8');
		
		$this->assertEquals(8, $this->decorator->getAttrib('name', 'maxlength'));
	}
	
	public function testSetAttribs()
	{
		$field = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getValue', 'getDescription'), array(), '', false);
		$field->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$field->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$field->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));
		
		$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array('name' => $field)));
		
		$attribs = array(
			'maxlength' => 8
		);
		
		$this->decorator->setModel($this->model);
		$this->decorator->createFields();
		$this->decorator->setAttribs('name', $attribs);
		
		$this->assertTrue(array_key_exists('maxlength', $this->decorator->getAttribs('name')));
		$this->assertEquals(8, $this->decorator->getAttrib('name', 'maxlength'));
	}
	
	/**
	 * Custom labels will be applied to any field that has a value set for the corresponding key in the decorator's label array (TODO Re-write that)
	 * Any field that doesn't have a custom label will have its id applied as the label
	 */
	public function testSetLabels()
	{
		$stringFld = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getDescription', 'getValue'), array(), '', false);
		$stringFld->expects($this->once())->method('getId')->will($this->returnValue('name'));
		$stringFld->expects($this->once())->method('getValue')->will($this->returnValue('James'));
		$stringFld->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));

		$intFld = $this->getMock('Cwcl_Services_Model_Field_Integer', array('getId', 'getDescription', 'getValue'), array(), '', false);
		$intFld->expects($this->once())->method('getId')->will($this->returnValue('age'));
		$intFld->expects($this->once())->method('getValue')->will($this->returnValue('19'));
		$intFld->expects($this->once())->method('getDescription')->will($this->returnValue('Your age in years'));
		
		$password = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getDescription', 'getValue'), array(), '', false);
		$password->expects($this->once())->method('getId')->will($this->returnValue('password'));
		$password->expects($this->once())->method('getValue')->will($this->returnValue('*****'));
		$password->expects($this->once())->method('getDescription')->will($this->returnValue('Your password'));
		
		$storage = array(
			'name' => $stringFld,
			'age' => $intFld,
			'password' => $password
		);
		
		$this->model->expects($this->exactly(2))->method('getFieldsAsContainer')->will($this->returnValue($storage));
		
		$nameLbl = 'The term by which people address you';
		$ageLbl = 'How many years it has been since you were born';
		$labels = array(
			'name' => $nameLbl,
			'age' => $ageLbl
		);
		
		$this->decorator->setModel($this->model);
		$this->decorator->setLabels($labels);
		
		$this->assertEquals($labels, $this->decorator->getLabels());
		
		$res = $this->decorator->createModelFormFields();
		
		$this->assertEquals($nameLbl, $res['name']->getLabel());
		$this->assertEquals($ageLbl, $res['age']->getLabel());
		$this->assertEquals('password', $res['password']->getLabel());
	}
	
	public function testRenderNamespace()
	{
		$namespace = 'cw';
		$sep = '_';
		$name = 'james';
		
		$this->decorator->setNamespace(array($namespace));
		$this->decorator->setNamespaceSeparator($sep);
		$res = $this->decorator->renderNamespace($name);
		
		$this->assertEquals('cw_james', $res);
	}
	
	// public function testToString()
	// {
	// 	$name = $this->getMock('Cwcl_Services_Model_Field_String', array('getId', 'getDescription', 'getValue', 'getType', 'getLength', 'validate'), array(), '', false);
	// 	$name->expects($this->once())->method('getId')->will($this->returnValue('name'));
	// 	$name->expects($this->once())->method('getValue')->will($this->returnValue('James'));
	// 	$name->expects($this->once())->method('getDescription')->will($this->returnValue('My name'));
	// 	$name->expects($this->once())->method('getType')->will($this->returnValue('string'));
	// 	$name->expects($this->once())->method('getLength')->will($this->returnValue('8'));
	// 	$name->expects($this->once())->method('validate')->will($this->returnValue(true));
	// 	
	// 	$this->model->expects($this->once())->method('getFieldsAsContainer')->will($this->returnValue(array('name' => $name)));
	// 	
	// 	$this->decorator->setModel($this->model);
	// 	
	// 	$res = $this->decorator->render();
	// 	
	// 	var_dump($res);
	// 	
	// 	$this->assertEquals($html, $res);
	// }
}
