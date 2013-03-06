/**
 * @class Garp.GridPanel
 * @extends Ext.grid.GridPanel
 * @author Peter
 *
 * @description Defines both Garp.gridpanel & its corresponding Garp.gridpanelstore.
 * Also decorates the directstore to allow for querying (searching)
 * 
 */


Garp.GridPanel = Ext.extend(Ext.grid.GridPanel, {
	loadMask: true,

	/**
	 * @cfg {string} model: current Model for this panel
	 */
	model: null,
	
	/**
	 * @function newItem
	 * Creates one new item in the store. Displays it on the grid and selects it
	 * Not applicable if this panel is disabled (various reasons) or the current model doesn't support it.
	 */
	newItem: function(){
		if (this.disabled || Garp.dataTypes[Garp.currentModel].disableCreate) {
			return;
		}
		var rec = new this.store.recordType(Ext.apply({}, Garp.dataTypes[this.model].defaultData));
		this.store.insert(0, rec);
		this.getSelectionModel().selectFirstRow();
	},
	
	/**
	 * @function deleteItems
	 * Delete one (or more) item(s) from the store and calls save to sync it with the server
	 * Not applicable if this panel is disabled (various reasons) or the current model doesn't support it.
	 */
	deleteItems: function(){
		
		var count = this.getSelectionModel().getCount();
		if (count <= 0) {
			return;
		}
		
		// phantom records will get deleted right away. Always. No questioning ;)
		var rec = this.getSelectionModel().getSelected();
		if(rec.phantom){
			this.store.remove(rec);
			this.getSelectionModel().clearSelections();
			this.fireEvent('afterdelete');
			return;
		}
		
		// not allowed?
		if (this.disabled || Garp.dataTypes[Garp.currentModel].disableDelete) {
			return;
		}
		
		Ext.Msg.confirm(__('Garp'), count == 1 ? __('Are you sure you want to delete the selected item?') : __('Are you sure you want to delete the selected items?'), function(btn){
			var sm = this.getSelectionModel();
			if (btn == 'yes') {
				Ext.each(sm.getSelections(), function(rec){
					this.store.remove(rec);
				});
				this.getStore().save();
				sm.clearSelections();
				this.fireEvent('afterdelete');
			}
			sm.selectRow(sm.last); // focus gridpanel again.
		}, this);
	},
	
	/**
	 * @function saveAll
	 */
	saveAll: function(){
		this.fireEvent('beforesave');
		
		var scrollTop = this.getView().scroller.getScroll().top;
				
		// Let's not show a loadMask if there's no modified records, a save operation would appear to never end,
		// because the listener to hide te loadMask will never be called:
		if (this.getStore().getModifiedRecords().length > 0) {
			this.loadMask.show();
		}
		
		var currentModified = this.getStore().getModifiedRecords();
		if(currentModified.length){
			currentModified = currentModified[0]; 
		}
		
		// Reload the store after saving, to get an accurate and fresh new view on the data
		this.getStore().on({
			'save': {
				fn: function(store){
					// Check to see if there are any modified (or phantom) records left.
					// If so, an error has possibly occurred that the user has to fix, before we can continue reloading the store (and view):
					if (this.getStore().getModifiedRecords().length === 0) {
						store.on({
							'load': {
								scope: this,
								single: true,
								fn: function(){
									
									if (currentModified && currentModified.get && !store.getById(currentModified.get('id'))) {
										this.getStore().on({
											load: {
												scope: this,
												single: true,
												fn: function(){
													this.loadMask.hide();
													this.getSelectionModel().selectFirstRow();
													this.getTopToolbar().searchById(currentModified.get('id'));
													this.fireEvent('after-save', this.getSelectionModel());
													this.enable();
												}
											}
										});
										this.getStore().load({
											params: {
												query: {
													id: currentModified.get('id')
												}
											}
										});
									} else {
										this.loadMask.hide();
										this.fireEvent('after-save', this.getSelectionModel());
										this.enable();
										var scope = this;
										setTimeout(function(){
											scope.getView().scroller.dom.scrollTop = scrollTop;
										}, 10); // ugly wait for DOM ready; view 'refresh' event fires way too early...
									}
								}
							}
						});
						store.reload();
					}
				},
				scope: this,
				single: true
			}
		});
		// Let the store decide whether or not to actually save:
		this.getStore().save();
	},
	
	/**
	 * @function selectAll
	 * Selects all items on the grid -if not disabled-, or clears all selection(s).
	 */
	selectAll: function(){
		if(this.disabled){
			return false;
		}
		var sm = this.getSelectionModel();
		var store = this.getStore();
		if (sm.getCount() === store.getCount()) { // if all are already selected 
			sm.clearSelections(); // ... clear the selection
		} else {
			sm.selectAll(); // ... otherwise, selectAll
		}
	},
	
	/**
	 * @function focus
	 * focuses the panel so cursor keys are enabled
	 */
	focus: function(){
		var sm = this.getSelectionModel();
		if (!sm.hasSelection()) {
			sm.selectFirstRow();
		}
		
		// now focus the actual row in the grid's view:
		var row = 0;
		var sel = sm.getSelections();
		if (sel.length) {
			row = this.getStore().indexOf(sel[0]);
		}
		this.getView().focusRow(row);
		
	},
	
	/**
	 * @function setupEvents
	 * Now initialize listeners:
	 */
	setupEvents: function(){
		/**
		 * Listen to Keyboard events
		 */
		var pagingToolbar = this.getBottomToolbar();
		
		function checkTarget(evt){
			// We might possibly have focus in a textbox in this gridpanel; we don't want the KeyMap to interfere
			return (!evt.getTarget('input') && !evt.getTarget('textarea') && !evt.getTarget('iframe'));
		}
		
		var keyMap = new Ext.KeyMap(this.getEl(), [{
			key: 'a',
			ctrl: true,
			fn: function(e, o){
				if (checkTarget(o)) {
					this.selectAll();
					o.stopEvent();
				}
			},
			scope: this
		},{
			key: Ext.EventObject.DELETE,
			ctrl: true,
			handler: function(e,o){
				if (checkTarget(o)) {
					if (Garp.dataTypes[Garp.currentModel].disableDelete || Garp.toolbar.deleteButton.disabled) {
						return;
					}
					
					this.fireEvent('delete');
					o.stopEvent();
				}
			},
			scope: this
		},{
			key: Ext.EventObject.BACKSPACE,
			ctrl: false,
			handler: function(e,o){
				if (checkTarget(o)) {
					if (Garp.dataTypes[Garp.currentModel].disableDelete || Garp.toolbar.deleteButton.disabled) {
						return;
					}
					this.fireEvent('delete');
					o.stopEvent();
				}
			},
			scope: this
		}]);
		
		var keyNav = new Ext.KeyNav(this.getEl(), {
			'enter': this.fireEvent.createDelegate(this, ['defocus']),
			'pageUp': function(e){
				if (e.ctrlKey || e.shiftKey) {
					pagingToolbar.moveFirst();
				} else {
					if (pagingToolbar.cursor > 0) {
						pagingToolbar.movePrevious();
					}
				}
			},
			'pageDown': function(e){
				if (e.ctrlKey || e.shiftKey) {
					pagingToolbar.moveLast();
				} else {
					if (pagingToolbar.cursor + this.getStore().getCount() < this.getStore().getTotalCount()) { // may we proceed?
						pagingToolbar.moveNext();
					}
				}
			},
			scope: this
		});
		
		/**
		 * Various events:
		 */
		this.on({
			scope: this,
			'new': {
				fn: this.newItem
			},
			'save-all': {
				fn: this.saveAll,
				buffer: 200
			},
			'delete': {
				fn: this.deleteItems
			}
		});
	},
	
	/**
	 * init
	 */
	initComponent: function(){
		this.addEvents('beforesave', 'rowselect', 'beforerowselect', 'storeloaded', 'rowdblclick', 'selectionchange');
		
		var fields = Garp.dataTypes[this.model].getStoreFieldsFromColumnModel();
		
		this.writer = new Ext.data.JsonWriter({
			paramsAsHash: false,
			encode: false
		});
		
		var scope = this;
		function confirmLoad(store, options){
			// check to see if the store has any dirty records. If so, do not continue, but give the user the option to discard / save.
			if (store.getModifiedRecords().length) {
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
											store.load(options);
										}
									}
								});
								store.save();
								break;
							case 'no':
								store.rejectChanges();
								store.on({
									load: {
										single: true,
										fn: function(){
											scope.getSelectionModel().clearSelections(false);
										}
									}
								});
								store.load(options);
								break;
							//case 'cancel':
							default:
								break;
						}
					}
				});
				return false;
			}
		}
		
		this.store = new Ext.data.DirectStore({
			autoLoad: false,
			autoSave: false,
			pruneModifiedRecords: true,
			remoteSort: true,
			restful: true,
			autoDestroy: true,
			root: 'rows',
			idProperty: 'id',
			fields: fields,
			listeners: {
				'beforeload': confirmLoad,
				'load': this.fireEvent.createDelegate(this, ['storeloaded']),
				'exception': {
					scope: this,
					fn: function(){
						this.loadMask.hide();
					}
				}
			},
			totalProperty: 'total',
			sortInfo: Garp.dataTypes[this.model].sortInfo || null,
			baseParams: {
				start: 0,
				limit: Garp.pageSize
			},
			api: {
				create: Garp[this.model].create || function(){return true;}, // TODO: FIXME: expand functions
				read: Garp[this.model].fetch ||  function(){return true;},
				update: Garp[this.model].update || function(){return true;},
				destroy: Garp[this.model].destroy || function(){return true;}
			},
			writer: this.writer
		});
		
		scope = this;
		
		// Set defaults:
		Ext.applyIf(this, {
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true
				},
				columns: Garp.dataTypes[this.model].columnModel,
				listeners: {
					// Defer, because renderers notified will not notice about the new state of columns on beforehand 
					'hiddenchange': function(){
						setTimeout(function(){
							scope.getView().refresh();
						}, 100);
					}
				}
			}),
			pageSize: Garp.pageSize,
			viewConfig: {
				scrollOffset: 20, // No reserved space for scrollbar. Share it with last column
				emptyText: Ext.PagingToolbar.prototype.emptyMsg,
				deferEmptyText: false,
				deferRowRender: false,
				enableRowBody: true,
				forceFit: true,
				contextRowCls: 'garp-contextrow'
			},
			store: this.store,
			border: false,
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: false // true
			}),
			bbar: new Ext.PagingToolbar({
				pageSize: Garp.pageSize,
				displayInfo: true,
				store: this.store,
				plugins: [new Garp.FilterMenu()]
			}),
			tbar: new Ext.ux.Searchbar({
				layout: 'hbox',
				store: this.store,
				listeners: {
					'search': {
						scope: this,
						fn: function(){
							this.getBottomToolbar().plugins[0].resetUI();
						}	
					}
				}
			})
		});
		
		this.relayEvents(this.sm, ['beforerowselect', 'rowselect', 'selectionchange', 'rowdblclick']);
		Garp.GridPanel.superclass.initComponent.call(this);
		this.on('render', function(){
			this.setupEvents();
		}, this);
		
		this.on('headerclick', function(grid, ci, e){
			
			// virtual columns might not be able to sort. Find out:
			if(grid.getColumnModel().columns[ci].virtual){
				var virtualSortField;
				// Some are fancy and able to sort:
				if (grid.getColumnModel().columns[ci].sortable) {
					virtualSortField = grid.getColumnModel().columns[ci].dataIndex;
				} else {
					// Others might point to others to sort on their behalf 
					virtualSortField = grid.getColumnModel().columns[ci].virtualSortField;
				}
				if (virtualSortField) {
					grid.getStore().on({
						load: {
							single: true,
							scope: this,
							fn: function(){
								for (var i = 0, l = grid.getColumnModel().columns.length; i < l; i++) {
									var el = Ext.get(grid.getView().getHeaderCell(i));
									el.removeClass('sort-asc');
									el.removeClass('sort-desc');
								}
								var dir = grid.getStore().sortInfo.direction.toLowerCase();
								Ext.get(grid.getView().getHeaderCell(ci)).addClass('sort-' + dir);
							}
						}
					});
					grid.getStore().sort(virtualSortField);
				} 
				return false;
			}
		});
		
	},
	
	setupContextMenus: function(){
		var scope = this;
		var refreshOption = {
			iconCls: 'icon-refresh',
			text: __('Refresh'),
			handler: function(){
				scope.getStore().reload();
			}
		};
		var newItemOption = {
			iconCls: 'icon-new',
			text: __('New'),
			handler: function(){
				scope.newItem();
			}
		};
		
		var cellContextMenu = new Ext.menu.Menu({
			items: [{
				iconCls: 'icon-open',
				text: __('Open'),
				handler: function(){
					removeContextMenuSelected.call(scope);
					scope.getSelectionModel().selectRow(scope._previousContextedRow);
				}
			}, {
				iconCls: 'icon-open-new-window',
				text: __('Open in new window'),
				handler: function(){
					scope.fireEvent('open-new-window');
				}
			}, newItemOption, {
				iconCls: 'icon-delete',
				text: __('Delete'),
				handler: function(){
					scope.deleteItems();
				}
			}, '-', refreshOption]
		});
		var viewContextMenu = new Ext.menu.Menu({
			items: [newItemOption, '-', refreshOption]
		});
		
		this.on('contextmenu', function(e){
			e.stopEvent();
			cellContextMenu.hide();
			viewContextMenu.showAt(e.getXY());
		});
		
		this._previousContextedRow = null;
		function removeContextMenuSelected(){
			if (this._previousContextedRow !== null) {
				Ext.get(this.getView().getRow(this._previousContextedRow)).removeClass(this.getView().contextRowCls);
			}
		}
		
		this.getView().el.on('click', removeContextMenuSelected.createDelegate(this));
		this.getView().el.on('contextmenu', removeContextMenuSelected.createDelegate(this));
		
		this.on('cellcontextmenu', function(grid, ri, ci, e){
			e.stopEvent();
			var gv = grid.getView();
			removeContextMenuSelected.call(this);
			Ext.get(gv.getRow(ri)).addClass(gv.contextRowCls);
			grid._previousContextedRow = ri;
			viewContextMenu.hide();
			cellContextMenu.showAt(e.getXY());
		});
	},
	
	afterRender: function(){
		this.getBottomToolbar().on('defocus', this.focus.createDelegate(this));
		Garp.GridPanel.superclass.afterRender.call(this);
		this.setupContextMenus();
	}
});