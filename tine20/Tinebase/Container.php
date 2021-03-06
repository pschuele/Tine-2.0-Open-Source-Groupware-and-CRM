<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        refactor that: remove code duplication, remove Zend_Db_Table_Abstract usage
 * @todo        move (or replace from) functions to backend
 * @todo        switch containers to hash ids
 */

/**
 * this class handles access rights(grants) to containers
 * 
 * any record in Tine 2.0 is tied to a container. the rights of an account on a record gets 
 * calculated by the grants given to this account on the container holding the record (if you know what i mean ;-))
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Container extends Tinebase_Backend_Sql_Abstract
{
	/**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'container';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Container';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
		
    /**
     * the table object for the container_acl table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_containerAclTable;
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Container
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Container
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Container;
        }
        
        return self::$_instance;
    }

    /**
     * creates a new container
     *
     * @param   Tinebase_Model_Container $_container the new container
     * @param   Tinebase_Record_RecordSet $_grants the grants for the new folder 
     * @param   bool  $_ignoreAcl
     * @param   integer $_accountId
     * @return  Tinebase_Model_Container the newly created container
     * @throws  Tinebase_Exception_Record_Validation
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addContainer(Tinebase_Model_Container $_container, $_grants = NULL, $_ignoreAcl = FALSE, $_accountId = NULL)
    {
        if(!$_container->isValid()) {
            throw new Tinebase_Exception_Record_Validation('Invalid container object supplied.');
        }
        
        if ( $_accountId !== NULL ) {
            $accountId = $_accountId;
        } else {

            $accountId = Tinebase_Core::getUser()->getId();
        }
        
        if($_ignoreAcl !== TRUE) {
            switch($_container->type) {
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    // is the user allowed to create personal container?
                    break;
                    
                case Tinebase_Model_Container::TYPE_SHARED:
                    $application = Tinebase_Application::getInstance()->getApplicationById($_container->application_id);
                    $appName = (string) $application;
                    $manageRight = FALSE;
                    
                    // check for MANAGE_SHARED_FOLDERS right
                    $appAclClassName = $appName . '_Acl_Rights';
                    if (@class_exists($appAclClassName)) {
                        $appAclObj = call_user_func(array($appAclClassName, 'getInstance'));
                        $allRights = $appAclObj->getAllApplicationRights();
                        if (in_array(Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS, $allRights)) {
                            $manageRight = Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS);
                        }
                    }
                    

                    if(!$manageRight && !Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights::ADMIN)) {
                        throw new Tinebase_Exception_AccessDenied('Permission to add shared container denied.');
                    }
                    break;
                    
                default:
                    throw new Tinebase_Exception_InvalidArgument('Can add personal or shared folders only when ignoring ACL.');
                    break;
            }
        }
        
        
        if($_grants === NULL) {
            $creatorGrants = array(
                'account_id'     => $accountId,
                'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                Tinebase_Model_Grants::GRANT_EXPORT    => true,
                Tinebase_Model_Grants::GRANT_SYNC      => true,
                Tinebase_Model_Grants::GRANT_ADMIN     => true,
            );
            
            if($_container->type === Tinebase_Model_Container::TYPE_SHARED) {
    
                // add all grants to creator
                // add read grants to any other user
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                    $creatorGrants,            
                    array(
                        'account_id'      => '0',
                        'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                        Tinebase_Model_Grants::GRANT_READ       => true
                    )            
                ), TRUE);
            } else {
                // add all grants to creator only
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array($creatorGrants), TRUE);
            }
        } else {
            $grants = $_grants;
        }
        
        $event = new Tinebase_Event_Container_BeforeCreate();
        $event->accountId = $accountId;
        $event->container = $_container;
        $event->grants = $grants;
        Tinebase_Event::fireEvent($event);
        
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_container, 'create');
        $container = $this->create($_container);
        $this->setGrants($container->getId(), $grants, TRUE);
        
        return $container;
    }
    
    /**
     * add grants to container
     *
     * @todo    check that grant is not already given to container/type/accout combi
     * @param   int $_containerId
     * @param   int $_accountId
     * @param   array $_grants list of grants to add
     * @return  boolean
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addGrants($_containerId, $_accountType, $_accountId, array $_grants, $_ignoreAcl = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        

        if($_ignoreAcl !== TRUE and !$this->hasGrant(Tinebase_Core::getUser(), $_containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to manage grants on container denied.');
        }
        
        switch($_accountType) {
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                $accountId = Tinebase_Model_Group::convertGroupIdToInt($_accountId);
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                $accountId = '0';
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('invalid $_accountType');
                break;
        }
        
        $containerGrants = $this->getGrantsOfContainer($containerId, TRUE);
        $containerGrants->addIndices(array('account_type', 'account_id'));
        $existingGrants = $containerGrants->filter('account_type', $_accountType)->filter('account_id', $_accountId)->getFirstRecord();
        
        $id = Tinebase_Record_Abstract::generateUID();
        
        foreach($_grants as $grant) {
            if ($existingGrants === NULL || ! $existingGrants->{$grant}) {
                $data = array(
                    'id'            => $id,
                    'container_id'  => $containerId,
                    'account_type'  => $_accountType,
                    'account_id'    => $accountId,
                    'account_grant' => $grant
                );
                $this->_getContainerAclTable()->insert($data);
            }
        }
        
        $this->_clearCache();
        
        return true;
    }
    
    /**
     * return all container, which the user has the requested right for
     * - cache the results because this function is called very often
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_onlyIds return only ids
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet|array
     * @throws  Tinebase_Exception_NotFound
     */
    public function getContainerByACL($_accountId, $_application, $_grant, $_onlyIds = FALSE, $_ignoreACL = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' app: ' . $_application . ' / account: ' . $_accountId . ' / grant:' . implode('', (array)$_grant));
        
        $accountId     = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_application)->getId();
        $grant         = $_ignoreACL ? '*' : $_grant;
        
        $cache = Tinebase_Core::getCache();
        $cacheId = convertCacheId('getContainerByACL' . $accountId . $applicationId . implode('', (array)$grant) . $_onlyIds);
        $result = $cache->load($cacheId);
        
        if ($result === FALSE) {
            $select = $this->_getSelect($_onlyIds ? 'id' : '*')
                ->join(array(
                    /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                    /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                    /* select */ array()
                )
                
                ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $applicationId)
                
                ->group('container.id')
                ->order('container.name');
            
            $this->addGrantsSql($select, $accountId, $grant);
    
            $stmt = $this->_db->query($select);
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            if ($_onlyIds) {
                $result = array();
                foreach ($rows as $row) {
                    $result[] = $row['id'];
                }
            } else {
                $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $rows, TRUE);
            }
            
            // any account should have at least one personal folder
            if(empty($result)) {
                $personalContainer = $this->getDefaultContainer($_accountId, $_application);
                if ($personalContainer instanceof Tinebase_Model_Container) {
                    $result = ($_onlyIds) ? 
                        array($personalContainer->getId()) : 
                        new Tinebase_Record_RecordSet('Tinebase_Model_Container', $personalContainers, TRUE);
                }
            }
            
            // save result and tag it with 'container'
            $cache->save($result, $cacheId, array('container'));
        }
            
        return $result;
        
    }
    
    /**
     * return a container by containerId
     * - cache the results because this function is called very often
     *
     * @todo what about grant checking here???
     * 
     * @param   int|Tinebase_Model_Container $_containerId the id of the container
     * @param   bool                         $_getDeleted get deleted records
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getContainerById($_containerId, $_getDeleted = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        $cacheId = 'getContainerById' . $containerId . 'd' . (int)$_getDeleted;

        // load from cache
        $cache = Tinebase_Core::getCache();
        $result = $cache->load($cacheId);

        if($result === FALSE) {
            $result = $this->get($containerId, $_getDeleted);
            $cache->save($result, $cacheId, array('container'));
        }
        
        return $result;
        
    }
    
    /**
     * return a container by container name
     *
     * @param   int|Tinebase_Model_Container $_containerId the id of the container
     * @param   int|Tinebase_Model_Container $_ignoreACL
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function getContainerByName($_application, $_containerName, $_type)
    {
        if($_type !== Tinebase_Model_Container::TYPE_PERSONAL && $_type !== Tinebase_Model_Container::TYPE_SHARED) {
            throw new Tinebase_Exception_UnexpectedValue ("Invalid type $_type supplied.");
        }
        
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_application)->getId();
        
        $filter = new Tinebase_Model_ContainerFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $applicationId),
            array('field' => 'name', 'operator' => 'equals', 'value' => $_containerName),
            array('field' => 'type', 'operator' => 'equals', 'value' => $_type),
        ));
        
        $container =  $this->search($filter)->getFirstRecord();
        if (! $container) {
            throw new Tinebase_Exception_NotFound("Container $_containerName not found.");
        }
        
        return $container;
    }
    
    /**
     * returns the personal container of a given account accessible by a another given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   int|Tinebase_Model_User             $_owner
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getPersonalContainer($_accountId, $_application, $_owner, $_grant, $_ignoreACL = false)
    {
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $ownerId     = Tinebase_Model_User::convertUserIdToInt($_owner);
        $grant       = $_ignoreACL ? '*' : $_grant;
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $this->_db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(array(
                /* table  */ 'user' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('user.container_id')}",
                /* select */ array()
            )
            ->join(array(
                /* table  */ 'container' => SQL_TABLE_PREFIX . 'container'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('container.id')}")
            
            ->where("{$this->_db->quoteIdentifier('owner.account_id')} = ?", $ownerId)
            ->where("{$this->_db->quoteIdentifier('owner.account_grant')} = ?", Tinebase_Model_Grants::GRANT_ADMIN)
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            
            ->group('container.id')
            ->order('container.name');
            
        $this->addGrantsSql($select, $accountId, $grant, 'user');
        
        $stmt = $this->_db->query($select);
        $containersData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // if no containers where found,  maybe something went wrong when creating the initial folder
        // let's check if the controller of the application has a function to create the needed folders
        if (empty($containersData) and $accountId === $ownerId) {
            $application = Tinebase_Core::getApplicationInstance($application->name);
            
            if ($application instanceof Tinebase_Container_Interface) {
                return $application->createPersonalFolder($accountId);
            }
        }

        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $containersData, TRUE);
        
        return $containers;
    }
    
    /**
     * appends container_acl sql 
     * 
     * @param  Zend_Db_Select    $_select
     * @param  String            $_accountId
     * @param  Array|String      $_grant
     * @param  String            $_aclTableName
     * @return void
     */
    public static function addGrantsSql($_select, $_accountId, $_grant, $_aclTableName = 'container_acl')
    {
        $db = $_select->getAdapter();
        
        $grants = is_array($_grant) ? $_grant : array($_grant);
        
        // admin grant includes all other grants
        if (! in_array(Tinebase_Model_Grants::GRANT_ADMIN, $grants)) {
            $grants[] = Tinebase_Model_Grants::GRANT_ADMIN;
        }

        // @todo fetch wildcard from specific db adapter
        $grants = str_replace('*', '%', $grants);
        
        if (empty($grants)) {
            $_select->where('1=0');
            return;
        }
        
        // @todo add groupmembers via join
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($_accountId);
        
        $quotedActId   = $db->quoteIdentifier("{$_aclTableName}.account_id");
        $quotedActType = $db->quoteIdentifier("{$_aclTableName}.account_type");
        $quotedGrant   = $db->quoteIdentifier("{$_aclTableName}.account_grant");
        
        $accountSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        $accountSelect
            ->orWhere("{$quotedActId} = ? AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER), $_accountId)
            ->orWhere("{$quotedActId} IN (?) AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP), empty($groupMemberships) ? ' ' : $groupMemberships)
            ->orWhere("{$quotedActType} = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        
        $grantsSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        foreach ($grants as $grant) {
            $grantsSelect->orWhere("{$quotedGrant} LIKE ?", $grant);
        }
        
        $grantsSelect->appendWhere(Zend_Db_Select::SQL_AND);
        $accountSelect->appendWhere(Zend_Db_Select::SQL_AND);
    }
    
    /**
     * gets default container of given user for given app
     *  - returns personal first container at the moment
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_applicationName
     * @return  Tinebase_Model_Container
     * 
     * @todo return default container from preferences if available
     * @todo create new default/personal container if it was deleted
     */
    public function getDefaultContainer($_accountId, $_applicationName)
    {
        return $this->getPersonalContainer(
            $_accountId, 
            $_applicationName, 
            $_accountId, 
            Tinebase_Model_Grants::GRANT_ADD
        )->getFirstRecord();
    }
    
    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getSharedContainer($_accountId, $_application, $_grant, $_ignoreACL = FALSE)
    {
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        $grant       = $_ignoreACL ? '*' : $_grant;
        
        $select = $this->_getSelect()
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                array()
            )
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_SHARED)
            
            ->group('container.id')
            ->order('container.name');
        
        $this->addGrantsSql($select, $accountId, $grant);
        
        $stmt = $this->_db->query($select);

        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC), TRUE);
        
        return $containers;
    }
    
    /**
     * return users which made personal containers accessible to given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_User
     */
    public function getOtherUsers($_accountId, $_application, $_grant, $_ignoreACL = FALSE)
    {
        $containersData = $this->_getOtherUsersContainerData($_accountId, $_application, $_grant, $_ignoreACL);
        
        $userIds = array();
        foreach($containersData as $containerData) {
            $userIds[] = $containerData['account_id'];
        }

        $users = Tinebase_User::getInstance()->getMultiple($userIds);
        $users->sort('accountDisplayName');
        
        return $users;
    }
    
    /**
     * return set of all personal container of other users made accessible to the given account 
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getOtherUsersContainer($_accountId, $_application, $_grant, $_ignoreACL = FALSE)
    {
        $containerData = $this->_getOtherUsersContainerData($_accountId, $_application, $_grant, $_ignoreACL);
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $containerData, TRUE);
        
        return $result;
    }
    
    /**
     * return containerData of containers which made personal accessible to given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  array of array of containerData
     */
    protected function _getOtherUsersContainerData($_accountId, $_application, $_grant, $_ignoreACL = FALSE)
    {
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        $grant       = $_ignoreACL ? '*' : $_grant;
        
        $select = $this->_db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array('account_id'))
            ->join(array(
                /* table  */ 'user' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('user.container_id')}",
                /* select */ array()
            )
            ->join(array(
                /* table  */ 'container' => SQL_TABLE_PREFIX . 'container'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('container.id')}"
            )
            ->join(array(
                /* table  */ 'accounts' => SQL_TABLE_PREFIX . 'accounts'),
                /* on     */ "{$this->_db->quoteIdentifier('owner.account_id')} = {$this->_db->quoteIdentifier('accounts.id')}",
                /* select */ array()
            )
            #->join(array(
            #    /* table  */ 'contacts' => SQL_TABLE_PREFIX . 'addressbook'),
            #    /* on     */ "{$this->_db->quoteIdentifier('owner.account_id')} = {$this->_db->quoteIdentifier('contacts.account_id')}",
            #    /* select */ array()
            #)
            ->where("{$this->_db->quoteIdentifier('owner.account_id')} != ?", $accountId)
            ->where("{$this->_db->quoteIdentifier('owner.account_grant')} = ?", Tinebase_Model_Grants::GRANT_ADMIN)
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            ->where("{$this->_db->quoteIdentifier('accounts.status')} = ?", 'enabled')
            
            ->order('accounts.display_name')
            ->group('owner.account_id');
                
        $this->addGrantsSql($select, $accountId, $grant, 'user');
        
        $stmt = $this->_db->query($select);
        $containersData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $containersData;
    }
    
    /**
     * delete container if user has the required right
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   boolean $_ignoreAcl
     * @param   boolean $_tryAgain
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_InvalidArgument
     * 
     * @todo move records in deleted container to personal container?
     */
    public function deleteContainer($_containerId, $_ignoreAcl = FALSE, $_tryAgain = TRUE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        $container = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($containerId);
        
        if($_ignoreAcl !== TRUE) {

            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to delete container denied.');
            }
            
            if($container->type !== Tinebase_Model_Container::TYPE_PERSONAL and $container->type !== Tinebase_Model_Container::TYPE_SHARED) {
                throw new Tinebase_Exception_InvalidArgument('Can delete personal or shared containers only.');
            }
        }
        
        //$this->delete($containerId);
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($container, 'delete', $container);
        $this->update($container);
        
        $this->_clearCache();
        
        /*
        // move all contained objects to next available personal container and try again to delete container
        $app = Tinebase_Application::getApplicationById($container->application_id);

        // get personal containers
        $personalContainers = $this->getPersonalContainer(
            Tinebase_Core::getUser(),
            $app->name,
            $container->owner,
            Tinebase_Model_Grants::GRANT_ADD
        );
        
        //-- determine first matching personal container (or create new one)
        // $personalContainer = 
        
        //-- move all records to personal container
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Moving all records from container ' . $containerId . ' to personal container ' . $personalContainer->getId()
        );
        */
    }
    
    /**
     * delete container by application id
     * 
     * @param string $_applicationId
     * @return integer numer of deleted containers 
     */
    public function deleteContainerByApplicationId($_applicationId)
    {
        return $this->deleteByProperty($_applicationId, 'application_id');
    }    
    
    /**
     * set container name, if the user has the required right
     *
     * @param   int $_containerId
     * @param   string $_containerName the new name
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function setContainerName($_containerId, $_containerName)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);


        if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Permission to rename container denied.');
        }
        
        $where = array(
            $this->_db->quoteInto('id = ?', $containerId)
        );
        
        $data = array(
            'name' => $_containerName
        );
        
        $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);

        $this->_clearCache();
        
        return $this->getContainerById($_containerId);
    }
    
    /**
     * set container color, if the user has the required right
     *
     * @param   int $_containerId
     * @param   string $_color the new color
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function setContainerColor($_containerId, $_color)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);

        if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Permission to set color of container denied.');
        }
        
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $_color)) {
            throw new Tinebase_Exception_UnexpectedValue('color is not valid');
        }
        
        $where = array(
            $this->_db->quoteInto('id = ?', $containerId)
        );
        
        $data = array(
            'color' => $_color
        );
        
        $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);

        $this->_clearCache();
        
        return $this->getContainerById($_containerId);
    }
    
    /**
     * check if the given user user has a certain grant
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   int|Tinebase_Model_Container        $_containerId
     * @param   array|string                        $_grant
     * @return  boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant) 
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        try {
            $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $teia->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $teia->getTraceAsString());
            return FALSE;
        }

        $cache = Tinebase_Core::getCache();
        $cacheId = convertCacheId('hasGrant' . $accountId . $containerId . implode('', (array)$_grant));
        $result = $cache->load($cacheId);
        
        if ($result === FALSE) {
            // NOTE: some tests ask for already deleted container ;-)
            $select = $this->_getSelect('*', TRUE)
            ->where("{$this->_db->quoteIdentifier('container.id')} = ?", $containerId)
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array()
            );
            
            $this->addGrantsSql($select, $accountId, $_grant);
            
            $stmt = $this->_db->query($select);
            
            $grants = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            $result = ! empty($grants);
            
            // save result and tag it with 'container'
            $cache->save($result, $cacheId, array('container'));
        }
        
        return $result;
    }
    
    /**
     * get all grants assigned to this container
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   bool                         $_ignoreAcl
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getGrantsOfContainer($_containerId, $_ignoreAcl = FALSE) 
    {
        $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        $select = $this->_getSelect('*', TRUE)
            ->where("{$this->_db->quoteIdentifier('container.id')} = ?", $containerId)
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array('*', 'account_grants' => "GROUP_CONCAT( DISTINCT container_acl.account_grant)")
            )
            ->group(array('container.id', 'container_acl.account_type', 'container_acl.account_id'));
            
        $stmt = $this->_db->query($select);

        $grantsData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);


        // @todo use _getGrantsFromArray here
        foreach($grantsData as $grantData) {
        	
            $givenGrants = explode(',', $grantData['account_grants']);
            foreach($givenGrants as $grant) {
                $grantData[$grant] = TRUE;
            }
        	
            $containerGrant = new Tinebase_Model_Grants($grantData, TRUE);

            $grants->addRecord($containerGrant);
        }
        
        if ($_ignoreAcl !== TRUE) {
            if (TRUE !== $this->hasGrant(Tinebase_Core::getUser()->getId(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to get grants of container denied.');
            }
        }
        return $grants;
    }
    
    /**
     * get grants assigned to one account of one container
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   int|Tinebase_Model_Container        $_containerId
     * @return Tinebase_Model_Grants
     */
    public function getGrantsOfAccount($_accountId, $_containerId) 
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $containerId        = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        $cacheKey = convertCacheId('getGrantsOfAccount' . $containerId . $accountId);
        $cache = Tinebase_Core::getCache();
        $grants = $cache->load($cacheKey);
        if($grants === FALSE) {
            $select = $this->_getSelect('*', TRUE)
                ->where("{$this->_db->quoteIdentifier('container.id')} = ?", $containerId)
                ->join(array(
                    /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                    /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                    /* select */ array('*', 'account_grants' => "GROUP_CONCAT( DISTINCT container_acl.account_grant)")
                )
                ->group('container_acl.account_grant');
    
            $this->addGrantsSql($select, $accountId, '*');
            
            $stmt = $this->_db->query($select);
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
	        $grants = $this->_getGrantsFromArray($rows, $accountId);
            
            $cache->save($grants, $cacheKey, array('container'));
        }
        return $grants;
    }
    
    /**
     * get grants assigned to given account of multiple records
     *
     * @param   Tinebase_Record_RecordSet   $_records records to get the grants for
     * @param   string|Tinebase_Model_User  $_accountId the account to get the grants for
     * @param   string                      $_containerProperty container property
     * @throws  Tinebase_Exception_NotFound
     */
    public function getGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId, $_containerProperty = 'container_id')
    {
        // get container ids
        $containers = array();
        foreach ($_records as $record) {
            if (isset($record[$_containerProperty]) && !isset($containers[$record[$_containerProperty]])) {
                $containers[Tinebase_Model_Container::convertContainerIdToInt($record[$_containerProperty])] = array();
            }
        }
        
        if (empty($containers)) {
        	return;
        }
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        $select = $this->_getSelect('*', TRUE)
            ->where("{$this->_db->quoteIdentifier('container.id')} IN (?)", array_keys($containers))
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array('*', 'account_grants' => "GROUP_CONCAT( DISTINCT container_acl.account_grant)")
            )
            ->joinLeft(array(
                /* table  */ 'owner' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('container.id')} " .
                             "AND {$this->_db->quoteIdentifier('container.type')} = {$this->_db->quote(Tinebase_Model_Container::TYPE_PERSONAL)} " .
                             "AND {$this->_db->quoteIdentifier('owner.account_grant')} = {$this->_db->quote(Tinebase_Model_Grants::GRANT_ADMIN)}",
                /* select */ array('owner.account_id AS owner_id')
            )
            ->group('container.id', 'container_acl.account_type', 'container_acl.account_id');
        
        $this->addGrantsSql($select, $accountId, '*');
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // add results to container ids and get grants array
        foreach ($rows as $row) {
            // NOTE id is non-ambiguous
            $row['id'] = $row['container_id'];
            $grantsArray = array_unique(explode(',', $row['account_grants']));
            $row['account_grants'] = $this->_getGrantsFromArray($grantsArray, $accountId)->toArray();
            $containers[$row['id']] = new Tinebase_Model_Container($row, TRUE);
        }
        
        // add container & grants to records
        foreach ($_records as &$record) {
            try {
                if (!isset($record->$_containerProperty)) {
                    continue;
                }
                
                $containerId = $record[$_containerProperty];
                if (! is_array($containerId) && ! $containerId instanceof Tinebase_Record_Abstract && ! empty($containers[$containerId])) {
                    $record[$_containerProperty] = $containers[$containerId];
                    $record[$_containerProperty]['path'] = $containers[$containerId]->getPath();
                }
            } catch (Exception $e) {
                // if path is not determinable, skip this container
                $_records->removeRecord($record);
            }
        }
    }
    
    /**
     * set all grant for given container
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   Tinebase_Record_RecordSet $_grants
     * @param   boolean $_ignoreAcl
     * @param   boolean $_failSafe don't allow to remove all admin grants for container
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Backend
     * @throws  Tinebase_Exception_Record_NotAllowed
     */
    public function setGrants($_containerId, Tinebase_Record_RecordSet $_grants, $_ignoreAcl = FALSE, $_failSafe = TRUE) 
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        if($_ignoreAcl !== TRUE) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to set grants of container denied.');
            }            
        }
        
        // do failsafe check
        if ($_failSafe) {
            $adminGrant = FALSE;
            foreach ($_grants as $recordGrants) {
                if ($recordGrants->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                    $adminGrant = TRUE;
                }
            }
            if (count($_grants) == 0 || ! $adminGrant) {
                throw new Tinebase_Exception_UnexpectedValue('You are not allowed to remove all (admin) grants for this container.');
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting grants for container id ' . $containerId . ' ...');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_grants->toArray(), TRUE));
        
        $container = $this->getContainerById($containerId);
       
        try {

            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            $where = $this->_getContainerAclTable()->getAdapter()->quoteInto('container_id = ?', $containerId);
            $this->_getContainerAclTable()->delete($where);
            
            foreach($_grants as $recordGrants) {
                $data = array(
                    'id'            => $recordGrants->getId(),
                    'container_id'  => $containerId,
                    'account_id'    => $recordGrants['account_id'],
                    'account_type'  => $recordGrants['account_type'],
                );
                if(empty($data['id'])) {
                    $data['id'] = $recordGrants->generateUID();
                }
                
                foreach ($recordGrants as $grantName => $grant) {
                    if (in_array($grantName, $recordGrants->getAllGrants()) && $grant === TRUE) {
                        $this->_getContainerAclTable()->insert($data + array('account_grant' => $grantName));
                    }
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->_clearCache();
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();            
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
        
        return $this->getGrantsOfContainer($containerId, $_ignoreAcl);
    }
    
    /**
     * remove all container related entries from cache
     */
    protected function _clearCache() 
    {        
        $cache = Tinebase_Core::getCache();
        if (!$cache || !$cache->getOption('caching')) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Removing all container entries from cache.');

        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('container'));
    }

    /**
     * lazy loading for containerACLTable
     * 
     * @return Tinebase_Db_Table
     */
    protected function _getContainerAclTable()
    {
        if (! $this->_containerAclTable) {
            $this->_containerAclTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'container_acl'));
        }
        
        return $this->_containerAclTable;
    }
    
    /**
     * get grants record from an array with grant values
     *
     * @param array $_grantsArray
     * @param int $_accountId
     * @return Tinebase_Model_Grants
     */
    protected function _getGrantsFromArray(array $_grantsArray, $_accountId)
    {
        $grants = array();
        foreach($_grantsArray as $key => $value) {
            $grantValue = (is_array($value)) ? $value['account_grant'] : $value; 
            $grants[$grantValue] = TRUE;
        }
        $grantsFields = array(
            'account_id'     => $_accountId,
            'account_type'   => 'account',
        );
        $grantsFields = array_merge($grantsFields, $grants);
        
        $grants = new Tinebase_Model_Grants($grantsFields, TRUE);

        return $grants;
    }
}
