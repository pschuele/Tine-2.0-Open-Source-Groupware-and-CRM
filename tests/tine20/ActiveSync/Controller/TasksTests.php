<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Controller_TasksTests::main');
}

/**
 * Test class for Tasks_Controller_Task
 * 
 * @package     ActiveSync
 */
class ActiveSync_Controller_TasksTests extends ActiveSync_TestCase
{
    /**
     * name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Tasks';
    
    protected $_controllerName = 'ActiveSync_Controller_Tasks';
    
    protected $_specialFolderName = 'tasks-root';
    
    protected $_class = ActiveSync_Controller::CLASS_TASKS;
    
    protected $_testXML = '';
    
/*    protected $_exampleXMLNotExisting = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>0</FilterType><Truncation>2</Truncation><Conflict>0</Conflict></Options><Commands><Add><ClientId>1</ClientId><ApplicationData><Contacts:FileAs>ads2f, asdfadsf</Contacts:FileAs><Contacts:FirstName>asdf </Contacts:FirstName><Contacts:LastName>asdfasdfaasd </Contacts:LastName><Contacts:MobilePhoneNumber>+4312341234124</Contacts:MobilePhoneNumber><Contacts:Body>&#13;
</Contacts:Body></ApplicationData></Add></Commands></Collection></Collections></Sync>';
    
    protected $_exampleXMLExisting = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>0</FilterType><Truncation>2</Truncation><Conflict>0</Conflict></Options><Commands><Add><ClientId>1</ClientId><ApplicationData><Contacts:FileAs>Kneschke, Lars</Contacts:FileAs><Contacts:FirstName>Lars</Contacts:FirstName><Contacts:LastName>Kneschke</Contacts:LastName></ApplicationData></Add></Commands></Collection></Collections></Sync>';
  */  
    protected $_testXMLInput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Tasks="uri:Tasks"><Collections><Collection><Class>Tasks</Class><SyncKey>17</SyncKey><CollectionId>tasks-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>8</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>2048</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>0</Conflict></Options><Commands><Change><ClientId>1</ClientId><ApplicationData><AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:Data>test beschreibung zeile 1&#13;
Zeile 2&#13;
Zeile 3</AirSyncBase:Data></AirSyncBase:Body><Tasks:Subject>Testaufgabe auf mfe</Tasks:Subject><Tasks:Importance>1</Tasks:Importance><Tasks:UtcDueDate>2010-11-28T22:59:00.000Z</Tasks:UtcDueDate><Tasks:DueDate>2010-11-28T23:59:00.000Z</Tasks:DueDate><Tasks:Complete>0</Tasks:Complete><Tasks:Sensitivity>0</Tasks:Sensitivity></ApplicationData></Change></Commands></Collection></Collections></Sync>';
    
