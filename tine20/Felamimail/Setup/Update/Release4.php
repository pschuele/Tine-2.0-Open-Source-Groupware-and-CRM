<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Felamimail updates for version 4.x
 *
 * @package     Felamimail
 * @subpackage  Setup
 */
class Felamimail_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * update to 4.1
     * - add keys for timestamp and received
     */    
    public function update_0()
    {
        if ($this->getTableVersion('felamimail_cache_message') < 6) {
            $declaration = new Setup_Backend_Schema_Index_Xml(
                '<index>
                    <name>received</name>
                    <field>
                        <name>received</name>
                    </field>
                </index>'
            );
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
            
            $declaration = new Setup_Backend_Schema_Index_Xml(
                '<index>
                    <name>timestamp</name>
                    <field>
                        <name>timestamp</name>
                    </field>
                </index>'
            );
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
            $this->setTableVersion('felamimail_cache_message', '6');
        }
        
        $this->setApplicationVersion('Felamimail', '4.1');
    }
    
    /**
     * update to 4.2
     * - add foreign key for account_id
     */    
    public function update_1()
    {
        if ($this->getTableVersion('felamimail_folder') < 6) {
            
            // remove folder for which no account exists
            $select = $this->_db->select()
                ->from(array('felamimail_folder' => SQL_TABLE_PREFIX . 'felamimail_folder'), array('DISTINCT(account_id)'))
                ->joinLeft(array('felamimail_account' => SQL_TABLE_PREFIX . 'felamimail_account'), 'felamimail_account.id = felamimail_folder.account_id', array())
                ->where('felamimail_account.id IS NULL');
                
            $result = $this->_db->fetchAll($select);
            
            foreach ($result as $row) {
                $where = array(
                    $this->_db->quoteInto('account_id = ?', $row['account_id'])
                );
                $this->_db->delete(SQL_TABLE_PREFIX . 'felamimail_folder', $where);
            }
            
            // add foreign key
            $declaration = new Setup_Backend_Schema_Index_Xml(
                '<index>
                    <name>felamimail_folder::account_id--felamimail_account::id</name>
                    <field>
                        <name>account_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>felamimail_account</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                        <onupdate>cascade</onupdate>
                    </reference>
                </index>'
            );
            $this->_backend->addForeignKey('felamimail_folder', $declaration);
            
            $this->setTableVersion('felamimail_folder', '6');
        }
        
        $this->setApplicationVersion('Felamimail', '4.2');
    }
    
    /**
     * update to 4.3
     * - reverse key order
     */    
    public function update_2()
    {
        if ($this->getTableVersion('felamimail_cache_message') < 7) {
            
            $this->_backend->dropIndex('felamimail_cache_message', 'messageuid-folder_id');
            
            // add foreign key
            $declaration = new Setup_Backend_Schema_Index_Xml(
                '<index>
                    <name>folder_id-messageuid</name>
                    <unique>true</unique>
                    <field>
                        <name>folder_id</name>
                    </field>
                    <field>
                        <name>messageuid</name>
                    </field>
                </index>'
            );
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
            
            $this->setTableVersion('felamimail_cache_message', '7');
        }
        
        $this->setApplicationVersion('Felamimail', '4.3');
    }

    /**
     * update to 4.4
     * - remove uidnext cols
     */    
    public function update_3()
    {
        $this->_backend->dropCol('felamimail_folder', 'imap_uidnext');
        $this->_backend->dropCol('felamimail_folder', 'cache_uidnext');
        
        $this->setTableVersion('felamimail_folder', '7');
        $this->setApplicationVersion('Felamimail', '4.4');
    }

    /**
     * update to 4.5
     * - remove user_id col
     */    
    public function update_4()
    {
        $this->_backend->dropCol('felamimail_folder', 'user_id');
        
        $this->setTableVersion('felamimail_folder', '8');
        $this->setApplicationVersion('Felamimail', '4.5');
    }
}
