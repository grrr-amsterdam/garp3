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
	
	window.onbeforeunload = function(){
		if (Garp.checkForModified()) {
			return __('Are you sure you want to navigate away from Garp?');
		} 
	};

	/**
	 * Checks for grid modifications
	 * @return number records modified
	 */
	Garp.checkForModified = function(){
		if(Garp.gridPanel && Garp.gridPanel.getStore && Garp.gridPanel.getStore()){
			var count = Garp.gridPanel.getStore().getModifiedRecords().length;
			return count; // > 0;
		}
		return false;
	};
	
	/**
	 * util function
	 */
	Garp.lazyLoad = function(url, cb){
		var cbId = Ext.id(null, 'garp');
		window['cb' + cbId] = cb.createDelegate(this).createSequence(function(){
			delete window['cb' + cbId];
		}, this);
		var script = document.createElement("script");
		script.type = "text/javascript";
		script.src = url + "&callback=cb" + cbId;
		document.body.appendChild(script);
	};
	
	/**
	 * UI dirty
	 */
	Garp.dirty = function(){
		Garp.gridPanel.disable();
		Garp.toolbar.disable();
	};
	
	/**
	 * UI undirty
	 */
	Garp.undirty = function(){
		Garp.gridPanel.enable();
		Garp.toolbar.enable();
	};
	
	
	/**
	 * @function updateUI
	 * @param {object} selectionModel
	 * Gets called whenever a selection change occurs. Reflect the UI:
	 * 
	 * 0 items selected: hide  
	 * 1 item : show
	 * 2 items: show & display mode Indicator
	 */
	Garp.updateUI =function(sm){
		if (!sm || !sm.getCount) {
			if(!Garp.gridPanel){
				return;
			}
			sm = Garp.gridPanel.getSelectionModel();
		}
		var count = sm.getCount();
		if(count > 1){
			count = 2;
		}
		if (Garp.updateUI.prevCount && Garp.updateUI.prevCount == count) {
			return;
		}
		switch (count) {
			case 1: // single mode
				//Garp.viewport.formPanelCt.doLayout();
				Garp.viewport.formPanelCt.getLayout().setActiveItem(1);
				Garp.formPanel.show();
				break;
			case 0: //default: // no items selected, or multiple items:
				Garp.viewport.formPanelCt.getLayout().setActiveItem(0);
				Garp.viewport.infoPanel.setInfo(Garp.dataTypes[Garp.currentModel]);
				Garp.viewport.formPanelCt.doLayout();
				Garp.viewport.infoPanel.updateCount(Garp.gridPanel.getStore().getTotalCount());
				break;
		}
		Garp.updateUI.prevCount = count;
		return true;
	};
	
	/**
	 * Simple singleton for managing state 
	 */
	Garp.history = Ext.apply(Garp.history || {}, {
	
		pastModel: null,
		
		pushState: function(state){
			if (!state) {
				state = this.getCurrentState();
			}
			if (state && history.pushState) {
				if (state.model !== Garp.history.pastModel) {
					history.pushState(state, '' + __(Garp.dataTypes[state.model].text || ''), BASE + 'admin/?' + Ext.urlEncode(state));
				} else {
					history.replaceState(state, '' + __(Garp.dataTypes[state.model].text || ''), BASE + 'admin/?' + Ext.urlEncode(state));
				}
				Garp.history.pastModel = state.model;
			}
		},
		
		getCurrentState: function(){
			if (Garp.gridPanel && Garp.gridPanel.store) {
				var state = {};
				var pt = Garp.gridPanel.getBottomToolbar();
				var sm = Garp.gridPanel.getSelectionModel();
				state.page = Math.ceil((pt.cursor + pt.pageSize) / pt.pageSize) || null;
				state.id = sm.getSelected() ? parseInt(sm.getSelected().get('id'), 10) || null : null;
				state.model = Garp.currentModel;
				return state;
			} else {
				return null;
			}
		},
		
		parseState: function(state){
			if (!state) {
				state = Ext.urlDecode(document.location.search.replace(/\?/, ''));
			}
			if (state.model) {
				Garp.eventManager.fireEvent('modelchange', false, state.model || null, state.page || null, state.id || null);
			}
		},
		
		setupListeners: function(){
			var scope = this;
			window.addEventListener('popstate', function(e){
				if(!e || !e.state){
					return;
				}
				scope.parseState(e.state ? e.state : e.originalEvent.state ? e.originalEvent.state : null);
			});
		}
	});
	
	/**
	 * Changes the model to a new one. Then rebuild the UI.
	 * @param {Bool} true to save state.
	 * @param {String} model
	 * //@param {Bool} overrideURI whether or not to check for URL parameters
	 * @param {Number} page
	 * @param {Number} id
	 */
	Garp.changeModel = function(doPushState, model, page, id){
	
		if (typeof Garp.dataTypes[model] == 'undefined') {
			return false;
			//throw ("Unknown model specified.");
		}
		
		if (Garp.checkForModified() > 1 || (Garp.checkForModified() == 1 && !Garp.gridPanel.getSelectionModel().getSelected().phantom)) {
			var store = Garp.gridPanel.getStore();
			var state = Garp.getCurrentState();
			
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
										Garp.changeModel(doPushState, model, page, id);
									}
								}
							});
							store.save();
							
							break;
						case 'no':
							store.rejectChanges();
							Garp.changeModel(doPushState, model, page, id);
							break;
						//case 'cancel':
						default:
							break;
					}
				}
			});
			return;
		}
		Garp.infoPanel.clearInfo();

		Garp.eventManager.purgeListeners();
		Garp.setupEventManager();
		
		Garp.currentModel = model;
		
		if (doPushState) {
			Garp.history.pushState({
				model: model
			});
		}		
		
		Garp.modelMenu.setIconClass(Garp.dataTypes[model].iconCls);
		Garp.modelMenu.setText(__(Garp.dataTypes[model].text));
		if (Garp.gridPanel) {
			Garp.gridPanel.ownerCt.remove(Garp.gridPanel);
		}
		
		if (Garp.formPanel && Garp.formPanel.ownerCt) {
			Garp.viewport.formPanelCt.getLayout().setActiveItem(0);
			Garp.viewport.formPanelCt.remove(Garp.formPanel);
		}
		
		Garp.formPanel = new Garp.FormPanel({
			previousValidFlag: true,
			listeners: {
				'cancel': function(){
					// reselect item to revert formPanel contents:
					var s = Garp.gridPanel.getSelectionModel().getSelected(); 
					Garp.gridPanel.getSelectionModel().clearSelections();
					Garp.gridPanel.getSelectionModel().selectRecords([s]);
				},
				'defocus': function(){
					Garp.gridPanel.focus.call(Garp.gridPanel);
				},
				'dirty': Garp.dirty,
				'undirty': Garp.undirty
			}
		});
		Garp.viewport.formPanelCt.add(Garp.formPanel);
		Garp.viewport.formPanelCt.doLayout();
		
		Garp.gridPanel = new Garp.GridPanel({
			model: model,
			listeners: {
				'beforesave': function(){
					Garp.syncValues();
				},
				'rowdblclick': function(){
					Garp.formPanel.focusFirstField();
				},
				'afterdelete': function(){
					Garp.formPanel.hide();
					Garp.gridPanel.enable();
					Garp.undirty();
				},
				'defocus': function(){
					Garp.formPanel.focusFirstField();
				},
				'storeloaded': {
					fn: function(){
						this.enable();
						if (document.location.hash == '#selectfirst') {
							Garp.gridPanel.getSelectionModel().selectFirstRow();
						}
						// wait for store load before pushing history actions. It might just be that (previous) state we loaded from!
						Garp.gridPanel.on('rowselect', function(){
							Garp.history.pushState();
						}, this, {
							buffer: 500
						});
					},
					buffer: 300,
					delay: 300,
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
				}
			}
		});
		Garp.viewport.gridPanelCt.add(Garp.gridPanel);
		Garp.viewport.gridPanelCt.doLayout();
		
		//Garp.rebuildViewportItems();
		
		Garp.gridPanel.on({
			'storeloaded': function(){
				Garp.updateUI.prevCount = -1;
				Garp.updateUI();
				if (!Garp.gridPanel.grid) {
					return;
				}
				var sm = Garp.gridPanel.getSelectionModel();
				var selected = sm.getSelections();
				sm.clearSelections();
				sm.selectRecords(selected);
			},
			'selectionchange': {
				fn: Garp.updateUI,
				buffer: 110
			}
		});
		
		Garp.gridPanel.relayEvents(Garp.eventManager, ['new', 'save-all', 'delete', 'clientvalidation']);
		Garp.formPanel.relayEvents(Garp.eventManager, ['new', 'rowselect', 'after-save']);
		
		Garp.eventManager.relayEvents(Garp.gridPanel, ['beforerowselect', 'rowselect', 'storeloaded', 'after-save', 'selectionchange', 'open-new-window']);
		Garp.eventManager.relayEvents(Garp.formPanel, ['clientvalidation', 'save-all', 'open-new-window', 'preview', 'delete']);
		
		Garp.infoPanel.clearInfo();

		// And fetch them data:
		// var query = Ext.urlDecode(window.location.search);
		if (id && !page) {
			Garp.gridPanel.getStore().load({
				params: {
					query: {
						id: id
					}
				},
				callback: function(){
					// select the item to show the formpanel:  
					Garp.gridPanel.getSelectionModel().selectFirstRow();
					Garp.gridPanel.getTopToolbar().searchById(id); // only visually set the UI as if searched, no real DB call.
					Garp.formPanel.on({
						'show': function(){
							var tt = new Ext.ToolTip({
								target: Garp.gridPanel.getTopToolbar().items.get(1).triggers[0],
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
		} else if(page){
			
			// @TODO: Find out if we can do this better. Two loads is a bit awkward!
			Garp.gridPanel.getStore().on({
				load: function(){
					if (id) {
						Garp.gridPanel.getStore().on({
							'load': function(){
								var idx = Garp.gridPanel.getStore().find('id', id);
								if (idx > -1) {
									var rec = Garp.gridPanel.getStore().getAt(idx);
									Garp.gridPanel.getSelectionModel().selectRecords([rec]);
								}
							},
							scope: this,
							single: true
						});
					}
					Garp.gridPanel.getBottomToolbar().changePage(page);
				},
				single: true
			});
			Garp.gridPanel.getStore().load();
		} else {
			Garp.gridPanel.getStore().load();	
		}
		
		// Disable toolbar items if neccesary:
		var tb = Garp.toolbar;
		tb.newButton.setVisible(!Garp.dataTypes[model].disableCreate);
		tb.deleteButton.setVisible(!Garp.dataTypes[model].disableDelete);
		tb.separator.setVisible(!Garp.dataTypes[model].disableDelete || !Garp.dataTypes[model].disableCreate);
		tb.extraMenu.menu.importButton.show();
		tb.extraMenu.menu.exportButton.show();
		tb.extraMenu.menu.printButton.show();
		
		document.title = __(Garp.dataTypes[model].text) + ' | ' + (typeof APP_TITLE != 'undefined' ? APP_TITLE : '');
		Garp.setFavicon(Garp.dataTypes[model].iconCls);
		
	};
	
	/*
	Garp.changePage = function(page, id){
		
		if(!Garp.gridPanel || !Garp.gridPanel.store){
			return;
		}
		
		var pt = Garp.gridPanel.getBottomToolbar();
		var sm = Garp.gridPanel.getSelectionModel();
		currentPage = Math.ceil((pt.cursor + pt.pageSize) / pt.pageSize) || null;
		
		function selectId(){
			var idx = Garp.gridPanel.getStore().find('id', id);
			if (idx) {
				var rec = Garp.gridPanel.getStore().getAt(idx);
				sm.selectRecords([rec]);
			}
		}
		
		if (page && page != currentPage) {
			if (id) {
				Garp.gridPanel.getStore().on({
					'load': selectId,
					single: true
				});
			}
			pt.changePage(page);
		} else if (id) {
			selectId();
		}
		
	};
	*/
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
		var fp = Garp.formPanel;
		fp.getForm().updateRecord(fp.rec);
	};
	
	/**
	 * (Re-) add the grid and the form to the viewport. 
	 */
	Garp.rebuildViewportItems = function(){
		if(Garp.infoPanel){
			Garp.infoPanel.clearInfo();
		}
		if (Garp.gridPanel) {
			Garp.viewport.gridPanelCt.remove(Garp.gridPanel);
		}
		if (Garp.formPanel) {
			Garp.viewport.formPanelCt.remove(Garp.formPanel);
		}
		Garp.viewport.formPanelCt.add(Garp.formPanel);
		//Garp.viewport.formPanelCt.getLayout().setActiveItem(1);
		Garp.formPanel.hide();	
		Garp.viewport.gridPanelCt.add(Garp.gridPanel);
		Garp.viewport.gridPanelCt.doLayout();
	};
	
	/**
	 * Eventmanager and subscriptions to various events.
	 */
	Garp.setupEventManager = function(){
		Garp.eventManager = new Ext.util.Observable();
		
		Garp.eventManager.addEvents('modelchange', 'beforerowselect', 'rowselect', 'storeloaded', 'new', 'save-all', 'after-save', 'delete','logout','open-new-window','external-relation-save');
		Garp.eventManager.on({
			'new': function(){
				Garp.updateUI.defer(20);	
			},
			'modelchange': Garp.changeModel,
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
				if (t && s) {
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
				if (Garp.formPanel.formcontent.getTopToolbar().saveButton.disabled) {
					return;
				}
				Garp.eventManager.fireEvent('save-all');
			}
		},{
			key: Ext.EventObject.DELETE,
			ctrl: true,
			handler: function(e){
				if(Garp.dataTypes[Garp.currentModel].disableDelete || Garp.toolbar.deleteButton.disabled){
					return;
				}
				Garp.eventManager.fireEvent('delete');
			}
		},{
			key: 'N',
			ctrl: true,
			handler: function(e){
				if(Garp.dataTypes[Garp.currentModel].disableCreate || Garp.toolbar.newButton.disabled){
					return;
				}
				Garp.eventManager.fireEvent('new');
			}
		}]);
		Garp.keyMap.stopEvent = true; // prevents browser key handling. 
	};
	
	/**
	 * Displays flashMessage from cookie
	 * @returns {bool} if a flashMessage is shown
	 */
	Garp.flashMessage = function(){
		var cookie = Ext.decode(Ext.util.Cookies.get('FlashMessenger'));
		var str = '';
		if(cookie.messages){
			for(var msg in cookie.messages){
				msg = cookie.messages[msg];
				if (msg) {
					msg = msg.replace(/\+/g, ' ');
					str += msg + '<br>';
				} 
			}
			if(str){
				var elm = Ext.get('app-loader'); 
				elm.update(str);
				elm.setWidth(300);
				elm.setHeight((cookie.messages.length-1) * 20 + 30);
				
				var value = "; path=/";
				var domain = document.location.host;
				var date = new Date();
				date.setHours(date.getHours(-1));
				value += "; domain="+escape(domain);
				value += ((date===null) ? "" : "; expires="+date.toGMTString());
				document.cookie='FlashMessenger' + "=" + value;
				return true;
			}
		}
		return false;
	};
	
	
	/**
	 * Afterinit
	 * Sets up history & displays flashMessages if needed. Also hides the loader anim. 
	 */
	Garp.afterInit = function(){

		Garp.history.setupListeners();
		Garp.history.parseState();

		var timeout = 610;		
		if (Garp.flashMessage()) {
			timeout = 2000;
		} 
		setTimeout(function(){
			Ext.get('app-loader').fadeOut();
		}, timeout);
	};
	
	/**
	 * Global Ajax event's will show a small spinner for the user to see activity with the server:
	 */
	Garp.setupAjaxSpinner = function(){
		var spinner = Ext.select('#icon-loading-spinner');
		spinner.hide();
		Ext.Ajax.on('requestcomplete', function(){
			if (!Ext.Ajax.isLoading()) {
				spinner.hide.defer(200, spinner); // defer makes it a bit less flashy & hyperactive
			}
		});
		Ext.Ajax.on('beforerequest', function(){
			spinner.show();
		});
		Ext.Ajax.on('requestexception', function(){
			spinner.hide();
		});
	};
	
	/**
	 * Init
	 */
	Garp.init = function(){
		Garp.gridPanel = new Garp.WelcomePanel(); // temporarily call it Garp.gridPanel, so the viewport doesn't have to reconfigure.
		Garp.viewport = new Garp.Viewport();
		Garp.setupEventManager();
		Garp.setupGlobalKeys();
		Garp.setupAjaxSpinner();
		Garp.afterInit();
	};
