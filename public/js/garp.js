/**
 * Garp CMS
 * 
 * Garp.js
 * 
 * Main setup for Garp CMS
   
 * @namespace Garp
 * @copyright (c) 2010 Grrr.nl / eenengelswoord.nl
 * @author Peter
 */
if(Ext.isIE){
	alert('Internet Explorer is not supported by Garp.\n\nPlease login with another browser.');
}
Ext.ns('Garp');
Ext.enableListenerCollection = true;
//Ext.enableNestedListenerRemoval = true; 
Ext.QuickTips.init();
Ext.Direct.addProvider(Garp.API);
Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

Ext.Direct.on({
	'exception': {
		fn: function(e, p){
			if(!this.msg){
				this.msg = '';
			}
			
			var transaction = '', action = '', method = '', message = '', tid = '';
			
			if (Ext.isObject(e)) {
				var message = e.error ? e.error.message : __('No connection');
				var tid = e.tid;
				var transaction = e.getTransaction();
				if (Ext.isObject(transaction)) {
					var action = transaction.action;
					var method = transaction.method;
				}
			}
			
			this.msg = (
				'<b>' + __('Error trying to ') + method + ' ' + (Garp.dataTypes[action] ? '<i>'+__(Garp.dataTypes[action].text)+'</i>' : action) + '</b><br><br>' +
				__('Server response: ') + message + '<br>' +
				__('Transaction id: ') + (tid || __('None'))+
				'<hr>'
			) + this.msg;
			
			Ext.Msg.show({
				title: __('Error'),
				msg: '<div class="garp-error-dialog">' + this.msg + '</div>',
				icon: Ext.Msg.ERROR,
				width: 500,
				buttons: Ext.Msg.OK,
				fn: function(){
					this.msg = '';
				},
				scope: this
			});
		}
	}
});

/**
 * We can override Ext.ux.form.dateTime just now
 * Override it, so that the columnModel doesn't think it got changed. (isDirty fix)
 */
Ext.override(Ext.ux.form.DateTime, {
	getValue: function(){
		return this.dateValue ? this.dateValue.format(this.hiddenFormat) : '';
	}
});
/**
 * Idem
 */
Ext.override(Ext.form.Checkbox,{
	getValue: function(){
		if (this.rendered) {
			return this.el.dom.checked ? "1" : "0";
		}
		return this.checked ? "1" : "0";
	}
});
/**
 * Idem
 */
Ext.override(Ext.form.DateField, {
	format: 'd F Y',
	altFormats: 'Y-m-d|d F Y|j F Y'
});
Ext.apply(Ext.form.TimeField.prototype, {
    altFormats: 'g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|ha|gA|h a|g a|g A|gi|hi|gia|hia|g|H|H:i:s'
});
/**
 * Idem
 */
Ext.apply(Ext.PagingToolbar.prototype, {
	beforePageText: '',
	displayMsg: ''
});
/**
 * Idem
 */
Ext.apply(Ext.ux.form.DateTime.prototype, {
	timeFormat: 'G:i',
	otherToNow: false,
	initDateValue:function() {
        this.dateValue = this.otherToNow ? new Date() : new Date(new Date().getFullYear(), 0, 1, 12, 0, 0);
    },
	timeConfig: {
		increment: 30
	},
	timeWidth: 70
});

