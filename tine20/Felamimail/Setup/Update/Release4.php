<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
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
}
