<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Event Controller
 * 
 * @package Calendar
 */
class Calendar_Controller_Event extends Tinebase_Controller_Record_Abstract implements Tinebase_Controller_Alarm_Interface
{
    // todo in this controller:
    //
    // add free time search
    // add group attendee handling
    // add handling to fetch all exceptions of a given event set (ActiveSync Frontend)
    
    /**
     * @var Calendar_Controller_Event
     */
    private static $_instance = NULL;
    
    /**
     * @var Tinebase_Model_User
     */
    protected $_currentAccount = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Event';
        $this->_backend         = new Calendar_Backend_Sql();
        $this->_currentAccount  = Tinebase_Core::getUser();
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Controller_Event
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_Event();
        }
        return self::$_instance;
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $event = parent::create($_record);
        
        $this->_saveAttendee($_record);
        $this->_saveAlarms($_record);
        
        return $this->get($event->getId());
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "updating event: {$_record->id} ");
        $event = parent::update($_record);
        
        $this->_saveAttendee($_record);
        $this->_saveAlarms($_record);
        
        return $this->get($event->getId());
    }
    
    /**
     * creates an exception instance of a recuring evnet
     *
     * NOTE: deleting persistent exceptions is done via a normal delte action
     *       and handled in the delteInspection
     * 
     * @param  Calendar_Model_Event  $_event
     * @param  bool                  $_deleteInstance
     * @return Calendar_Model_Event  exception Event | updated baseEvent
     */
    public function createRecurException($_event, $_deleteInstance = FALSE)
    {
        // NOTE: recurd is computed by rrule recur computations and therefore is already
        //       part of the event.
        if (empty($_event->recurid)) {
            throw new Exception('recurid must be present to create exceptions!');
        }
        
        $baseEvent = $this->_getRecurBaseEvent($_event);
                
        if (! $_deleteInstance) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating persistent exception for: '{$_event->recurid}'");
            
            $_event->setId(NULL);
            unset($_event->rrule);
            unset($_event->exdate);
            
            $_event->attendee->setId(NULL);
            $_event->notes->setId(NULL);
            
            // we need to touch the recur base event, so that sync action find the updates
            $this->_backend->update($baseEvent);
            
            return $this->create($_event);
            
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleting recur instance: '{$_event->recurid}'");
            
            $exdate = new Zend_Date(substr($_event->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
            if (is_array($baseEvent->exdate)) {
                $exdates = $baseEvent->exdate;
                array_push($exdates, $exdate);
                $baseEvent->exdate = $exdates;
            } else {
                $baseEvent->exdate = array($exdate);
            }
            
            return $this->update($baseEvent);
        }
    }
    
    /**
     * returns base event of a recuring series
     *
     * @param  Calendar_Model_Event $_event
     * @return Calendar_Model_Event
     */
    protected function _getRecurBaseEvent($_event)
    {
        return $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $_event->uid),
            array('field' => 'recurid', 'operator' => 'isnull', 'value' => NULL)
        )))->getFirstRecord();
    }
    
    /****************************** overwritten functions ************************/
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
    {
        $_record->uid = $_record->uid ? $_record->uid : Tinebase_Record_Abstract::generateUID();
        $_record->originator_tz = $_record->originator_tz ? $_record->originator_tz : Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        // if dtstart of an event changes, we update the originator_tz
        if (! $_oldRecord->dtstart->equals($_record->dtstart)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart changed -> adopting organizer_tz');
            $_record->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
            
            // update exdates and recurids if dtsart of an recurevent changes
            if (! empty($_record->rrule)) {
                $diff = clone $_record->dtstart;
                $diff->sub($_oldRecord->dtstart);
                
                // update rrule->until
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting rrule_until');
                
                
                $rrule = $_record->rrule instanceof Calendar_Model_Rrule ? $_record->rrule : Calendar_Model_Rrule::getRruleFromString($_record->rrule);
                Calendar_Model_Rrule::addUTCDateDstFix($rrule->until, $diff, $_record->originator_tz);
                $_record->rrule = (string) $rrule;
                
                // update exdate(s)
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting '. count($_record->exdate) . ' exdate(s)');
                foreach ((array)$_record->exdate as $exdate) {
                    Calendar_Model_Rrule::addUTCDateDstFix($exdate, $diff, $_record->originator_tz);
                }
                
                // update exceptions
                $exceptions = $this->_backend->getMultipleByProperty($_record->uid, 'uid');
                unset($exceptions[$exceptions->getIndexById($_record->getId())]);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting '. count($exceptions) . ' recurid(s)');
                foreach ($exceptions as $exception) {
                    $originalDtstart = new Zend_Date(substr($exception->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
                    Calendar_Model_Rrule::addUTCDateDstFix($originalDtstart, $diff, $_record->originator_tz);
                    
                    $exception->recurid = $exception->uid . '-' . $originalDtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
                    $this->_backend->update($exception);
                }
            }
        }
        
        // delete recur exceptions if update is not longer a recur series
        if (! empty($_oldRecord->rrule) && empty($_record->rrule)) {
            $exceptionIds = $this->_backend->getMultipleByProperty($_record->uid, 'uid')->getId();
            unset($exceptionIds[array_search($_record->getId(), $exceptionIds)]);
            $this->_backend->delete($exceptionIds);
        }
        
        // touch base event of a recur series if an persisten exception changes
        if ($_record->recurid) {
            $baseEvent = $this->_getRecurBaseEvent($_record);
            $this->_backend->update($baseEvent);
        }
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids) {
        $events = $this->_backend->getMultiple($_ids);
        
        foreach ($events as $event) {
            
            // implicitly delete persistent recur instances of series
            if (! empty($event->rrule)) {
                $exceptionIds = $this->_backend->getMultipleByProperty($event->uid, 'uid')->getId();
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Implicitly deleting ' . (count($exceptionIds) - 1 ) . ' persistent exception(s) for recuring series with uid' . $event->uid);
                $_ids = array_merge($_ids, $exceptionIds);
            }
            
            // deleted persistent recur instances must be added to exdate of the baseEvent
            if (! empty($event->recurid)) {
                $this->createRecurException($event, true);
            }
        }
        
        $this->_deleteAlarmsForIds($_ids);
        
        return array_unique($_ids);
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (    !$this->_doContainerACLChecks 
            // admin grant includes all others
            ||  $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADMIN)) {
            return TRUE;
        }

        switch ($_action) {
            case 'get':
                // NOTE: free/busy is not a read grant!
                $hasGrant = (bool) $_record->readGrant;
                break;
            case 'create':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = (bool) $_record->editGrant;
                break;
            case 'delete':
                $hasGrant = (bool) $_record->deleteGrant;
                break;
        }
        
        if (!$hasGrant) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'No permissions to ' . $_action . ' in container ' . $_record->container_id);
            }
        }
        
        return $hasGrant;
    }
    
    /****************************** attendee functions ************************/
    
    /**
     * sets attendee status for an attendee on the given event
     * 
     * NOTE: for recur events we implicitly create an exceptions on demand
     *
     * @param  Calendar_Model_Event    $_event
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @return Calendar_Model_Attender updated attender
     */
    public function setAttenderStatus($_event, $_attender, $_authKey)
    {
        $eventId = $_event->getId();
        if (! $eventId) {
            if ($_event->recurid) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating recur exception for a exceptional attendee status");
                $this->_doContainerACLChecks = FALSE;
                $event = $this->createRecurException($_event);
                $this->_doContainerACLChecks = TRUE;
            } else {
                throw new Exception("cant set status, invalid event given");
            }
        } else {
            $event = $this->get($eventId);
        }
        
        $currentAttender = $event->attendee[$event->attendee->getIndexById($_attender->getId())];
        $currentAttender->status = $_attender->status;
        
        if ($currentAttender->status_authkey == $_authKey) {
            $updatedAttender = $this->_backend->updateAttendee($currentAttender);
            
            // touch event
            $event = $_event->recurid ? $this->_getRecurBaseEvent($_event) : $this->_backend->get($_event->getId());
            $this->_backend->update($event);
        } else {
            $updatedAttender = $currentAttender;
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no permissions to update status for {$currentAttender->user_type} {$currentAttender->user_id}");
        }
        
        return $updatedAttender;
    }
    
    /**
     * saves all attendee of given event
     * 
     * NOTE: This function is executed in a create/update context. As such the user
     *       has edit/update the event and can do anything besides status settings of attendee
     * 
     * @todo add support for resources
     * 
     * @param Calendar_Model_Event $_event
     */
    protected function _saveAttendee($_event)
    {
        $attendee = $_event->attendee instanceof Tinebase_Record_RecordSet ? 
            $_event->attendee : 
            new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        $attendee->cal_event_id = $_event->getId();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " About to save attendee for event {$_event->id} " .  print_r($attendee->toArray(), true));
        
        $currentAttendee = $this->_backend->getEventAttendee($_event);
        
        $diff = $currentAttendee->getMigration($attendee->getArrayOfIds());
        $this->_backend->deleteAttendee($diff['toDeleteIds']);
        
        $calendar = Tinebase_Container::getInstance()->getContainerById($_event->container_id);
        
        foreach ($attendee as $attender) {
            $attenderId = $attender->getId();
            
            if ($attenderId) {
                $currentAttender = $currentAttendee[$currentAttendee->getIndexById($attenderId)];
                
                $this->_updateAttender($attender, $currentAttender, $calendar);
                
            } else {
                $this->_createAttender($attender, $calendar);
            }
        }
    }

    /**
     * creates a new attender
     * @todo add support for resources
     * 
     * @param Calendar_Model_Attender  $_attender
     * @param Tinebase_Model_Container $_calendar
     */
    protected function _createAttender($_attender, $_calendar) {
        
        // apply default user_type
        $_attender->user_type = $_attender->user_type ?  $_attender->user_type : Calendar_Model_Attender::USERTYPE_USER;
        
        // reset status if user != attender        
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUP
                || $_attender->user_id != Tinebase_Core::getUser()->getId()) {

            $_attender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        // generate auth key
        $_attender->status_authkey = Tinebase_Record_Abstract::generateUID();
        
        // attach to display calendar
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_USER || $_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUPMEMBER) {
            if ($_calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_calendar, Tinebase_Model_Container::GRANT_ADMIN)) {
                // if attender has admin grant to personal phisycal container, this phys. cal also gets displ. cal
                $_attender->displaycontainer_id = $_calendar->getId();
            } else if ($_attender->displaycontainer_id && $_attender->user_id == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_attender->displaycontainer_id, Tinebase_Model_Container::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $_attender->displaycontainer_id = $_attender->displaycontainer_id;
            } else {
                $displayCalId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $_attender->user_id);
                if ($displayCalId) {
                    $_attender->displaycontainer_id = $displayCalId;
                }
            }
        }
        
        $this->_backend->createAttendee($_attender);
    }
    
    /**
     * updates an attender
     * @todo add support for resources
     * 
     * @param Calendar_Model_Attender  $_attender
     * @param Calendar_Model_Attender  $_currentAttender
     * @param Tinebase_Model_Container $_calendar
     */
    protected function _updateAttender($_attender, $_currentAttender, $_calendar) {
        
        // reset status if user != attender
        if ($_currentAttender->user_type == Calendar_Model_Attender::USERTYPE_GROUP 
                || $_currentAttender->user_id != Tinebase_Core::getUser()->getId()) {

            $_attender->status = $_currentAttender->status;
        }
        
        // preserv old authkey
        $_attender->status_authkey = $_currentAttender->status_authkey;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r("updating atternder: " . $_attender->toArray(), true));
        // update display calendar
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_USER || $_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUPMEMBER) {
            if ($_calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_calendar, Tinebase_Model_Container::GRANT_ADMIN)) {
                // if attender has admin grant to personal physical container, this phys. cal also gets displ. cal
                $_attender->displaycontainer_id = $_calendar->getId();
            } else if ($_attender->user_id == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_attender->displaycontainer_id, Tinebase_Model_Container::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $_attender->displaycontainer_id = $_attender->displaycontainer_id;
            } else {
                $_attender->displaycontainer_id = $_currentAttender->displaycontainer_id;
            }
        }
        
        $this->_backend->updateAttendee($_attender);
    }
    
    /****************************** alarm functions ************************/
    
    /**
     * sendAlarm - send an alarm and update alarm status/sent_time/...
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return Tinebase_Model_Alarm
     * 
     * @todo implement
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm) 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " About to send alarm " . print_r($_alarm->toArray(), TRUE)
        );
    }

    /**
     * saves alarm of given event
     * 
     * @param Calendar_Model_Event $_event
     * @return Tinebase_Record_RecordSet
     * 
     * @todo get current alarms of event and delete diffs / update alarms
     */
    protected function _saveAlarms($_event)
    {
        $alarms = $_event->alarms instanceof Tinebase_Record_RecordSet ? 
            $_event->alarms : 
            new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        if (count($alarms) == 0) {
            // no alarms
            return $alarms;
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " About to save " . count($alarms) . " alarms for event {$_event->id} " 
            //.  print_r($alarms->toArray(), true)
        );
        
        /*
        $currentAlarms = $this->_backend->getEventAttendee($_event);
        $diff = $currentAttendee->getMigration($attendee->getArrayOfIds());
        $this->_backend->deleteAttendee($diff['toDeleteIds']);
        */
        
        //$calendar = Tinebase_Container::getInstance()->getContainerById($_event->container_id);
        
        foreach ($alarms as $alarm) {
            $id = $alarm->getId();
            
            if ($id) {
                //$currentAlarm = $currentAttendee[$currentAttendee->getIndexById($attenderId)];
                //$this->_updateAttender($attender, $currentAttender, $calendar);
                
            } else {
                $alarm->record_id = $_event->getId();
                if (! $alarm->model) {
                    $alarm->model = 'Calendar_Model_Event';
                }
                $alarm = Tinebase_Alarm::getInstance()->create($alarm);
            }
        }
        
        return $alarms;
    }

    /**
     * delete alarms for events
     *
     * @param array $_eventIds
     * 
     * @todo implement
     */
    protected function _deleteAlarmsForIds($_eventIds)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Deleting alarms for events " . print_r($_eventIds, TRUE)
        );
    }
}
