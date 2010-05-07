/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.persistentfilter');

/**
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.widgets.persistentfilter.PickerPanel
 * @extends     Ext.tree.TreePanel
 * 
 * <p>PersistentFilter Picker Panel</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.persistentfilter.PickerPanel
 */
Tine.widgets.persistentfilter.PickerPanel = Ext.extend(Ext.tree.TreePanel, {
    
    /**
     * @cfg {application}
     */
    app: null,
    /**
     * @cfg {String} filterMountId
     * mount point of persitant filter folder
     */
    filterMountId: '/',
    
    /**
     * @private
     */
    autoScroll: true,
    border: false,
    rootVisible: false,

    /**
     * returns persistent filter tree node
     * 
     * @return {Ext.tree.AsyncTreeNode}
     */
    getPersistentFilterNode: function() {
        return this.filterNode;
    },
    
    /**
     * @private
     */
    initComponent: function() {
        this.loader = new Tine.widgets.persistentfilter.PickerTreePanelLoader({
            app: this.app
        });
        
        if (! this.root) {
            this.root = new Ext.tree.TreeNode({
                id: '/',
                leaf: false,
                expanded: true
            });
        }
        
        Tine.widgets.persistentfilter.PickerPanel.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter) {
                node.select();
                this.onFilterSelect(node.attributes.filter);
            } else if (node.id == '_persistentFilters') {
                node.expand();
                return false;
            }
        }, this);
        
        this.on('contextmenu', this.onContextMenu, this);
    },
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.widgets.persistentfilter.PickerPanel.superclass.afterRender.call(this);
        
        this.filterNode = new Ext.tree.AsyncTreeNode({
            text: _('My saved filters'),
            id: '_persistentFilters',
            leaf: false,
            expanded: true
        });
        
        this.getNodeById(this.filterMountId).appendChild(this.filterNode);
        
        /*
        if (this.filterMountId === '/') {
            this.expandPath('///_persistentFilters');
        }
        */
    },
    
    /**
     * load grid from saved filter
     * 
     * NOTE: As all filter plugins add their data on the stores beforeload event
     *       we need a litte hack to only present a filterid.
     *       
     *       When a filter is selected, we register ourselve as latest beforeload,
     *       remove all filter data and paste our filter id. To ensure we are
     *       always the last listener, we directly remove the listener afterwards
     */
    onFilterSelect: function(filter) {
        var store = this.app.getMainScreen().getCenterPanel().getStore();
        
        // NOTE: this can be removed when all instances of filterplugins are removed
        store.on('beforeload', this.storeOnBeforeload, this);
        store.load();
        
        //this.app.getMainScreen().getCenterPanel().filterToolbar.setValue(filter.filters);
        
        if (typeof this.app.getMainScreen().getContainerTreePanel().activate == 'function') {
            this.app.getMainScreen().getContainerTreePanel().activate(0);
        }
    },
    
    storeOnBeforeload: function(store, options) {
        options.params.filter = this.getSelectionModel().getSelectedNode().attributes.filter.filters;
        store.un('beforeload', this.storeOnBeforeload, this);
    },
    
    /**
     * returns additional ctx items
     * 
     * @TODO: make this a hooking approach!
     * 
     * @return {Array}
     */
    getAdditionalCtxItems: function(filter) {
        var items = [];
        
        var as = Tine.Tinebase.appMgr.get('ActiveSync');
        if (as) {
            items = items.concat(as.getPersistentFilterPickerCtxItems(this, filter));
        }
        
        return items;
    },
    
    onContextMenu: function(node, e) {
        if (! node.attributes.isPersistentFilter) {
            return;
        }
        
        var menu = new Ext.menu.Menu({
            items: [{
                text: _('Delete Filter'),
                iconCls: 'action_delete',
                scope: this,
                handler: function() {
                    Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the Filter "{0}"?'), node.text), function(_btn){
                        if ( _btn == 'yes') {
                            Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting Filter "{0}"' ), this.containerName , node.text));
                            
                            Ext.Ajax.request({
                                params: {
                                    method: 'Tinebase_PersistentFilter.delete',
                                    filterId: node.id
                                },
                                scope: this,
                                success: function(){
                                    node.unselect();
                                    node.remove();
                                    Ext.MessageBox.hide();
                                }
                            });
                        }
                    }, this);
                }
            }, {
                text: _('Rename Filter'),
                iconCls: 'action_edit',
                scope: this,
                handler: function() {
                    Ext.MessageBox.prompt(_('New Name'), String.format(_('Please enter the new name for filter "{0}"?'), node.text), function(_btn, _newName){
                        if ( _btn == 'ok') {
                            Ext.MessageBox.wait(_('Please wait'), String.format(_('Renaming Filter "{0}"' ), this.containerName , node.text));
                            
                            Ext.Ajax.request({
                                params: {
                                    method: 'Tinebase_PersistentFilter.rename',
                                    filterId: node.id,
                                    newName: _newName
                                },
                                scope: this,
                                success: function(response) {
                                    var pfilter = Ext.decode(response.responseText);
                                    node.setText(_newName);
                                    node.attributes.filter = pfilter;
                                    Ext.MessageBox.hide();
                                }
                            });
                        }
                    }, this, false, node.text);
                }
            }].concat(this.getAdditionalCtxItems(node.attributes.filter))
        });
        menu.showAt(e.getXY());
    }
});

/**
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.widgets.persistentfilter.PickerTreePanelLoader
 * @extends     Tine.widgets.tree.Loader
 * 
 * <p>PersistentFilter Picker Panel Tree Loader</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.persistentfilter.PickerTreePanelLoader
 */
Tine.widgets.persistentfilter.PickerTreePanelLoader = Ext.extend(Tine.widgets.tree.Loader, {
    
    /**
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Function} callback Function to call after the node has been loaded. The
     * function is passed the TreeNode which was requested to be loaded.
     * @param (Object) scope The cope (this reference) in which the callback is executed.
     * defaults to the loaded TreeNode.
     */
    requestData : function(node, callback, scope) {
        if(this.fireEvent("beforeload", this, node, callback) !== false) {
            var store = Tine.widgets.persistentfilter.store.getPersistentFilterStore();
            
            var recordCollection = store.query('application_id', this.app.id);
            
            node.beginUpdate();
            recordCollection.each(function(record) {
                var n = this.createNode(record.data);
                if (n) {
                    node.appendChild(n);
                }
            }, this);
            node.endUpdate();
            
            this.runCallback(callback, scope || node, [node]);

        }  else {
            // if the load is cancelled, make sure we notify
            // the node that we are done
            this.runCallback(callback, scope || node, []);
        }
    },
    
    inspectCreateNode: function(attr) {
        var isPersistentFilter = !!attr.model && !!attr.filters;
        
        if (isPersistentFilter) {
            Ext.apply(attr, {
                isPersistentFilter: isPersistentFilter,
                text: Ext.util.Format.htmlEncode(attr.name),
                id: attr.id,
                leaf: attr.leaf === false ? attr.leaf : true,
                filter: Ext.copyTo({}, attr, 'id, name, filters')
            });
        }
    }
});