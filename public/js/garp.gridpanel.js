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

	/**
	 * @cfg {string} model: current Model for this panel
	 */
	model: null,
	
	/**
	 * @function newItem
	 * Creates one new item in the store. Displays it on the grid and selects it
	 */
	newItem: function(){
		if (Garp.dataTypes[Garp.currentModel].disableCreate) {
			return;
		}
		var rec = new this.store.recordType(Ext.apply({}, Garp.dataTypes[this.model].defaultData));
		this.store.insert(0, rec);
		this.getSelectionModel().selectFirstRow();
	},
	
	/**
	 * @function deleteItems
	 * Delete one (or more) item(s) from the store and calls save to sync it with the server
	 */
	deleteItems: function(){
		if (Garp.dataTypes[Garp.currentModel].disableDelete) {
			return;
		}
		var count = this.getSelectionModel().getCount();
		if (count <= 0) {
			return;
		}
		Ext.Msg.confirm(__('Garp'), count == 1 ? __('Are you sure you want to delete the selected item?') : __('Are you sure you want to delete the selected items?'), function(btn){
			if (btn == 'yes') {
				//this.loadMask.show();
				Ext.each(this.getSelectionModel().getSelections(), function(rec){
					this.store.remove(rec);
				});
				this.getStore().save();
				this.getSelectionModel().clearSelections();
				this.fireEvent('afterdelete');
			}
		}, this);
	},
	
	/**
	 * @function saveAll
	 */
	saveAll: function(){
		this.fireEvent('beforesave');
		
		// Let's not show a loadMask if there's no modified records, a save operation would appear to never end,
		// because the listener to hide te loadMask will never be called:
		if (this.getStore().getModifiedRecords().length > 0) {
			this.loadMask.show();
		}
		// Reload the store after saving, to get an accurate and fresh new view on the data
		this.getStore().on({
			'save': {
				fn: function(store){
					// Check to see if there are any modified (or phantom) records left.
					// If so, an error has possibly occurred that the user has to fix, before we can continue reloading the store (and view):
					if (this.getStore().getModifiedRecords().length == 0) {
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
	 * Selects all items on the grid, or clears all selection(s).
	 */
	selectAll: function(){
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
	 * @function _clientValidation
	 * @param {Object} formpanel
	 * @param {Object} valid
	 */
	_clientValidation: function(formpanel, valid){
		// check to see if we are already rendered.
		if (this.dom) {
			this.setDisabled(!valid);
		}
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
		
		var keyMap = new Ext.KeyMap(this.getEl(), [{
			key: 'a',
			ctrl: true,
			fn: this.selectAll,
			scope: this
		}]);
		keyMap.stopEvent = true;
		
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
			},
			'clientvalidation': {
				buffer: 50,
				fn: this._clientValidation
			}
		});
	},
	
	/**
	 * init
	 */
	initComponent: function(){
		this.addEvents('beforesave', 'rowselect', 'beforerowselect', 'storeloaded', 'rowdblclick', 'selectionchange');
		
		var fields = Garp.getStoreFieldsFromColumnModel(Garp.dataTypes[this.model].columnModel);
		
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
							case 'cancel':
							default:
								break;
						}
					}
				});
				return false;
			}
		};
		
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
				'save': {
					scope: this,
					fn: function(){
						this.fireEvent('after-save');
						this.loadMask.hide();
					}
				},
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
				create: Garp[this.model].create || function(){console.log('create');return true;}, // TODO: FIXME: expand functions
				read: Garp[this.model].fetch ||  function(){console.log('read');return true;},
				update: Garp[this.model].update || function(){console.log('update');return true;},
				destroy: Garp[this.model].destroy || function(){console.log('destroy');return true;}
			},
			writer: this.writer
		});
		
		// Set defaults:
		Ext.applyIf(this, {
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true
				},
				columns: Garp.dataTypes[this.model].columnModel
			}),
			pageSize: Garp.pageSize,
			viewConfig: {
				scrollOffset: -1, // No reserved space for scrollbar. Share it with last column
				emptyText: Ext.PagingToolbar.prototype.emptyMsg,
				deferEmptyText: true,
				forceFit: true
			},
			store: this.store,
			border: false,
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: false // true
			}),
			bbar: new Ext.PagingToolbar({
				pageSize: Garp.pageSize,
				displayInfo: true,
				store: this.store
			}),
			tbar: new Ext.ux.Searchbar({
				layout: 'hbox',
				store: this.store
			})
		});
		
		this.relayEvents(this.sm, ['beforerowselect', 'rowselect', 'selectionchange', 'rowdblclick']);
		Garp.GridPanel.superclass.initComponent.call(this);
		this.on('render', function(){
			this.setupEvents();
		}, this);
		
	},
	
	afterRender: function(){
		this.getBottomToolbar().on('defocus', this.focus.createDelegate(this));
		Garp.GridPanel.superclass.afterRender.call(this);
	}
});