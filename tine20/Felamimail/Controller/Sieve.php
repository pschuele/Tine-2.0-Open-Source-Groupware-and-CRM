<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add __destruct with $_backend->logout()?
 */

/**
 * Sieve controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Sieve extends Tinebase_Controller_Abstract
{
    /**
     * script name
     *
     * @var string
     */
    protected $_scriptName = 'felamimail2.0';

    /**
     * old script name (this is used to read filter settings from from egw for example and save them with the new name)
     * 
     * @var string
     */
    protected $_oldScriptName = 'felamimail';
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * Sieve Script backend
     *
     * @var Felamimail_Sieve_Script
     */
    protected $_scriptBackend = NULL;
    
    /**
     * Sieve backend
     *
     * @var Felamimail_Backend_Sieve
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Sieve
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_scriptBackend = new Felamimail_Sieve_Script();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Sieve
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Sieve();
        }
        
        return self::$_instance;
    }
    
    /**
     * get vacation for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return Felamimail_Model_Sieve_Vacation
     */
    public function getVacation($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $script = $this->_getSieveScript();
        $vacation = ($script !== NULL) ? $script->getVacation() : NULL;
        
        $result = new Felamimail_Model_Sieve_Vacation(array(
            'id'    => ($_accountId instanceOf Felamimail_Model_Account) ? $_accountId->getId() : $_accountId
        ));
            
        if ($vacation !== NULL) {
            $result->setFromFSV($vacation);
        }
        
        return $result;
    }
    
    /**
     * get sieve script for account
     * 
     * @return NULL|Felamimail_Sieve_Script
     */
    protected function _getSieveScript()
    {
        $result = NULL;
        $scripts = $this->_backend->listScripts();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting list of SIEVE scripts: ' . print_r($scripts, TRUE));
   
        foreach (array($this->_scriptName, $this->_oldScriptName) as $scriptNameToFetch) {
            if (count($scripts) > 0 && array_key_exists($scriptNameToFetch, $scripts)) {
                $scriptName = $scripts[$scriptNameToFetch]['name'];
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Get SIEVE script: ' . $scriptName);
                
                $script = $this->_backend->getScript($scriptName);
                if ($script) {
                    if ($scriptNameToFetch == $this->_oldScriptName) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got old SIEVE script for migration.');
                    }
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Got SIEVE script: ' . $script);
                    return new Felamimail_Sieve_Script($script);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Could not get SIEVE script: ' . $scriptName);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No relevant SIEVE scripts found.');
            }
        }
        
        return $result;
    }
    
    /**
     * init and connect to sieve backend + authenticate with imap user of account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @throws Felamimail_Exception
     */
    protected function _setSieveBackendAndAuthenticate($_accountId)
    {
        if (empty($_accountId)) {
            throw new Felamimail_Exception('No account id given.');
        }
        
        $this->_backend = Felamimail_Backend_SieveFactory::factory($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' sieve server capabilities: ' . print_r($this->_backend->capability(), TRUE));
    }
    
    /**
     * set vacation for account
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     * @return Felamimail_Model_Sieve_Vacation
     */
    public function setVacation(Felamimail_Model_Sieve_Vacation $_vacation)
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_vacation->getId());
        
        $this->_setSieveBackendAndAuthenticate($account);
        $this->_addVacationUserData($_vacation, $account);
        $this->_fixNewlinesAndcheckCapabilities($_vacation);
        $this->_addVacationSubject($_vacation);
        
        $fsv = $_vacation->getFSV();
        
        $script = $this->_getSieveScript();
        if ($script === NULL) {
            $script = new Felamimail_Sieve_Script();
        }
        $script->setVacation($fsv);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated vacation SIEVE script ' . $this->_scriptName);
        
        $this->_putScript($account, $script);
        Felamimail_Controller_Account::getInstance()->setVacationActive($account, $_vacation->enabled);
        
        return $this->getVacation($account);
    }
    
    /**
     * add addresses and from to vacation
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     * @param Felamimail_Model_Account $_account
     */
    protected function _addVacationUserData(Felamimail_Model_Sieve_Vacation $_vacation, Felamimail_Model_Account $_account)
    {
        $addresses = array();
        if ($_account->type == Felamimail_Model_Account::TYPE_SYSTEM) {
            $addresses[] = (! empty($this->_currentAccount->accountEmailAddress)) ? $this->_currentAccount->accountEmailAddress : $_account->email;
            if ($this->_currentAccount->smtpUser && ! empty($this->_currentAccount->smtpUser->emailAliases)) {
                $addresses = array_merge($addresses, $this->_currentAccount->smtpUser->emailAliases);
            }
        } else {
            $addresses[] = $_account->email;
        }
        
        $from = $_account->from;
        if (strpos($from, '@') === FALSE) {
            $from .= ' <' . $_account->email . '>';
        }
        
        $_vacation->addresses = $addresses;
        $_vacation->from = $from;
    }
    
    /**
     * check sieve backend capabilities
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     */
    protected function _fixNewlinesAndcheckCapabilities(Felamimail_Model_Sieve_Vacation $_vacation)
    {
        $capabilities = $this->_backend->capability();
        
        if (! in_array('mime', $capabilities['SIEVE'])) {
            unset($_vacation->mime);
            $_vacation->reason = preg_replace('/<br \/>/', "\r", $_vacation->reason);
        }
        $_vacation->reason = preg_replace('/\n/', "", $_vacation->reason);
        
        if (preg_match('/cyrus/i', $capabilities['IMPLEMENTATION'])) {
            // cyrus does not support :from
            unset($_vacation->from);
        }
    }
    
    /**
     * add vacation subject
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     */
    protected function _addVacationSubject(Felamimail_Model_Sieve_Vacation $_vacation)
    {
        if (preg_match('/dbmail/i', $this->_backend->getImplementation())) {
            // dbmail seems to have problems with different subjects and sends vacation responses to the same recipients again and again
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            $_vacation->subject = $translate->_('Out of Office reply');
        }
    }
        
    /**
     * put updated sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @param Felamimail_Sieve_Script $_script
     * @throws Felamimail_Exception_Sieve
     */
    protected function _putScript($_accountId, $_script)
    {
        $scriptToPut = $_script->getSieve();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $scriptToPut);
        
        try {
            $this->_backend->putScript($this->_scriptName, $scriptToPut);
            $this->activateScript($_accountId);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getTraceAsString());
            throw new Felamimail_Exception_SievePutScriptFail($zmpe->getMessage());
        }
    }

    /**
     * set sieve script name
     * 
     * @param string $_name
     */
    public function setScriptName($_name)
    {
        $this->_scriptName = $_name;
    }
    
    /**
     * delete sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    public function deleteScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete SIEVE script ' . $this->_scriptName);
        
        $this->_backend->deleteScript($this->_scriptName);
    }

    /**
     * activate sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    public function activateScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Activate vacation SIEVE script ' . $this->_scriptName);
        $this->_backend->setActive($this->_scriptName);
    }
    
    /**
     * get name of active script for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return string|NULL
     */
    public function getActiveScriptName($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $scripts = $this->_backend->listScripts();
        
        $result = NULL;
        foreach ($scripts as $scriptname => $values) {
            if ($values['active'] == 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found active script: ' . $scriptname);
                $result = $scriptname;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Inactive script: ' . $scriptname);
            }
        }
        
        return $result;
    }
    
    /**
     * get rules for account
     * 
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Sieve_Rule
     */
    public function getRules($_accountId)
    {
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_Rule');
        
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $script = $this->_getSieveScript();
        if ($script !== NULL) {
            foreach ($script->getRules() as $fsr) {
                $rule = new Felamimail_Model_Sieve_Rule();
                $rule->setFromFSR($fsr);
                $result->addRecord($rule);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result->toArray(), TRUE));
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Sieve script empty or could not parse it.');            
        }
        
        return $result;
    }
    
    /**
     * set rules for account
     * 
     * @param string $_accountId
     * @param Tinebase_Record_RecordSet $_rules (Felamimail_Model_Sieve_Rule)
     * @return Tinebase_Record_RecordSet
     */
    public function setRules($_accountId, Tinebase_Record_RecordSet $_rules)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $script = $this->_getSieveScript();
        if ($script === NULL) {
            $script = new Felamimail_Sieve_Script();
        } else {
            $script->clearRules();
        }
        
        foreach ($_rules as $rule) {
            $fsr = $rule->getFSR();
            $script->addRule($fsr);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated rules SIEVE script ' . $this->_scriptName);
        
        $this->_putScript($_accountId, $script);
        
        return $this->getRules($_accountId);
    }
}
