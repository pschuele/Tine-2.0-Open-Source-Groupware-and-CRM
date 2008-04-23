<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to handle setup of Tine 2.0
 *
 * @package     Setup
 */
class Setup_Controller
{
    private $_backend;
    private $_config;
    
    /**
     * get set to true, if Tinebase got installed (not updated!!!)
     *
     * @var bool
     */
	private $_doInitialLoad = true;
	
    /**
     * the contructor
     *
     */
    public function __construct()
    {
        $this->_config = Zend_Registry::get('configFile');

        $this->setupLogger();
        $this->setupDatabaseConnection();
        
        #switch ($this->_config->database->database) {
        #    case('mysql'):
                $this->_backend = new Setup_Backend_Mysql();
        #        break;
        #    
        #    default:
        #        echo "you have to define a dbms = yourdbms (like mysql) in your config.ini file";
        #}		
    }

    /**
     * initializes the logger
     *
     */
    protected function setupLogger()
    {
        $logger = new Zend_Log();

        if(isset($this->_config->logger)) {
            $loggerConfig = $this->_config->logger;

            $filename = $loggerConfig->filename;
            $priority = (int)$loggerConfig->priority;

            $writer = new Zend_Log_Writer_Stream($filename);
            $logger->addWriter($writer);

            $filter = new Zend_Log_Filter_Priority($priority);
            $logger->addFilter($filter);

        } else {
            $writer = new Zend_Log_Writer_Null;
            $logger->addWriter($writer);
        }

        Zend_Registry::set('logger', $logger);

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' logger initialized');
    }

    /**
     * initializes the database connection
     *
     */
    protected function setupDatabaseConnection()
    {
        if(isset($this->_config->database)) {
            $dbConfig = $this->_config->database;

            define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_');

            echo "<hr>setting table prefix to: " . SQL_TABLE_PREFIX . " <hr>";

            $db = Zend_Db::factory('PDO_MYSQL', $dbConfig->toArray());
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }

    public function updateInstalledApplications()
    {
        // get list of applications, sorted by id. Tinebase should have the smallest id because it got installed first.
		try {
			$applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
       } catch (Exception $e) {
			return;
	   }
		
		foreach($applications as $application) {
			$xml = $this->parseFile2($application->name);
			if($xml->name == 'Tinebase') {
				$this->_doInitialLoad = false;
			}
			$this->updateApplication($application, $xml->version);
		}
						
    }
	
    /**
     * setup the Database - read from the XML-files
     *
     */

    public function installNewApplications($_tinebaseFile, $_setupFilesPath )
    {	
        if(file_exists($_tinebaseFile)) {
            echo "Processing tables definitions for <b>Tinebase</b> ($_tinebaseFile)<br>";
           $this->parseFile($_tinebaseFile);
        }
        
        foreach ( new DirectoryIterator('./') as $item ) {
            if($item->isDir() && $item->getFileName() != 'Tinebase') {
                $fileName = $item->getFileName() . $_setupFilesPath ;
                if(file_exists($fileName)) {
                    echo "Processing tables definitions for <b>" . $item->getFileName() . "</b>($fileName)<br>";
                    $this->parseFile($fileName);
                }
            }
        }
    }
    

    
    /**
     * returns true if we need to load initial data
     *
     * @return bool
     */
    public function initialLoadRequired()
    {
        return $this->_doInitialLoad;
    }
    

    
    public function addApplication($_xml)
    {
        // just insert tables
        if(isset($_xml->tables)) {
            foreach ($_xml->tables[0] as $table) {
                if (false == $this->_backend->tableExists($table->name)) {
                    try {
                        $this->_backend->createTable($table);
                        $createdTables[] = $table;
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
        }
        
        try {
            $application = Tinebase_Application::getInstance()->getApplicationByName($_xml->name);
        } catch (Exception $e) {
            $application = new Tinebase_Model_Application(array(
                'name'      => $_xml->name,
                'status'    => $_xml->status ? $_xml->status : Tinebase_Application::ENABLED,
                'order'     => $_xml->order ? $_xml->order : 99,
                'version'   => $_xml->version
            ));

            $application = Tinebase_Application::getInstance()->addApplication($application);
        }

        foreach($createdTables as $table) {
            $this->_backend->addTable($application, $table->name, $table->version);
        }

        if(isset($_xml->defaultRecords)) {
            foreach ($_xml->defaultRecords[0] as $record) {
                $this->_backend->execInsertStatement($record);
            }
        }


    }
    
    /**
     * parses the xml stream and creates the tables if needed
     *
     * @param string $_file path to xml file
     */
    public function parseFile($_file)
    {
        $createdTables = array();
    
        $xml = simplexml_load_file($_file);
        
        if (!$this->_backend->applicationExists($xml->name)) {
            $this->addApplication($xml);
        } else {
            $this->updateApplication($xml->name, $xml->version);
        }
    }
    
/**
     * parses the xml stream and creates the tables if needed
     *
     * @param string $_file path to xml file
     */
    public function parseFile2($_applicationName)
    {
        $setupXML = dirname(__FILE__) . '/../' . ucfirst($_applicationName) . '/Setup/setup.xml';
        
        if(!file_exists($setupXML)) {
            throw new Exception(ucfirst($_applicationName) . '/Setup/setup.xml not foud');
        }
        
        $xml = simplexml_load_file($setupXML);
        
        return $xml;
    }
    
    /**
     * compare versions
     *
     * @param string $_tableName (database)
     * @param string $_tableVersion (xml-file)
     * @return 1,0,-1
     */
    public function tableNeedsUpdate($_tableName, $_tableVersion)
    {
        return version_compare($this->tableVersionQuery($_tableName),  $_tableVersion);
    }

    /**
     * update installed application
     *
     * @param string $_name application name
     * @param string $_updateTo version to update to (example: 1.17)
     */
    public function updateApplication(Tinebase_Model_Application $_application, $_updateTo)
    {
        switch(version_compare($_application->version, $_updateTo)) {
            case -1:
                echo "Updating " . $_application->name . " from " . $_application->version . " to $_updateTo<br>";
                
                list($fromMajorVersion, $fromMinorVersion) = explode('.', $_application->version);
                list($toMajorVersion, $toMinorVersion) = explode('.', $_updateTo);
        
                $minor = $fromMinorVersion;
                
                for($major = $fromMajorVersion; $major <= $toMajorVersion; $major++) {
                    if(file_exists(ucfirst($_name) . '/Setup/Update/Release' . $major . '.php')){
                        $className = ucfirst($_name) . '_Setup_Update_Release' . $major;
                    
                        $update = new $className($this->_backend);
                    
                        $classMethods = get_class_methods($update);
                    
                        // we must do at least one update
                        do {
                            $functionName = 'update_' . $minor;
                            //echo "FUNCTIONNAME: $functionName<br>";
                            $update->$functionName();
                            $minor++;
                        } while(array_search('update_' . $minor, $classMethods) !== false);
                    
                        //reset minor version to 0
                        $minor = 0;
                    }
                }
                
                echo "<strong> Updated " . $_application->name . " successfully to " .  $_updateTo . "</strong><br>";
                
                break; 
                
            case 0:
                echo "<i>" . $_application->name . " is up to date (Version: " . $_updateTo . ")</i><br>";
                break;
                
            case 1:
                echo "<span style=color:#ff0000>Something went wrong!!! Current application version is higher than version from setup.xml.</span>";
                throw new Exception('Current application version is higher than version from setup.xml');
                break;
        }        
    }
}
