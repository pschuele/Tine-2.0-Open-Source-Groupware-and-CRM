/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @version     $Id$
 */
Tine.Filemanager.Application = Ext.extend(Tine.Tinebase.Application, {
    
    hasMainScreen: false,
    
    /**
     * Get translated application title of this application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Filemanager');
    }
});