Ext.form.VTypes.mailtoOrUrlText = __('Not a valid Url');


	
	window.onbeforeunload = function(){
		if (Garp.checkForModified()) {
			return __('Are you sure you want to navigate away from Garp?');
		} 
	};

	/**
	 * Checks for grid modifications
	 * @return bool true if modified
	 */
	Garp.checkForModified = function(){
		if(Garp.gridPanel && Garp.gridPanel.getStore && Garp.gridPanel.getStore()){
			var count = Garp.gridPanel.getStore().getModifiedRecords().length;
			return count; // > 0;
		}
		return false;
	};
	
	/**
	 * 
	 */
	Garp.getStoreFieldsFromColumnModel = function(cm){
		var fields = [];
		Ext.each(cm, function(col){
			var o = {};
			if(col.dataIndex){
				o.name = col.dataIndex;
			}
			if(col.convert){
				o.convert = col.convert;
			}
			if(col.mapping){
				o.mapping = col.mapping;
			}
			fields.push(o);
		});
		return fields;
	}
	
	/**
	 * Changes the model to a new one. Then rebuild the UI. 
	 * @param {String} model
	 * @param {Bool} overrideURI whether or not to check for URL parameters
	 */
	Garp.changeModel = function(model, overrideURI){
		
		if (typeof Garp.dataTypes[model] == 'undefined') {
			return false;
			//throw ("Unknown model specified.");
		}
		
			if (Garp.checkForModified() > 1  || (Garp.checkForModified() == 1 && !Garp.gridPanel.getSelectionModel().getSelected().phantom)) {
				var store = Garp.gridPanel.getStore();
				Ext.Msg.show({
					animEl: Garp.viewport.getEl(),
					icon: Ext.MessageBox.QUESTION,
					title: __('Garp'),
					msg: __('Would you like to save your changes?'),
					buttons: Ext.Msg.YESNOCANCEL,
					fn: function(btn){
						switch (btn) {
							case 'yes':
								store.on({
									save: {
										single: true,
										fn: function(){
											Garp.changeModel(model, true);
										}
									}
								});
								store.save();
								
								break;
							case 'no':
								store.rejectChanges();
								Garp.changeModel(model, true);
								break;
							case 'cancel':
							default:
								break;
						}
					}
				});
				return;
			}
		
		
		Garp.eventManager.purgeListeners();
		Garp.setupEventManager();
		
		Garp.currentModel = model;
		
		Ext.state.Manager.set('model', model);
		
		Garp.modelMenu.setIconClass(Garp.dataTypes[model].iconCls);
		Garp.modelMenu.setText(__(Garp.dataTypes[model].text));
		if(Garp.viewport.infoPanel){
			Garp.viewport.infoPanel.setInfo(Garp.dataTypes[model]);
		}
		if(Garp.viewport.formPanelCt){
			Garp.viewport.formPanelCt.getLayout().setActiveItem(0);
		}
		if (Garp.gridPanel) {
			Garp.gridPanel.ownerCt.remove(Garp.gridPanel);
		}
		
		if (Garp.formPanel && Garp.formPanel.ownerCt) {
			Garp.formPanel.ownerCt.remove(Garp.formPanel);
		}
		
		Garp.toolbar.removeColumnMenu();
		
		Garp.gridPanel = new Garp.GridPanel({
			model: model,
			listeners: {
				'beforesave': function(){
					Garp.syncValues();
				},
				'rowdblclick' : function(){
					Garp.formPanel.focusFirstField();
				},
				'afterdelete': function(){
					Garp.formPanel.hide();
					Garp.gridPanel.enable();
					
					// force toolbar enable:
					Garp.eventManager.fireEvent('clientvalidation', true, true, true);
				},
				'defocus': function(){
					Garp.formPanel.focusFirstField();
				},
				'storeloaded': {
					fn: function(){
						// add a columnMenu to the Garp toolbar: 
						Garp.toolbar.addColumnMenu(this);
						// Enable this thing. It might be disabled on change:
						this.enable();
					},
					single: true
				},
				'after-save': function(){
					//@TODO: fixme
					//Can this try/catch be done in a better way? 
					try {
						if (window.opener && typeof window.opener.Garp != 'undefined') {
							// window.opener is always true... but window.opener.Garp is not accessible if we didn't open the window ourselves
							window.opener.Garp.eventManager.fireEvent('external-relation-save');
						}
					} 
					catch (e) {
					}
					if (Garp.gridPanel && Garp.formPanel) {
						var sm = Garp.gridPanel.getSelectionModel();
						if (sm && sm.getCount() == 1) {
							// let the form update if we have one record selected.
							// the formpanel listens for gridPanel's  rowselect event.
							// we use it here:
							Garp.gridPanel.fireEvent('rowselect', sm);
						}
					}
				}
			}
		});
		Garp.gridPanel.on({
			'storeloaded': function(){
				Garp.viewport.infoPanel.updateCount(this.getStore().getTotalCount());
				if(!Garp.gridPanel.grid){
					return;
				}
				var sm = Garp.gridPanel.getSelectionModel();
				var selected = sm.getSelections();
				sm.clearSelections();
				sm.selectRecords(selected);
			}
		});
		Garp.formPanel = new Garp.FormPanel({
			previousValidFlag: true,
			listeners: {
				'defocus': Garp.gridPanel.focus.createDelegate(Garp.gridPanel),
				'clientvalidation': function(fp,valid){
					if (valid) {
						Garp.syncValues();
					}
				}
			}
		});
		Garp.gridPanel.relayEvents(Garp.eventManager, ['new', 'save-all', 'delete', 'clientvalidation']);
		Garp.formPanel.relayEvents(Garp.eventManager, ['new', 'rowselect', 'selectionchange']);
		
		Garp.eventManager.relayEvents(Garp.gridPanel, ['beforerowselect', 'rowselect', 'storeloaded', 'selectionchange']);
		Garp.eventManager.relayEvents(Garp.formPanel, ['clientvalidation', 'save-all', 'open-new-window', 'preview']);
		Garp.rebuildViewportItems();
		
		// And fetch them data:
		var query = Ext.urlDecode(window.location.search);
		if(!overrideURI && query['id']){
			Garp.gridPanel.getStore().load({
				params:{
					query:{
						id: decodeURIComponent(query['id'])
					}
				},
				callback: function(){
					//@TODO: Refactor this callback. Put it in Ext.ux.pagingSearchbar?
					
					// select the item to show the formpanel:  
					Garp.gridPanel.getSelectionModel().selectFirstRow();
					
					// show the id in the search bar & update menu to only set 'id' checked 
					var bb = Garp.gridPanel.getTopToolbar();
					var sf = bb.searchField;
					var sm = bb.searchOptionsMenu;
					sf.setValue(query['id']);
					sf.triggers[0].show();
					sf.hasSearch = true;
					sf.fireEvent('change');
					sm.items.each(function(item){
						if (item.setChecked) {
							item.setChecked(item.text == 'id' ? true : false);
						}
					});
					bb.fireEvent('change');
					Garp.formPanel.on({
						'show': function(){
							var tt = new Ext.ToolTip({
								target: sf.triggers[0],
								anchor: 'top',
								anchorOffset: -13,
								html: __('Click here to view all items again'),
								closable: true,
								autoHide: true
							});
							tt.show();
						},
						'single': true,
						'delay': 100
					});
				}
			});
		} else {
			Garp.gridPanel.getStore().load();
		}
		// And force toolbar enable:
		Garp.eventManager.fireEvent('clientvalidation', true, true, true);
		
		// Disable toolbar items if neccesary:
		Garp.toolbar.fileMenu.menu.newButton.setVisible(!Garp.dataTypes[model].disableCreate);
		Garp.toolbar.fileMenu.menu.deleteButton.setVisible(!Garp.dataTypes[model].disableDelete);
		Garp.viewport.doLayout();

		// Preview functionality is depended upon support from the dataType: 
		Garp.formPanel.getTopToolbar().previewButton.setVisible(Ext.isObject(Garp.dataTypes[model].previewLink));
		
		document.title = __(Garp.dataTypes[model].text) + ' | ' + APP_TITLE;
		Garp.setFavicon(Garp.dataTypes[model].iconCls);
	};
	
	/**
	 * setFavicon
	 * @param {String} iconCls (optional, leave blank for Garp favicon)
	 */
	Garp.setFavicon = function(iconCls){
		var d = document;
		var iconCss = Ext.get('icons').dom;
		if (iconCss.sheet && iconCss.sheet.cssRules) {
			var found = false;
			Ext.each(iconCss.sheet.cssRules, function(){
				if (this.selectorText == '.' + iconCls) {
					found = this.style.backgroundImage.trim();
					found = found.substr(8, found.length - 10); // remove url() shizzle
					found = d.location.protocol + '//' + d.location.host + BASE + found; // absolute path
					return;
				}
			});
			if (!found) {
				found = Ext.get('favicon').dom.href;
			}
			var link = d.createElement('link'), old = d.getElementById('dynamic-favicon');
			link.id = 'dynamic-favicon';
			link.rel = 'shortcut icon';
			link.href = found;
			if (old) {
				d.head.removeChild(old);
			}
			d.head.appendChild(link);
		}
	};
	
	/**
	 * Sync the values from the form to the grid.
	 */
	Garp.syncValues = function(){
		// various situations simply require nothing to sync:
		var fp = Garp.formPanel;
		if(fp.isLocked()){
			return;
		}
		var form = fp.getForm();
		if(!form.isValid()){
			fp.getTopToolbar().previewButton.setDisabled(true);
		}
		if (!form.isDirty() || !form.isValid()) {
			return;
		}
		if (Ext.Ajax.isLoading()){
			return;
		}
		//var fields = form.getValues(); 
		//Ext.apply(fields, form.getFieldValues()); // @TODO: @FIXME: getFieldValues() does not contain compositefields. getValues() does not contain checkboxes...
		
		var fields = form.getFieldValues();
		
		var recs = Garp.gridPanel.getSelectionModel().getSelections();
		if (recs.length === 0) {
			return;
		}
		// if we're still here, sync grid from form:
		Garp.gridPanel.suspendEvents();
		Ext.each(recs, function(rec){
			rec.beginEdit();
			for (var field in fields) {
				var ff = form.findField(field);
				if (ff && ff.isDirty() && !ff.disabled) { // sync dirty items only
					rec.set(field, fields[field]);
					// Only "unDirty" dirty fields (except rte fields), so we're not calling form.unDirty() here
					if (ff.xtype === 'richtexteditor' || ff.xtype === 'htmleditor') {
						// Do not let the rte push textarea contents to iframe. It causes the caret to move to the end of the content... 
						ff.on({
							'beforepush': {
								fn: function(){
									return false;
								},
								'single': true
							}
						});
					}
					ff.originalValue = ff.getValue();
				}
			}
			rec.endEdit();
		});
		//Do not refresh. Not sure why this would be needed.
		//Garp.gridPanel.getView().refresh();
		fp.getTopToolbar().previewButton.setDisabled(true);
		Garp.gridPanel.resumeEvents();
	};
	
	/**
	 * (Re-) add the grid and the form to the viewport. 
	 */
	Garp.rebuildViewportItems = function(){
		if (Garp.formPanel) {
			Garp.viewport.formPanelCt.remove(Garp.formPanel);
		}
		if (Garp.gridPanel) {
			Garp.viewport.gridPanelCt.remove(Garp.gridPanel);
		}
		Garp.viewport.gridPanelCt.add(Garp.gridPanel);
		Garp.viewport.formPanelCt.add(Garp.formPanel);
		Garp.viewport.doLayout();
	};
	
	/**
	 * Eventmanager and subscriptions to various events.
	 */
	Garp.setupEventManager = function(){
		Garp.eventManager = new Ext.util.Observable();
		
		Garp.eventManager.addEvents('modelchange', 'beforerowselect', 'rowselect', 'storeloaded', 'new', 'save-all', 'after-save', 'delete','logout','open-new-window','external-relation-save');
		Garp.eventManager.on({
			'modelchange': Garp.changeModel,
			'beforerowselect': Garp.syncValues,
			//'save-all': Garp.syncValues, // this should get called before gridpanel's save method, because grid is not constructed yet. Consequently, it's listener for 'save' is not yet defined
			'clientvalidation': function(fp, valid, force){
				
				if(!Garp.checkForModified() && force !== true){
					return;
				}
				if (fp.previousValidFlag !== valid) {
					Ext.each(['newButton', 'saveButton', 'exportButton'], function(item){
						Garp.toolbar.fileMenu.menu[item].setDisabled(!valid);
						Garp.gridPanel.setDisabled(!valid);
					});
					if (fp.rendered) {
						fp.getTopToolbar().saveButton.setDisabled(!valid);
					}
					
					if (Ext.isObject(fp)) {
						fp.previousValidFlag = valid;
					}
				}
			},
			'open-new-window': function(){
					if(!Garp.formPanel){
						return;
					}
					var id = Garp.formPanel.getForm().findField('id').getValue();
					if (id) {
						var url = BASE + 'admin?' + Ext.urlEncode({
							model: Garp.currentModel,
							id: id
						});
						var win = window.open(url);
					}
			},
			
			'logout': function(){
				window.location = BASE + 'g/auth/logout';
			},
			
			'preview': function(){
				var t = Garp.dataTypes[Garp.currentModel].previewLink;
				var s = Garp.gridPanel.getSelectionModel().getSelected();
				if (s) {
					var tpl = new Ext.Template(t.urlTpl);
					var url = tpl.apply([s.get(t.param)]);
					var win = window.open(BASE + url);
				}
			}
		});
		
		Garp.eventManager.relayEvents(Garp.toolbar, ['logout', 'delete', 'new', 'open-new-window']);
	};
	
	/**
	 * Setup Global Keyboard shortcuts:
	 */
	Garp.setupGlobalKeys = function(){
		Garp.keyMap = new Ext.KeyMap(Ext.getBody(), [{
			key: Ext.EventObject.ENTER,
			ctrl: true,
			handler: function(e){
				Garp.eventManager.fireEvent('save-all');
			}
		},{
			key: Ext.EventObject.DELETE,
			ctrl: true,
			handler: function(e){
				if(Garp.dataTypes[Garp.currentModel].disableDelete){
					return;
				}
				Garp.eventManager.fireEvent('delete');
			}
		},{
			key: 'N',
			ctrl: true,
			handler: function(e){
				if(Garp.dataTypes[Garp.currentModel].disableCreate){
					return;
				}
				Garp.eventManager.fireEvent('new');
			}
		}]);
		Garp.keyMap.stopEvent = true; // prevents browser key handling. 
	};
	
	//
	// @TODO: Refactor Garp.init & Garp.afterInit and Garp.modelChange()
	// 		  It looks kind of odd to first load welcomepanel ...and  
	//		  ... then looking for and possibly loading models
	
	/**
	 * Afterinit
	 * Looks for possible URI or cookie based 'states' to recall; model to be loaded, etc...
	 */
	Garp.afterInit = function(){
		var query = Ext.urlDecode(window.location.search);
		if(query){
			var model = query['?model'] || query['model']; 
		}
		if (!model) {
			model = Ext.state.Manager.get('model');
		}
		if (model) {
			Garp.eventManager.fireEvent('modelchange', model, false);
		}
		setTimeout(function(){
			Ext.select('#app-loader').fadeOut();	
		}, 310);
	};
	
	/**
	 * Set up basic 'ACL'-like behaviour and then fires every model's init event; so custom overrides could take place here
	 */
	Garp.initModels = function(){
		for(var i in Garp.dataTypes){
			var model = Garp.dataTypes[i];
			if (model instanceof Garp.DataType) { // model might actually not be a Garp.dataType model but a '-' separator for the modelMenu
				if (model.setupACL(Garp[i])) {
					model.fireEvent('init');
				} else {
					delete Garp.dataTypes[i];
				}
			}
		}
	};
	
	/**
	 * Init
	 */
	Garp.init = function(){
		Garp.initModels();
		Garp.gridPanel = new Garp.WelcomePanel({}); // temporarily call it Garp.gridPanel, so the viewport doesn't have to reconfigure.
		Garp.modelMenu = new Garp.ModelMenu({});
		Garp.viewport = new Garp.Viewport({});
		Garp.setupEventManager();
		Garp.setupGlobalKeys();
		Garp.afterInit();
	};
	