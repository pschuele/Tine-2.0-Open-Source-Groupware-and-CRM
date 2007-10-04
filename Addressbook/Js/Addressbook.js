Ext.namespace("Egw.Addressbook");Egw.Addressbook=function(){var W;var c=true;var u=false;var p;var o;var P=function(j){switch(p.attributes.datatype){case "list":j.baseParams.listId=p.attributes.listId;j.baseParams.method="Addressbook.getList";break;case "contacts":case "otherpeople":case "sharedaddressbooks":j.baseParams.method="Addressbook.getContacts";j.baseParams.options=Ext.encode({displayContacts:c,displayLists:u});break;case "overview":j.baseParams.method="Addressbook.getOverview";j.baseParams.options=Ext.encode({displayContacts:c,displayLists:u});break;}j.baseParams.query="";};var Y=function(T,j){b("contactWindow","index.php?method=Addressbook.editContact&contactId=",950,600);};var Q=function(T,j){b("listWindow","index.php?method=Addressbook.editList&listId=",450,600);};var N=function(T,j){contactDS.reload();};var A=function(i,j){var R=o.getSelectionModel().getSelections();var T=R[0].id;if(R[0].data.contact_tid=="l"){b("listWindow","index.php?method=Addressbook.editList&listId="+T,450,600);}else{b("contactWindow","index.php?method=Addressbook.editContact&contactId="+T,950,600);}};var M=new Ext.Action({text:"add contact",handler:Y,iconCls:"action_addContact"});var g=new Ext.Action({text:"add list",handler:Q,iconCls:"action_addList"});var Z=new Ext.Action({text:"edit",disabled:true,handler:A,iconCls:"action_edit"});var r=new Ext.Action({text:"delete",disabled:true,handler:N,enableToggle:true,iconCls:"action_delete"});var a=function(){var T=Ext.getCmp("north-panel");var R=Ext.getCmp("applicationToolbar");T.remove(R);var i=new Ext.Action({handler:v,enableToggle:true,pressed:c,iconCls:"x-btn-icon action_displayContacts"});var E=new Ext.Action({handler:s,enableToggle:true,pressed:u,iconCls:"x-btn-icon action_displayLists"});var j=new Ext.Toolbar({region:"south",id:"applicationToolbar",split:false,height:26,items:[M,g,Z,r,"-","Display",": ",i,E,"->","Search:"," "," ",new Ext.app.SearchField({width:240,paramName:"q"})]});T.add(j);T.doLayout();return ;};var n=function(R){p=R;var j=Ext.getCmp("center-panel");if(j.items){for(var T=0;T<j.items.length;T++){j.remove(j.items.get(T));}}W=new Ext.data.JsonStore({url:"index.php",baseParams:{datatype:p.attributes.datatype,owner:p.attributes.owner,sort:"contact_id",dir:"ASC",query:""},root:"results",totalProperty:"totalcount",id:"contact_id",fields:[{name:"contact_id"},{name:"contact_tid"},{name:"contact_owner"},{name:"contact_private"},{name:"cat_id"},{name:"n_family"},{name:"n_given"},{name:"n_middle"},{name:"n_prefix"},{name:"n_suffix"},{name:"n_fn"},{name:"n_fileas"},{name:"contact_bday"},{name:"org_name"},{name:"org_unit"},{name:"contact_title"},{name:"contact_role"},{name:"contact_assistent"},{name:"contact_room"},{name:"adr_one_street"},{name:"adr_one_street2"},{name:"adr_one_locality"},{name:"adr_one_region"},{name:"adr_one_postalcode"},{name:"adr_one_countryname"},{name:"contact_label"},{name:"adr_two_street"},{name:"adr_two_street2"},{name:"adr_two_locality"},{name:"adr_two_region"},{name:"adr_two_postalcode"},{name:"adr_two_countryname"},{name:"tel_work"},{name:"tel_cell"},{name:"tel_fax"},{name:"tel_assistent"},{name:"tel_car"},{name:"tel_pager"},{name:"tel_home"},{name:"tel_fax_home"},{name:"tel_cell_private"},{name:"tel_other"},{name:"tel_prefer"},{name:"contact_email"},{name:"contact_email_home"},{name:"contact_url"},{name:"contact_url_home"},{name:"contact_freebusy_uri"},{name:"contact_calendar_uri"},{name:"contact_note"},{name:"contact_tz"},{name:"contact_geo"},{name:"contact_pubkey"},{name:"contact_created"},{name:"contact_creator"},{name:"contact_modified"},{name:"contact_modifier"},{name:"contact_jpegphoto"},{name:"account_id"}],remoteSort:true});W.setDefaultSort("n_family","asc");W.loadData({"results":[],"totalcount":"0","status":"success"});W.on("beforeload",P);W.load({params:{start:0,limit:50}});var E=new Ext.PagingToolbar({pageSize:25,store:W,displayInfo:true,displayMsg:"Displaying contacts {0} - {1} of {2}",emptyMsg:"No contacts to display"});var S=new Ext.grid.ColumnModel([{resizable:true,id:"contact_tid",header:"Type",dataIndex:"contact_tid",width:30,renderer:z},{resizable:true,id:"n_family",header:"Family name",dataIndex:"n_family"},{resizable:true,id:"n_given",header:"Given name",dataIndex:"n_given"},{resizable:true,id:"n_fn",header:"Full name",dataIndex:"n_fn",hidden:true},{resizable:true,id:"n_fileas",header:"Name + Firm",dataIndex:"n_fileas",hidden:true},{resizable:true,id:"contact_email",header:"eMail",dataIndex:"contact_email",width:150,hidden:false},{resizable:true,id:"contact_bday",header:"Birthday",dataIndex:"contact_bday",hidden:true},{resizable:true,id:"org_name",header:"Organisation",dataIndex:"org_name",width:150},{resizable:true,id:"org_unit",header:"Unit",dataIndex:"org_unit",hidden:true},{resizable:true,id:"contact_title",header:"Title",dataIndex:"contact_title",hidden:true},{resizable:true,id:"contact_role",header:"Role",dataIndex:"contact_role",hidden:true},{resizable:true,id:"contact_room",header:"Room",dataIndex:"contact_room",hidden:true},{resizable:true,id:"adr_one_street",header:"Street",dataIndex:"adr_one_street",hidden:true},{resizable:true,id:"adr_one_locality",header:"Locality",dataIndex:"adr_one_locality",hidden:false},{resizable:true,id:"adr_one_region",header:"Region",dataIndex:"adr_one_region",hidden:true},{resizable:true,id:"adr_one_postalcode",header:"Postalcode",dataIndex:"adr_one_postalcode",hidden:true},{resizable:true,id:"adr_one_countryname",header:"Country",dataIndex:"adr_one_countryname",hidden:true},{resizable:true,id:"adr_two_street",header:"Street (private)",dataIndex:"adr_two_street",hidden:true},{resizable:true,id:"adr_two_locality",header:"Locality (private)",dataIndex:"adr_two_locality",hidden:true},{resizable:true,id:"adr_two_region",header:"Region (private)",dataIndex:"adr_two_region",hidden:true},{resizable:true,id:"adr_two_postalcode",header:"Postalcode (private)",dataIndex:"adr_two_postalcode",hidden:true},{resizable:true,id:"adr_two_countryname",header:"Country (private)",dataIndex:"adr_two_countryname",hidden:true},{resizable:true,id:"tel_work",header:"Phone",dataIndex:"tel_work",hidden:false},{resizable:true,id:"tel_cell",header:"Cellphone",dataIndex:"tel_cell",hidden:false},{resizable:true,id:"tel_fax",header:"Fax",dataIndex:"tel_fax",hidden:true},{resizable:true,id:"tel_car",header:"Car phone",dataIndex:"tel_car",hidden:true},{resizable:true,id:"tel_pager",header:"Pager",dataIndex:"tel_pager",hidden:true},{resizable:true,id:"tel_home",header:"Phone (private)",dataIndex:"tel_home",hidden:true},{resizable:true,id:"tel_fax_home",header:"Fax (private)",dataIndex:"tel_fax_home",hidden:true},{resizable:true,id:"tel_cell_private",header:"Cellphone (private)",dataIndex:"tel_cell_private",hidden:true},{resizable:true,id:"contact_email_home",header:"eMail (private)",dataIndex:"contact_email_home",hidden:true},{resizable:true,id:"contact_url",header:"URL",dataIndex:"contact_url",hidden:true},{resizable:true,id:"contact_url_home",header:"URL (private)",dataIndex:"contact_url_home",hidden:true},{resizable:true,id:"contact_note",header:"Note",dataIndex:"contact_note",hidden:true},{resizable:true,id:"contact_tz",header:"Timezone",dataIndex:"contact_tz",hidden:true},{resizable:true,id:"contact_geo",header:"Geo",dataIndex:"contact_geo",hidden:true}]);S.defaultSortable=true;o=new Ext.grid.GridPanel({store:W,cm:S,tbar:E,autoSizeColumns:false,selModel:new Ext.grid.RowSelectionModel({multiSelect:true}),enableColLock:false,autoExpandColumn:"n_family",border:false});j.add(o);j.show();j.doLayout();o.on("rowclick",function(q,f,V){var i=o.getSelectionModel().getCount();if(i<1){Z.setDisabled(true);r.setDisabled(true);}else{if(i==1){Z.setDisabled(false);r.setDisabled(false);}else{Z.setDisabled(true);r.setDisabled(false);}}});o.on("rowcontextmenu",function(f,q,V){V.stopEvent();var i=f.getStore().getAt(q);m.showAt(V.getXY());});o.on("rowdblclick",function(V,q,f){var i=V.getStore().getAt(q);if(i.data.contact_tid=="l"){try{d(i.data.contact_id,"list");}catch(F){}}else{try{d(i.data.contact_id);}catch(F){}}});return ;textF1=new Ext.form.TextField({height:22,width:200,emptyText:"Suchparameter ...",allowBlank:false});textF1.on("specialkey",function(V,i){if(i.getKey()==i.ENTER||i.getKey()==e.RETURN){contactDS.reload();}});};var z=function(i,S,R,j,T,E){switch(i){case "l":return "<img src='images/oxygen/16x16/actions/users.png' width='12' height='12' alt='list'/>";default:return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";}};var v=function(T,j){c=T.pressed;W.reload();};var s=function(T,j){u=T.pressed;W.reload();};var D=function(S,j){var R=Array();var E=o.getSelectionModel().getSelections();for(var T=0;T<E.length;++T){R.push(E[T].id);}J(R,function(){Egw.Addressbook.reload();});contactDS.reload();};var O=function(i,j){var R=o.getSelectionModel().getSelections();var T=R[0].id;d(T,"list");};var m=new Ext.menu.Menu({id:"ctxMenuAddress",items:[Z,r,"-",M,g]});var U=function(T,j){};var b=function(T,V,j,S){if(document.all){w=document.body.clientWidth;h=document.body.clientHeight;x=window.screenTop;y=window.screenLeft;}else{if(window.innerWidth){w=window.innerWidth;h=window.innerHeight;x=window.screenX;y=window.screenY;}}var E=((w-j)/2)+y;var i=((h-S)/2)+x;var R=window.open(V,T,"width="+j+",height="+S+",top="+i+",left="+E+",directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no");return R;};var d=function(f,R){var T;var F=1024,V=786;var i=950,q=600;if(R=="list"){i=450,q=600;}if(document.all){F=document.body.clientWidth;V=document.body.clientHeight;x=window.screenTop;y=window.screenLeft;}else{if(window.innerWidth){F=window.innerWidth;V=window.innerHeight;x=window.screenX;y=window.screenY;}}var E=((F-i)/2)+y,S=((V-q)/2)+x;if(R=="list"&&!f){T="index.php?method=Addressbook.editList";}else{if(R=="list"&&f){T="index.php?method=Addressbook.editList&contactid="+f;}else{if(R!="list"&&f){T="index.php?method=Addressbook.editContact&contactid="+f;}else{T="index.php?method=Addressbook.editContact";}}}appId="addressbook";var j=window.open(T,"popupname","width="+i+",height="+q+",top="+S+",left="+E+",directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no");return ;};var X=function(j){j=(j==null)?false:j;window.opener.Egw.Addressbook.reload();if(j==true){window.setTimeout("window.close()",400);}};var J=function(T,j,R){var i=Ext.util.JSON.encode(T);new Ext.data.Connection().request({url:"index.php",method:"post",scope:this,params:{method:"Addressbook.deleteContacts",_contactIDs:i},success:function(S,V){var E;try{E=Ext.util.JSON.decode(S.responseText);if(E.success==true){if(typeof j=="function"){j;}}else{Ext.MessageBox.alert("Failure!","Deleting contact failed!");}}catch(f){Ext.MessageBox.alert("Failure!",f.message);}},failure:function(E,S){console.log("failure function called");}});};var l=function(q){var i=Ext.Element.get("iWindowContAdrTag");if(i!=null){i.remove();}var j=Ext.Element.get(document.body);var E=j.createChild({tag:"div",id:"adrContainer"});var V=E.createChild({tag:"div",id:"iWindowAdrTag"});var S=E.createChild({tag:"div",id:"iWindowContAdrTag"});if(!F){var F=new Ext.LayoutDialog("iWindowAdrTag",{modal:true,width:375,height:400,shadow:true,minWidth:375,title:"please select addressbook",minHeight:400,collapsible:false,autoTabs:false,proxyDrag:true,center:{autoScroll:true,tabPosition:"top",closeOnTab:true,alwaysShowTabs:false}});F.addKeyListener(27,F.hide,F);var T=Ext.tree;treeLoader=new T.TreeLoader({dataUrl:"index.php"});treeLoader.on("beforeload",function(H,k){H.baseParams.method="Addressbook.getSubTree";H.baseParams._node=k.id;H.baseParams._datatype=k.attributes.datatype;H.baseParams._owner=k.attributes.owner;H.baseParams._location="selectFolder";},this);var B=new T.TreePanel("iWindowContAdrTag",{animate:true,loader:treeLoader,containerScroll:true,rootVisible:false});var f=new T.TreeNode({text:"root",draggable:false,allowDrop:false,id:"root"});B.setRootNode(f);Ext.each(application,function(k){f.appendChild(new T.AsyncTreeNode(k));});B.render();B.on("click",function(){if(B.getSelectionModel().getSelectedNode()){var H=B.getSelectionModel().getSelectedNode().id;var k=B.getNodeById(H).attributes.owner;if((k>0)||(k<0)){q(k,k);F.hide();}else{Ext.MessageBox.alert("wrong selection","please select a valid addressbook");}}else{Ext.MessageBox.alert("no selection","please select an addressbook");}});var R=F.getLayout();R.beginUpdate();R.add("center",new Ext.ContentPanel("iWindowContAdrTag",{autoCreate:true,fitContainer:true}));R.endUpdate();}F.show();};return {show:function(j){a();n(j);},displayAddressbookSelectDialog:l,reload:function(){contactDS.reload();}};}();Egw.Addressbook.ContactEditDialog=function(){var U;var J;var s;var u;var a;var Z=new Ext.Action({text:"save and close",iconCls:"action_saveAndClose"});var A=new Ext.Action({text:"apply changes",iconCls:"action_applyChanges"});var N=new Ext.Action({text:"delete contact",iconCls:"action_delete"});var d=function(){Ext.QuickTips.init();Ext.form.Field.prototype.msgTarget="side";var r=true;if(formData.values){r=false;}var b=new Ext.Toolbar({region:"south",id:"applicationToolbar",split:false,height:26,items:[Z,A,N]});var l=new Ext.FormPanel({url:"index.php",baseParams:{method:"Addressbook.saveContact"},labelAlign:"top",bodyStyle:"padding:5px",region:"center",id:"contactDialog",tbar:b,items:[{layout:"column",border:false,items:[{columnWidth:0.5,layout:"form",border:false,items:[{xtype:"textfield",fieldLabel:"First Name",name:"first",anchor:"95%"},{xtype:"textfield",fieldLabel:"Company",name:"company",anchor:"95%"}]},{columnWidth:0.5,layout:"form",border:false,items:[{xtype:"textfield",fieldLabel:"Last Name",name:"last",anchor:"95%"},{xtype:"textfield",fieldLabel:"Email",name:"email",vtype:"email",anchor:"95%"}]}]}]});var c=new Ext.Viewport({layout:"border",items:l});return ;var n=new Ext.BorderLayout(document.body,{north:{split:false,initialSize:28},center:{autoScroll:true}});n.beginUpdate();n.add("north",new Ext.ContentPanel("header",{fitToFrame:true}));n.add("center",new Ext.ContentPanel("content"));n.endUpdate();var Q=new Ext.data.JsonStore({url:"index.php",baseParams:{method:"Egwbase.getCountryList"},root:"results",id:"shortName",fields:["shortName","translatedName"],remoteSort:false});var v=new Ext.data.SimpleStore({fields:["id","addressbooks"],data:formData.config.addressbooks});var W=Ext.Element.get("content");a=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveContact",reader:new Ext.data.JsonReader({root:"results"},[{name:"contact_id"},{name:"contact_tid"},{name:"contact_owner"},{name:"contact_private"},{name:"cat_id"},{name:"n_family"},{name:"n_given"},{name:"n_middle"},{name:"n_prefix"},{name:"n_suffix"},{name:"n_fn"},{name:"n_fileas"},{name:"contact_bday"},{name:"org_name"},{name:"org_unit"},{name:"contact_title"},{name:"contact_role"},{name:"contact_assistent"},{name:"contact_room"},{name:"adr_one_street"},{name:"adr_one_street2"},{name:"adr_one_locality"},{name:"adr_one_region"},{name:"adr_one_postalcode"},{name:"adr_one_countryname"},{name:"contact_label"},{name:"adr_two_street"},{name:"adr_two_street2"},{name:"adr_two_locality"},{name:"adr_two_region"},{name:"adr_two_postalcode"},{name:"adr_two_countryname"},{name:"tel_work"},{name:"tel_cell"},{name:"tel_fax"},{name:"tel_assistent"},{name:"tel_car"},{name:"tel_pager"},{name:"tel_home"},{name:"tel_fax_home"},{name:"tel_cell_private"},{name:"tel_other"},{name:"tel_prefer"},{name:"contact_email"},{name:"contact_email_home"},{name:"contact_url"},{name:"contact_url_home"},{name:"contact_freebusy_uri"},{name:"contact_calendar_uri"},{name:"contact_note"},{name:"contact_tz"},{name:"contact_geo"},{name:"contact_pubkey"},{name:"contact_created"},{name:"contact_creator"},{name:"contact_modified"},{name:"contact_modifier"},{name:"contact_jpegphoto"},{name:"account_id"}])});a.on("beforeaction",function(o,X){o.baseParams={};o.baseParams._contactOwner=o.getValues().contact_owner;if(formData.values&&formData.values.contact_id){o.baseParams.contact_id=formData.values.contact_id;}});a.fieldset({legend:"Contact information"});a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"First Name",name:"n_given",width:175}),new Ext.form.TextField({fieldLabel:"Middle Name",name:"n_middle",width:175}),new Ext.form.TextField({fieldLabel:"Last Name",name:"n_family",width:175,allowBlank:false}));var O=new Ext.form.TriggerField({fieldLabel:"Addressbook",name:"contact_owner",width:175,readOnly:true});O.onTriggerClick=function(){Egw.Addressbook.displayAddressbookSelectDialog(D);};a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Prefix",name:"n_prefix",width:175}),new Ext.form.TextField({fieldLabel:"Suffix",name:"n_suffix",width:175}),O);a.end();a.fieldset({legend:"Business information"});a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Company",name:"org_name",width:175}),new Ext.form.TextField({fieldLabel:"Street",name:"adr_one_street",width:175}),new Ext.form.TextField({fieldLabel:"Street 2",name:"adr_one_street2",width:175}),new Ext.form.TextField({fieldLabel:"Postalcode",name:"adr_one_postalcode",width:175}),new Ext.form.TextField({fieldLabel:"City",name:"adr_one_locality",width:175}),new Ext.form.TextField({fieldLabel:"Region",name:"adr_one_region",width:175}),new Ext.form.ComboBox({fieldLabel:"Country",name:"adr_one_countryname",hiddenName:"adr_one_countryname",store:Q,displayField:"translatedName",valueField:"shortName",typeAhead:true,mode:"remote",triggerAction:"all",emptyText:"Select a state...",selectOnFocus:true,width:175}));a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Phone",name:"tel_work",width:175}),new Ext.form.TextField({fieldLabel:"Cellphone",name:"tel_cell",width:175}),new Ext.form.TextField({fieldLabel:"Fax",name:"tel_fax",width:175}),new Ext.form.TextField({fieldLabel:"Car phone",name:"tel_car",width:175}),new Ext.form.TextField({fieldLabel:"Pager",name:"tel_pager",width:175}),new Ext.form.TextField({fieldLabel:"Email",name:"contact_email",vtype:"email",width:175}),new Ext.form.TextField({fieldLabel:"URL",name:"contact_url",vtype:"url",width:175}));a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Unit",name:"org_unit",width:175}),new Ext.form.TextField({fieldLabel:"Role",name:"contact_role",width:175}),new Ext.form.TextField({fieldLabel:"Title",name:"contact_title",width:175}),new Ext.form.TextField({fieldLabel:"Room",name:"contact_room",width:175}),new Ext.form.TextField({fieldLabel:"Name Assistent",name:"contact_assistent",width:175}),new Ext.form.TextField({fieldLabel:"Phone Assistent",name:"tel_assistent",width:175}));a.end();a.fieldset({legend:"Private information"});a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Street",name:"adr_two_street",width:175}),new Ext.form.TextField({fieldLabel:"Street2",name:"adr_two_street2",width:175}),new Ext.form.TextField({fieldLabel:"Postalcode",name:"adr_two_postalcode",width:175}),new Ext.form.TextField({fieldLabel:"City",name:"adr_two_locality",width:175}),new Ext.form.TextField({fieldLabel:"Region",name:"adr_two_region",width:175}),new Ext.form.ComboBox({fieldLabel:"Country",name:"adr_two_countryname",hiddenName:"adr_two_countryname",store:Q,displayField:"translatedName",valueField:"shortName",typeAhead:true,mode:"remote",triggerAction:"all",emptyText:"Select a state...",selectOnFocus:true,width:175}));a.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.DateField({fieldLabel:"Birthday",name:"contact_bday",format:formData.config.dateFormat,altFormats:"Y-m-d",width:175}),new Ext.form.TextField({fieldLabel:"Phone",name:"tel_home",width:175}),new Ext.form.TextField({fieldLabel:"Cellphone",name:"tel_cell_private",width:175}),new Ext.form.TextField({fieldLabel:"Fax",name:"tel_fax_home",width:175}),new Ext.form.TextField({fieldLabel:"Email",name:"contact_email_home",vtype:"email",width:175}),new Ext.form.TextField({fieldLabel:"URL",name:"contact_url_home",vtype:"url",width:175}));a.column({width:"33%",labelSeparator:"",hideLabels:true},new Ext.form.TextArea({name:"contact_note",grow:false,preventScrollbars:false,width:"95%",maxLength:255,height:150}));a.end();var M=new Ext.form.TriggerField({fieldLabel:"Categories",name:"categories",width:320,readOnly:true});M.onTriggerClick=m;a.column({width:"45%",labelWidth:80,labelSeparator:" ",labelAlign:"right"},M);var g=new Ext.form.TriggerField({fieldLabel:"Lists",name:"lists",width:320,readOnly:true});g.onTriggerClick=Y;a.column({width:"45%",labelWidth:80,labelSeparator:" ",labelAlign:"right"},g);a.column({width:"10%",labelWidth:50,labelSeparator:" ",labelAlign:"right"},new Ext.form.Checkbox({fieldLabel:"Private",name:"categories",width:10}));a.render("content");return a;};var P=function(O,n){for(var l in n){var W=O.findField(l);if(W){W.setValue(n[l]);}}};var p=function(O,W){Ext.MessageBox.alert("Export","Not yet implemented.");};var D=function(W,O){a.setValues([{id:"contact_owner",value:O}]);};var m=function(){var c=Ext.Element.get("container");var g=c.createChild({tag:"div",id:"iWindowTag"});var v=c.createChild({tag:"div",id:"iWindowContTag"});var W=new Ext.data.SimpleStore({fields:["category_id","category_realname"],data:[["1","erste Kategorie"],["2","zweite Kategorie"],["3","dritte Kategorie"],["4","vierte Kategorie"],["5","fuenfte Kategorie"],["6","sechste Kategorie"],["7","siebte Kategorie"],["8","achte Kategorie"]]});W.load();ds_checked=new Ext.data.SimpleStore({fields:["category_id","category_realname"],data:[["2","zweite Kategorie"],["5","fuenfte Kategorie"],["6","sechste Kategorie"],["8","achte Kategorie"]]});ds_checked.load();var b=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveAdditionalData",reader:new Ext.data.JsonReader({root:"results"},[{name:"category_id"},{name:"category_realname"},])});var O=1;var Q=new Array();ds_checked.each(function(M){Q[M.data.category_id]=M.data.category_realname;});W.each(function(M){if((O%12)==1){b.column({width:"33%",labelWidth:50,labelSeparator:""});}if(Q[M.data.category_id]){b.add(new Ext.form.Checkbox({boxLabel:M.data.category_realname,name:M.data.category_realname,checked:true}));}else{b.add(new Ext.form.Checkbox({boxLabel:M.data.category_realname,name:M.data.category_realname}));}if((O%12)==0){b.end();}O=O+1;});b.render("iWindowContTag");if(!l){var l=new Ext.LayoutDialog("iWindowTag",{modal:true,width:700,height:400,shadow:true,minWidth:700,minHeight:400,autoTabs:true,proxyDrag:true,center:{autoScroll:true,tabPosition:"top",closeOnTab:true,alwaysShowTabs:true}});l.addKeyListener(27,this.hide);l.addButton("save",function(){Ext.MessageBox.alert("Todo","Not yet implemented!");l.hide;},l);l.addButton("cancel",function(){Ext.MessageBox.alert("Todo","Not yet implemented!");l.hide;},l);var n=l.getLayout();n.beginUpdate();n.add("center",new Ext.ContentPanel("iWindowContTag",{autoCreate:true,title:"Category"}));n.endUpdate();}l.show();};var Y=function(){var c=Ext.Element.get("container");var g=c.createChild({tag:"div",id:"iWindowTag"});var v=c.createChild({tag:"div",id:"iWindowContTag"});var W=new Ext.data.SimpleStore({fields:["list_id","list_realname"],data:[["1","Liste A"],["2","Liste B"],["3","Liste C"],["4","Liste D"],["5","Liste E"],["6","Liste F"],["7","Liste G"],["8","Liste H"]]});W.load();ds_checked=new Ext.data.SimpleStore({fields:["list_id","list_realname"],data:[["2","Liste B"],["5","Liste E"],["6","Liste F"],["8","Liste H"]]});ds_checked.load();var b=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveAdditionalData",reader:new Ext.data.JsonReader({root:"results"},[{name:"list_id"},{name:"list_realname"},])});var O=1;var Q=new Array();ds_checked.each(function(M){Q[M.data.list_id]=M.data.list_realname;});W.each(function(M){if((O%12)==1){b.column({width:"33%",labelWidth:50,labelSeparator:""});}if(Q[M.data.list_id]){b.add(new Ext.form.Checkbox({boxLabel:M.data.list_realname,name:M.data.list_realname,checked:true}));}else{b.add(new Ext.form.Checkbox({boxLabel:M.data.list_realname,name:M.data.list_realname}));}if((O%12)==0){b.end();}O=O+1;});b.render("iWindowContTag");if(!l){var l=new Ext.LayoutDialog("iWindowTag",{modal:true,width:700,height:400,shadow:true,minWidth:700,minHeight:400,autoTabs:true,proxyDrag:true,center:{autoScroll:true,tabPosition:"top",closeOnTab:true,alwaysShowTabs:true}});l.addKeyListener(27,this.hide);l.addButton("save",function(){Ext.MessageBox.alert("Todo","Not yet implemented!");},l);l.addButton("cancel",function(){window.location.reload();l.hide;},l);var n=l.getLayout();n.beginUpdate();n.add("center",new Ext.ContentPanel("iWindowContTag",{autoCreate:true,title:"Lists"}));n.endUpdate();}l.show();};return {display:function(){var W=d();if(formData.values){P(W,formData.values);}}};}();Egw.Addressbook.ListEditDialog=function(){var Y;var a=function(){Ext.QuickTips.init();Ext.form.Field.prototype.msgTarget="side";var n=new Ext.BorderLayout(document.body,{north:{split:false,initialSize:28},center:{split:false,initialSize:70},south:{split:false,initialSize:350,autoScroll:true}});n.beginUpdate();n.add("north",new Ext.ContentPanel("header",{fitToFrame:true}));n.add("center",new Ext.ContentPanel("content",{fitToFrame:true}));n.add("south",new Ext.ContentPanel("south",{fitToFrame:true}));n.endUpdate();var b=true;if(formData.values){b=false;}var Z=new Ext.Toolbar("header");Z.add({id:"savebtn",cls:"x-btn-text-icon",text:"Save and Close",icon:"images/oxygen/22x22/actions/document-save.png",tooltip:"save this list and close window",onClick:function(){if(Y.isValid()){var Q={};if(formData.values){Q.contact_id=formData.values.contact_id;}Y.submit({waitTitle:"Please wait!",waitMsg:"saving contact...",params:Q,success:function(M,r,X){window.opener.Egw.Addressbook.reload();window.setTimeout("window.close()",400);},failure:function(M,r){}});}else{Ext.MessageBox.alert("Errors","Please fix the errors noted.");}}},{id:"savebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/save-all.png",tooltip:"apply changes for this list",onClick:function(){if(Y.isValid()){var Q={};if(formData.values){Q.contact_id=formData.values.contact_id;}Y.submit({waitTitle:"Please wait!",waitMsg:"saving contact...",params:Q,success:function(M,r,X){window.opener.Egw.Addressbook.reload();},failure:function(M,r){}});}else{Ext.MessageBox.alert("Errors","Please fix the errors noted.");}}},{id:"deletebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/edit-delete.png",tooltip:"delete this contact",disabled:b,handler:function(M,Q){if(formData.values.contact_id){Ext.MessageBox.wait("Deleting contact...","Please wait!");_deleteContact([formData.values.contact_id]);_reloadMainWindow(true);}}});var v=new Ext.data.SimpleStore({fields:["id","addressbooks"],data:formData.config.addressbooks});var D=Ext.Element.get("content");Y=new Ext.form.Form({labelWidth:75,url:"index.php",baseParams:{method:"Addressbook.saveList"}});Y.on("beforeaction",function(M,Q){M.baseParams._listOwner=M.getValues().list_owner;M.baseParams._listmembers=s(u);if(formData.values&&formData.values.list_id){M.baseParams._listId=formData.values.list_id;}else{M.baseParams._listId="";}});var W=new Ext.form.TriggerField({fieldLabel:"Addressbook",name:"list_owner",width:325,readOnly:true});W.onTriggerClick=function(){Egw.Addressbook.displayAddressbookSelectDialog(m);};Y.fieldset({legend:"list information"});Y.column({width:"100%",labelWidth:90,labelSeparator:""},W,new Ext.form.TextField({fieldLabel:"List Name",name:"list_name",width:325}),new Ext.form.TextArea({fieldLabel:"List Description",name:"list_description",width:325,grow:false}));Y.end();if(formData.values){var N=formData.values.list_owner;var g=formData.values.list_id;}else{var N=-1;var g=-1;}searchDS=new Ext.data.JsonStore({url:"index.php",baseParams:{method:"Addressbook.getOverview",owner:N,options:"{\"displayContacts\":true,\"displayLists\":false}",},root:"results",totalProperty:"totalcount",id:"contact_id",fields:[{name:"contact_id"},{name:"n_family"},{name:"n_given"},{name:"contact_email"}],remoteSort:true,success:function(Q,M){},failure:function(Q,M){}});searchDS.setDefaultSort("n_family","asc");var O=new Ext.Template("<div class=\"search-item\">","{n_family}, {n_given} {contact_email}","</div>");var A=new Ext.form.ComboBox({store:searchDS,displayField:"n_family",typeAhead:false,loadingText:"Searching...",width:415,pageSize:10,hideTrigger:true,tpl:O,onSelect:function(M){var Q=new J({contact_id:M.data.contact_id,n_family:M.data.n_family,contact_email:M.data.contact_email});u.add(Q);u.sort("n_family");A.reset();A.collapse();}});A.on("specialkey",function(T,r){if(searchDS.getCount()==0){var j=/^[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4}|museum)$/;var M=j.exec(A.getValue());if(M&&(r.getKey()==r.ENTER||r.getKey()==e.RETURN)){var z=A.getValue();var Q=z.indexOf("@");if(Q!=-1){var o=Ext.util.Format.capitalize(z.substr(0,Q));}else{var o=z;}var X=new J({contact_id:"-1",n_family:o,contact_email:z});u.add(X);u.sort("n_family");A.reset();}}});Y.fieldset({legend:"select new list members"});Y.column({width:"100%",labelWidth:0,labelSeparator:""},A);Y.end();Y.render("content");var J=Ext.data.Record.create([{name:"contact_id",type:"int"},{name:"n_family",type:"string"},{name:"contact_email",type:"string"}]);if(formData.values){var P=formData.values.list_members;}var u=new Ext.data.SimpleStore({fields:["contact_id","n_family","contact_email"],data:P});u.sort("n_family","ASC");var l=new Ext.grid.ColumnModel([{resizable:true,id:"n_family",header:"Family name",dataIndex:"n_family"},{resizable:true,id:"contact_email",header:"eMail address",dataIndex:"contact_email"}]);l.defaultSortable=true;var c=new Ext.menu.Menu({id:"ctxListMenu",items:[{id:"delete",text:"delete entry",icon:"images/oxygen/16x16/actions/edit-delete.png",handler:p}]});var U=new Ext.grid.Grid("south",{ds:u,cm:l,selModel:new Ext.grid.RowSelectionModel({multiSelect:true}),autoSizeColumns:true,monitorWindowResize:false,trackMouseOver:true,contextMenu:"ctxListMenu",autoExpandColumn:"contact_email"});U.on("rowcontextmenu",function(r,X,M){M.stopEvent();var Q=r.getDataSource().getAt(X);if(Q.data.contact_tid=="l"){c.showAt(M.getXY());}else{c.showAt(M.getXY());}});U.render("south");return Y;};var m=function(u,N){Y.setValues([{id:"list_owner",value:N}]);};var p=function(D,u){var P=Array();var U=listGrid.getSelectionModel().getSelections();for(var N=0;N<U.length;++N){ds_listMembers.remove(U[N]);}};var d=function(u,N){u.findField("list_name").setValue(N["list_name"]);u.findField("list_description").setValue(N["list_description"]);u.findField("list_owner").setValue(N["list_owner"]);};var s=function(N){var u=new Array();N.each(function(P){u.push(P.data);},this);return Ext.util.JSON.encode(u);};return {display:function(){var u=a();if(formData.values){d(u,formData.values);}}};}();