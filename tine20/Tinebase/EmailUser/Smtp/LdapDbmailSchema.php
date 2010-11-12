<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * plugin to handle smtp settings for dbmail ldap schema
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_LdapDbmailSchema extends Tinebase_EmailUser_Ldap
{
    /**
     * dbmail config
     * 
     * @var array 
     */
    protected $_config = array(
        'emailGID' => null
    );
    
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailAddress'  => 'mail',
        'emailAliases'  => 'mailalternateaddress', 
        'emailForwards' => 'mailforwardingaddress'
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'dbmailUser'
    );    
    
    protected $_backendType = Tinebase_Model_Config::SMTP;
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        $this->_config['emailGID'] = sprintf("%u", crc32(Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId()));
    }
    
    /**
     * returns array of ldap data
     *
     * @param  Tinebase_Model_FullUser  $_user
     * @param  array                    $_ldapData
     * @param  array                    $_ldapEntry
     */
    protected function _user2Ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry)
    {
        if (empty($_user->accountEmailAddress)) {
            foreach ($this->_propertyMapping as $ldapKeyName) {
                $_ldapData[$ldapKeyName] = array();
            }
            $_ldapData['accountStatus'] = array();
            $_ldapData['mailHost']      = array();
            
            $_ldapData['objectclass'] = array_unique(array_diff($_ldapEntry['objectclass'], $this->_requiredObjectClass));
            
        } else {
            parent::_user2Ldap($_user, $_ldapData, $_ldapEntry);
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($_ldapData, true));
    }
}  