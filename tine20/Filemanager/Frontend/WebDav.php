<?php
/**
 * Tine 2.0
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class to handle webdav requests for filemanager
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_WebDav extends Filemanager_Frontend_WebDavNode implements Sabre_DAV_ICollection
{
    public function getChildren() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath);
        
        $children = array();
            
        // top level directory of application
        if ($this->_fileSystemPath == $this->_fileSystemBasePath) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath);
            $children[] = $this->getChild(Tinebase_Model_Container::TYPE_SHARED);
            $children[] = $this->getChild(Tinebase_Model_Container::TYPE_PERSONAL);
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_SHARED) {
            $sharedContainers = Tinebase_Core::getUser()->getSharedContainer($this->_application->name, Tinebase_Model_Grants::GRANT_READ);
            
            foreach ($sharedContainers as $container) {
                $children[] = $this->getChild($container);
            }
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_PERSONAL) {
            $children[] = $this->getChild(Tinebase_Core::getUser());
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountId) {
            $personalContainers = Tinebase_Core::getUser()->getPersonalContainer($this->_application->name, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_READ);
            
            foreach ($personalContainers as $container) {
                $children[] = $this->getChild($container);
            }
        }
        
        return $children;
    }
    
    public function getChild($name) 
    {
        if ($this->_fileSystemPath == $this->_fileSystemBasePath) {
            if ($name != Tinebase_Model_Container::TYPE_SHARED && $name != Tinebase_Model_Container::TYPE_PERSONAL) {
                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path . '/' . $name);
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $name);
            
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_SHARED) {
            try {
                $container = $name instanceof Tinebase_Model_Container ? $name : Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $name, Tinebase_Model_Container::TYPE_SHARED);
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
            }
            
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $container->name);
            
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_PERSONAL) {
            try {
                $user = $name instanceof Tinebase_Model_User ? $name : Tinebase_User::getInstance()->getUserByLoginName($name);
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
            }
            
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $user->accountLoginName);
            
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountId) {
            try {
                $container = $name instanceof Tinebase_Model_Container ? $name : Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $name, Tinebase_Model_Container::TYPE_PERSONAL);
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
            }
            
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $container->name);
            
        } else {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
        }
    }
    
    public function getNodeForPath() 
    {
        if ($this->_container == null) {
            return new Filemanager_Frontend_WebDav($this->_path);
        } elseif (is_dir($this->_fileSystemPath)) {
            return new Filemanager_Frontend_WebDavDirectory($this->_path);
        } else {
            return new Filemanager_Frontend_WebDavFile($this->_path);
        }
    }
    
    public function childExists($name) 
    {
        $path = $this->_fileSystemPath . '/' . $name;
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' exists: ' . $path);
        
        return file_exists($path);
    }

    /**
     * Creates a new file in the directory 
     * 
     * @param string $name Name of the file 
     * @param resource $data Initial payload, passed as a readable stream resource. 
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createFile($name, $data = null) 
    {
        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $path . ')');
    }

    /**
     * Creates a new subdirectory 
     * 
     * @param string $name 
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createDirectory($name) 
    {
        if ($this->_fileSystemPath != $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_SHARED && 
            $this->_fileSystemPath != $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountId) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to create directory');
        };
        
        $containerType = $this->_fileSystemPath == $this->_fileSystemBasePath . '/' . Tinebase_Model_Container::TYPE_SHARED ?  Tinebase_Model_Container::TYPE_SHARED : Tinebase_Model_Container::TYPE_PERSONAL;
        
        if ($containerType == Tinebase_Model_Container::TYPE_SHARED &&
            !Tinebase_Core::getUser()->hasRight($this->_application, Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS)) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to create directory');
        }
        
        try {
            Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $name, $containerType);
            
            // container exists already => that's bad!
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to create directory');
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue
        }
        
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $name,
            'type'           => $containerType,
            'backend'        => 'sql',
            'application_id' => $this->_application->getId()
        )));
        
        $path = $this->_fileSystemPath . '/' . $container->getId();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create directory: ' . $path);
        
        mkdir($path, 0777, true);
    }
    
    /**
     * Deleted the current node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void 
     */
    public function delete() 
    {
        throw new Sabre_DAV_Exception_Forbidden('Permission denied to delete node');
    }
    
    /**
     * Renames the node
     * 
     * @throws Sabre_DAV_Exception_Forbidden
     * @param string $name The new name
     * @return void
     */
    public function setName($name) 
    {
        throw new Sabre_DAV_Exception_Forbidden('Permission denied to rename node');
    }
}