    protected $_testXMLOutput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Tasks="uri:Tasks"><Collections><Collection><Class>Tasks</Class><SyncKey>17</SyncKey><CollectionId>tasks-root</CollectionId><Commands><Change><ClientId>1</ClientId><ApplicationData/></Change></Commands></Collection></Collections></Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller Tasks Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        // skip this test => iPhone support no tasks synchronisation
    }
    
    /**
     * test xml generation for sync to client
     */
    public function testAppendXml()
    {
        $dom     = $this->_getOutputDOMDocument();
        $appData = $dom->getElementsByTagNameNS('uri:AirSync', 'ApplicationData')->item(0);

        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM)); 
        
        $task = Tasks_TestCase::getTestRecord();
        $task->description = "Hello\r\nTask\nLars";
        $task = Tasks_Controller_Task::getInstance()->create($task);
        
        $this->objects['tasks']['appendxml'] = $task;
        
        $controller->appendXML($appData, null, $task, array());
        
        #$dom->formatOutput = true; echo $dom->saveXML(); $dom->formatOutput = false;
        
        // namespace === uri:Calendar
        $dueDate = $task->due->format("Y-m-d\TH:i:s") . '.000Z';
        $this->assertEquals($dueDate, @$dom->getElementsByTagNameNS('uri:Tasks', 'DueDate')->item(0)->nodeValue, $dom->saveXML());
        $this->assertEquals("Hello\r\nTask\r\nLars", @$dom->getElementsByTagNameNS('uri:AirSyncBase', 'Data')->item(0)->nodeValue, $dom->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($dom);
        
        #rewind($outputStream);
        #fpassthru($outputStream);
    }
    
    /**
     * test convert from XML to Tine 2.0 model
     */
    public function testConvertToTine20Model()
    {
        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));   
        
        $task = $controller->toTineModel($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        #var_dump($task->toArray());
        
        $this->assertEquals('Testaufgabe auf mfe', $task->summary);
        $this->assertEquals(0,                     $task->percent);
        $this->assertEquals("test beschreibung zeile 1\r\nZeile 2\r\nZeile 3", $task->description);
    }
    
    /**
     * test search tasks
     */
    public function testSearch()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));

        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $record = $controller->add($this->_getContainerWithSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $this->objects['tasks'][] = $record;
        
        $task = $controller->search($this->_specialFolderName, $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        #var_dump($task->toArray());
        
        $this->assertEquals(1                    , count($task));
        $this->assertEquals('Testaufgabe auf mfe', $task[0]->summary);
    }
    
    protected function _validateAddEntryToBackend(Tinebase_Record_Abstract $_record)
    {
        $this->objects['tasks'][] = $_record;
        
        #var_dump($_record->toArray());
        
        $this->assertEquals('Testaufgabe auf mfe', $_record->summary);
        $this->assertEquals(0,                     $_record->percent);
        $this->assertEquals("test beschreibung zeile 1\r\nZeile 2\r\nZeile 3", $_record->description);
    }
    
    protected function _validateGetServerEntries(Tinebase_Record_Abstract $_record)
    {
        $this->objects['tasks'][] = $_record;
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        $records = $controller->getServerEntries($this->_specialFolderName, ActiveSync_Controller_Tasks::FILTER_NOTHING);
        
        $this->assertContains($_record->getId(), $records);
        #$this->assertNotContains($this->objects['unSyncableContact']->getId(), $entries);
    }
    
    
    
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function _testGetServerEntries()
    {
    	$controller = new ActiveSync_Controller_Contacts($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
    	
    	$entries = $controller->getServerEntries('addressbook-root', null);
    	
    	$this->assertContains($this->objects['contact']->getId(), $entries);
    	$this->assertNotContains($this->objects['unSyncableContact']->getId(), $entries);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function _testSyncableFolder()
    {
        $controller = new ActiveSync_Controller_Contacts($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        $entries = $controller->getServerEntries($this->objects['containerWithSyncGrant']->getId(), null);
        
        $this->assertContains($this->objects['contact']->getId(), $entries);
        $this->assertNotContains($this->objects['unSyncableContact']->getId(), $entries);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function _testUnSyncableFolder()
    {
        $controller = new ActiveSync_Controller_Contacts($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        $entries = $controller->getServerEntries($this->objects['containerWithoutSyncGrant']->getId(), null);
        
        $this->assertNotContains($this->objects['contact']->getId(), $entries);
        $this->assertNotContains($this->objects['unSyncableContact']->getId(), $entries);
    }
    
    /**
     * test getChanged entries
     */
    public function _testGetChanged()
    {
        $controller = new ActiveSync_Controller_Contacts($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        Addressbook_Controller_Contact::getInstance()->update($this->objects['contact']);
        Addressbook_Controller_Contact::getInstance()->update($this->objects['unSyncableContact']);
        
        $entries = $controller->getChanged('addressbook-root', Tinebase_DateTime::now()->subMinute(1));
        #var_dump($entries);
        $this->assertContains($this->objects['contact']->getId(), $entries);
        $this->assertNotContains($this->objects['unSyncableContact']->getId(), $entries);
    }
    
    /**
     * test search contacts
     * 
     */
    public function _testSearch()
    {
        $controller = new ActiveSync_Controller_Contacts($this->objects['devicePalm'], new Tinebase_DateTime(null, null, 'de_DE'));

        // search for non existing contact
        $xml = new SimpleXMLElement($this->_exampleXMLNotExisting);
        $existing = $controller->search('addressbook-root', $xml->Collections->Collection->Commands->Add->ApplicationData);
        
        $this->assertEquals(count($existing), 0);
        
        // search for existing contact
        $xml = new SimpleXMLElement($this->_exampleXMLExisting);
        $existing = $controller->search('addressbook-root', $xml->Collections->Collection->Commands->Add->ApplicationData);
        
        $this->assertEquals(count($existing), 1);
    }
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller_TasksTests::main') {
    ActiveSync_Controller_TasksTests::main();
}
