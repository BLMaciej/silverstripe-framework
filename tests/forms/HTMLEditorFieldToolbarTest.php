<?php

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField_Toolbar;

class HTMLEditorFieldToolbarTest_Toolbar extends HTMLEditorField_Toolbar {
	public function viewfile_getLocalFileByID($id) {
		return parent::viewfile_getLocalFileByID($id);
	}

	public function viewfile_getRemoteFileByURL($fileUrl) {
		return parent::viewfile_getRemoteFileByURL($fileUrl);
	}
}

class HTMLEditorFieldToolbarTest extends SapphireTest {

	protected static $fixture_file = 'HTMLEditorFieldToolbarTest.yml';

	/**
	 * @return HTMLEditorFieldToolbarTest_Toolbar
	 */
	protected function getToolbar() {
		return new HTMLEditorFieldToolbarTest_Toolbar(null, '/');
	}

	public function setUp() {
		parent::setUp();

		HTMLEditorField_Toolbar::config()->update('fileurl_scheme_whitelist', array('http'));
		HTMLEditorField_Toolbar::config()->update('fileurl_domain_whitelist', array('example.com'));

		// Filesystem mock
		AssetStoreTest_SpyStore::activate(__CLASS__);

		// Load up files
		/** @var File $file1 */
		$file1 = $this->objFromFixture('SilverStripe\\Assets\\File', 'example_file');
		$file1->setFromString(str_repeat('x', 1000), $file1->Name);
		$file1->write();

		/** @var Image $image1 */
		$image1 = $this->objFromFixture('SilverStripe\\Assets\\Image', 'example_image');
		$image1->setFromLocalFile(
			__DIR__ . '/images/HTMLEditorFieldTest-example.jpg',
			'folder/subfolder/HTMLEditorFieldTest_example.jpg'
		);
		$image1->write();
	}

	public function testValidLocalReference() {
		/** @var File $exampleFile */
		$exampleFile = $this->objFromFixture('SilverStripe\\Assets\\File', 'example_file');
		$expectedUrl = $exampleFile->AbsoluteLink();
		HTMLEditorField_Toolbar::config()->update('fileurl_domain_whitelist', array(
			'example.com',
			strtolower(parse_url($expectedUrl, PHP_URL_HOST))
		));

		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL($exampleFile->AbsoluteLink());
		$this->assertEquals($expectedUrl, $url);
	}

	public function testValidScheme() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('http://example.com/test.pdf');
		$this->assertEquals($url, 'http://example.com/test.pdf');
	}

	/** @expectedException SilverStripe\Control\HTTPResponse_Exception */
	public function testInvalidScheme() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('nosuchscheme://example.com/test.pdf');
	}

	public function testValidDomain() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('http://example.com/test.pdf');
		$this->assertEquals($url, 'http://example.com/test.pdf');
	}

	/** @expectedException SilverStripe\Control\HTTPResponse_Exception */
	public function testInvalidDomain() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('http://evil.com/test.pdf');
	}

}
