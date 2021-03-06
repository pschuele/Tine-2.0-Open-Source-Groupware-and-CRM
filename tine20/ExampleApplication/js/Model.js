/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.ExampleApplication.Model');


/**
 * @namespace   Tine.ExampleApplication.Model
 * @class       Tine.ExampleApplication.Model.ExampleRecord
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.ExampleApplication.Model.ExampleRecord = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]), {
    appName: 'ExampleApplication',
    modelName: 'ExampleRecord',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('example record', 'example records', n);
    recordName: 'example record',
    recordsName: 'example records',
    containerProperty: 'container_id',
    // ngettext('example record list', 'example record lists', n);
    containerName: 'example record list',
    containersName: 'example record lists'
});

/**
 * @namespace Tine.ExampleApplication.Model
 * 
 * get default data for a new record
 *  
 * @return {Object} default data
 * @static
 * 
 * TODO generalize default container id handling
 */ 
Tine.ExampleApplication.Model.ExampleRecord.getDefaultData = function() { 
    var app = Tine.Tinebase.appMgr.get('ExampleApplication');
    var defaultsContainer = Tine.ExampleApplication.registry.get('defaultContainer');
    
    return {
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getSelectedContainer('addGrant', defaultsContainer)
        // TODO add more defaults
    };
};

/**
 * default ExampleRecord backend
 */
Tine.ExampleApplication.recordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'ExampleApplication',
    modelName: 'ExampleRecord',
    recordClass: Tine.ExampleApplication.Model.ExampleRecord
});
