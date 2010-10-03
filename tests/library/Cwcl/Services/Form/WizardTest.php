<?php

class Cwcl_Services_Form_Wizard_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->_subject = new Cwcl_Services_Form_Wizard;
		$this->_page = $this->getMock('Cwcl_Services_Form_Wizard_Page', array(), array(), '', false);
	}
	
	public function tearDown()
	{
		
	}
	
	public function testConstructorAndAccessors()
	{
		$page = $this->_page;
		$dependencies = array(
			'name' => 'Bob',
			'pages' => array($page),
			'page' => 0,
		);
		
		$subject = new Cwcl_Services_Form_Wizard($dependencies);
		
		$this->assertEquals('Bob', $subject->getName());
		$this->assertEquals(0, $subject->getCurrentPage());
		$this->assertEquals(array($page), $subject->getPages());
	}
	
	public function testGetPage()
	{
		$page = $this->_page;
		$this->_subject->addPage($page);
		
		$this->assertEquals($page, $this->_subject->getPage(0));
	}
	
	public function testGetPageNonExistent()
	{
		$this->assertNull($this->_subject->getPage(12));
	}
	
	public function testSetCurrentPage()
	{
		$this->_subject->setCurrentPage(1);
		$this->assertEquals(1, $this->_subject->getCurrentPage());
	}
	
	public function testNextPage()
	{
		$page = $this->_page;
		$page2 = $this->getMock('Cwcl_Services_Form_Wizard_Page', array(), array(), '', false);
		$this->_subject->setPages(array($page, $page2));
		
		$this->assertEquals(1, $this->_subject->nextPage()->getCurrentPage());
	}
	
	public function testNextPageLastPage()
	{
		$page = $this->_page;
		$this->_subject->setPages(array($page));
		
		$this->assertEquals(0, $this->_subject->nextPage()->getCurrentPage());
	}
	
	public function testPreviousPage()
	{
		$page = $this->_page;
		$page2 = $this->getMock('Cwcl_Services_Form_Wizard_Page', array(), array(), '', false);
		$this->_subject->setPages(array($page, $page2));
		$this->_subject->setCurrentPage(1);
		
		$this->assertEquals(0, $this->_subject->previousPage()->getCurrentPage());
	}
	
	public function testPreviousPageFirstPage()
	{
		$page = $this->_page;
		$page2 = $this->getMock('Cwcl_Services_Form_Wizard_Page', array(), array(), '', false);
		$this->_subject->setPages(array($page, $page2));
		
		$this->assertEquals(1, $this->_subject->previousPage()->getCurrentPage());
	}
	
	public function testSetPageNonExistent()
	{
		try {
			$this->_subject->setPage();
		} catch(Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testGetBreadcrumb()
	{
		$name = 'bob';
		$title = "Bob's page";
		$description = 'A page belonging to Bob';
		$uri = 'www.foo.bar/bob';
		$breadcrumb = array(
			'name' => $name,
			'title' => $title,
			'description' => $description,
			'currentPage' => true,
			'uri' => $uri
		);
		
		$this->_page->expects($this->once())->method('getName')->will($this->returnValue($name));
		$this->_page->expects($this->once())->method('getTitle')->will($this->returnValue($title));
		$this->_page->expects($this->once())->method('getDescription')->will($this->returnValue($description));
		$this->_page->expects($this->once())->method('getUri')->will($this->returnValue($uri));
		
		$this->_subject->setPages(array($this->_page));
		
		$res = $this->_subject->getBreadcrumb();
		
		$this->assertEquals(array($breadcrumb), $res);
	}
	
	public function testLoadCurrentPage()
	{
		$this->_subject->addPage($this->_page);
		
		$res = $this->_subject->loadCurrentPage();
		
		$this->assertEquals($this->_page, $res);
	}
	
	public function testIsValidValidPage()
	{
		$this->_page->expects($this->once())->method('isValid')->will($this->returnValue(true));
		$this->_subject->addPage($this->_page);
		
		$this->assertTrue($this->_subject->isValid(0));
	}
	
	public function testIsValidInvalidPage()
	{
		$this->_page->expects($this->once())->method('isValid')->will($this->returnValue(false));
		$this->_subject->addPage($this->_page);
		
		$this->assertFalse($this->_subject->isValid(0));
	}
	
	public function testIsValidValidForm()
	{		
		$this->_page->expects($this->once())->method('isValid')->will($this->returnValue(true));
		$newPage = $this->getMock('Cwcl_Services_Form_Wizard_Page', array(), array(), '', false);
		$newPage->expects($this->once())->method('isValid')->will($this->returnValue(true));
		$this->_subject->setPages(array($this->_page, $newPage));

		$this->assertTrue($this->_subject->isValid());
	}
	
	public function testClearPages()
	{
		$this->_subject->addPage($this->_page);
		$this->_subject->clearPages();
		
		$this->assertEquals(array(), $this->_subject->getPages());
	}
	
	public function testRemovePageNonExistentPage()
	{
		// shouldn't throw an error
		try {
			$this->_subject->removePage(13);
		} catch (Exception $e) {
			$this->fail('Should fail silently');
		}
		
		return;
		
	}
	
	public function testRemovePage()
	{
		$this->_subject->addPage($this->_page);
		$this->_subject->removePage(0);
		
		$this->assertNull($this->_subject->getPage(0));
	}
	
	public function testSerialize()
	{
		$serial = $this->_subject->serialize();
		$newForm = new Cwcl_Services_Form_Wizard;
		$newForm->unserialize($serial);
		
		$this->assertEquals($this->_subject, $newForm);
	}
}