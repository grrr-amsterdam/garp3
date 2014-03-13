Ext.ns('Ext.ux');
/**
 * RelationPanel extends on Ext.Panel
 * 
 * @author: Peter Schilleman, Engelswoord for Grrr.nl
 *  
 */
Ext.ux.RelationPanel = Ext.extend(Ext.Panel, {
	layout: 'border',
	bodyBorder: false,
	border: true,
	forceLayout:true,
	
	monitorValid: false,
	
	/**
	 * @cfg: If set, we merely create a view tab; the only interactions possible are to open existing relations or quickCreate a new relation
	 */
	minimalUI: false,
	
	/**
	 * @cfg: Whether we want one long list (false) or paginate (true) the related items 
	 */
	paginated: false,
	
	/**
	 * @cfg: whether or not we allow users to create a new instance
	 */
	quickCreatable: false,
	
	/**
	 * @cfg: fieldReference to give to the quickCreate window
	 */
	quickCreateReference: null,
	
	/**
	 * @cfg: get to hold the Id of the selected row 
	 */
	localId : null,
	
	/**
	 * @cfg: Whether or not to unrelate existing items on the server first
	 */
	unrelateExisting: true,
	
	/**
	 * @cfg: model: specifies the relatable model for this panel
	 */
	model: null,
	
	/**
	 * @cfg: whether or not we can sort on the client
	 */
	weighable: false,
	
	/**
	 * @cfg: rules: defines rules for "self" relations (modelA - modelA relations)
	 */
	rule: null,
	rule2: null,

	/**
	 * @cfg: the bindingmodel to use with habtm relations
	 */
	bindingModel: null,

	/**
	 * @cfg: wether to save homophyllic relationships bidirectionally
	 */
	bidirectional: true,
	
	foreignKey: 'id',
	
	/**
	 * @cfg: override columnModel 
	 */
	columns: null,
	
	/**
	 * @cfg: title: specifies the title
	 */
	title: '',
	
	/**
	 * @cfg: quickCreateReference: used for the "New Something" button label
	 */
	quickCreateBtnLabel: '',
	
	/**
	 * @cfg: iconCls: specifies the icon
	 */
	//iconCls: null,
	
	/**
	 * @cfg: maxItems
	 */
	maxItems: null,
	
	/**
	 * @cfg: metaDataEditors: editors to use in metaDataPanel
	 */
	metaDataEditors: null,
	metaDataValidator: function(){return true;},
	
	dirty: function(){
		this.fireEvent('dirty');
		
		if (this.metaDataPanel) {
			var valid = this.metaDataValidator(this.metaDataPanel.getSource(), this.relateePanel.store.data);
		}
		if (valid) {
			this.getTopToolbar().saveBtn.enable();
		}
		this.getTopToolbar().cancelBtn.enable();
		this.ownerCt.items.each(function(i){
			if(i!=this){
				i.disable();
			}
		}, this);
	},
	
	undirty: function(){
		this.fireEvent('undirty');
		this.getTopToolbar().saveBtn.disable();
		this.getTopToolbar().cancelBtn.disable();
		this.ownerCt.items.each(function(i){
			if(i!=this){
				i.enable();
			}
		}, this);
	},
	
	
	/**
	 * @function getRowIndex
	 * gives the rowIndex of the D'n D drop on grid operation
	 *
	 * @param {Object} elm
	 * @param {Object} e
	 */
	getRowIndex: function(elm, e){
		return elm.getView().findRowIndex(Ext.lib.Event.getTarget(e));
	},
	
	/**
	 * @function highlight
	 * Highlights or unhiglihts the row for dnd operations
	 * @param {Object} el
	 */
	highlight: function(el){
		if (this.highlightEl) {
			this.highlightEl.removeClass('garp-dnd-over');
		}
		if (el) {
			this.highlightEl = Ext.get(el);
			this.highlightEl.addClass('garp-dnd-over');
		}
	},
	
	/**
	 * @function setupDD
	 * sets up the D'n D targets
	 */
	setupDD: function(){
		var scope = this;
		
		new Ext.dd.DropTarget(this.relatePanel.getView().el, {
			ddGroup: 'dd',
			copy: true,
			notifyOut: function(){
				scope.highlight(false);
			},
			notifyOver: function(ddSource, e, data){
				if (ddSource.dragData.grid.itemId == 'relatePanel') {
					// do not allow relatePanel items to be dropped in relatePanel
					return Ext.dd.DropZone.prototype.dropNotAllowed;
				} else {
					scope.highlight(scope.relatePanel.getView().getRow(scope.getRowIndex(scope.relatePanel, e)), this.highlight);
					return Ext.dd.DropZone.prototype.dropAllowed;
				}
			},
			notifyDrop: function(ddSource, e, data){
				scope.highlight(false);
				if (ddSource.dragData.grid.itemId == 'relatePanel') {
					return false;
				} else {
					var records = ddSource.dragData.selections;
					var index = scope.getRowIndex(scope.relateePanel, e);
					scope.moveRecords(ddSource.grid, scope.relatePanel, records, index);
					return true;
				}
			}
		});
		/* @FIXME:
		 * Used to work better, but didn't allow for moving outside of "items already there"
		 *  
		new Ext.dd.DropTarget(this.relateePanel.getView().mainBody, {
			ddGroup: 'dd',
			copy: true,
			notifyOut: function(){
				scope.highlight(false);
			},
			notifyOver: function(ddSource, e, data){
				scope.highlight(scope.relateePanel.getView().getRow(scope.getRowIndex(scope.relateePanel, e)), this.highlight);
				
				if (scope.maxItems && scope.relateeStore.getCount() >= scope.maxItems) {
					return Ext.dd.DropZone.prototype.dropNotAllowed;
				} else {
					return Ext.dd.DropZone.prototype.dropAllowed;
				}
				
			},
			notifyDrop: function(ddSource, e, data){
				scope.highlight(false);
				var records = ddSource.dragData.selections;
				var index = scope.getRowIndex(scope.relateePanel, e);
				scope.moveRecords(ddSource.grid, scope.relateePanel, records, index);
				
				return !(scope.maxItems && scope.relateeStore.getCount() >= scope.maxItems)
			}
		});
		*/
		new Ext.dd.DropTarget(this.relateePanel.getView().el, {
			ddGroup: 'dd',
			copy: true,
			notifyOut: function(){
				scope.highlight(false);
			},
			notifyOver: function(ddSource, e, data){
				scope.highlight(scope.relateePanel.getView().getRow(scope.getRowIndex(scope.relateePanel, e)), this.highlight);
				if (!scope.weighable && ddSource.dragData.grid.itemId == 'relateePanel') {
					return Ext.dd.DropZone.prototype.dropNotAllowed;
				}
				if (ddSource.dragData.grid.itemId == 'relateePanel') {
					return Ext.dd.DropZone.prototype.dropAllowed;
				}
				if (scope.maxItems && scope.relateeStore.getCount() >= scope.maxItems) {
					return Ext.dd.DropZone.prototype.dropNotAllowed;
				} else {
					return Ext.dd.DropZone.prototype.dropAllowed;
				}
				
			},
			notifyDrop: function(ddSource, e, data){
				if (!scope.weighable && ddSource.dragData.grid.itemId == 'relateePanel') {
					return false;
				}
				scope.highlight(false);
				var records = ddSource.dragData.selections;
				var index = scope.getRowIndex(scope.relateePanel, e);
				scope.moveRecords(ddSource.grid, scope.relateePanel, records, index);
				
				return !(scope.maxItems && scope.relateeStore.getCount() >= scope.maxItems);
			}
		});
	},
	
	/**
	 * @function moveRecords
	 * moves Records from grid one to the other
	 * @param {Object} source
	 * @param {Object} target
	 * @param {Object} records (optional)
	 * @param {Number} index (optional)
	 */
	moveRecords: function(source, target, records, index){

		// Reordering is not yet implemented on the server. disabled for now
		//index = null; // @TODO: remove this line, when it gets supported.

		// see if we may proceed with moving:
		if(this.maxItems && this.relateeStore.getCount() >= this.maxItems && target == this.relateePanel){
			// re-ordering within the same region should however be possible:
			if (source !== target) {
				return;
			}
		}
		if (!records) {
			records = source.getSelectionModel().getSelections();
		}
		if (this.maxItems && (this.relateeStore.getCount() + records.length) > this.maxItems && source == this.relatePanel) {
			return;
		}
		
		// @TODO: Possibly check for duplicate items (decide later):
		// if(!Ext.isDefined(target.store.getById(source.store.find('id'))))
		Ext.each(records, function(rec, i){
			
			var nr = new source.store.recordType(rec.data);
			if (Ext.isNumber(index)) {
				target.store.insert(index, nr);
			} else {
				index = target.store.getCount();
				target.store.add(nr);
			}
			source.store.remove(rec);
		});
		
		Ext.each([source, target], function(grid){
			grid.getSelectionModel().clearSelections();
			grid.getView().refresh();
		});
		
		this.dirty();
		if(!this.weighable){
			target.store.remoteSort = false;
			target.store.sort(Garp.dataTypes[this.model].sortInfo.field, Garp.dataTypes[this.model].sortInfo.direction);
			target.store.remoteSort = true;
		}
		
		var rec = records[0];
		var idx = target.store.find('id', rec.data.id);
		target.getSelectionModel().selectRow(idx || 0);
		
	},

	/**
	 * @function getStoreCfg
	 * @return default store Cfg object
	 */
	getStoreCfg: function(){
		return {
			autoLoad: false,
			autoSave: false,
			remoteSort: true,
			restful: true,
			autoDestroy: true,
			pruneModifiedRecords: true,
			root: 'rows',
			idProperty: 'id',
			fields: (function(){
				var out = Garp.dataTypes[this.model].getStoreFieldsFromColumnModel();
				out.push({
					dataIndex: 'relationMetadata',
					header: false,
					searchable: false,
					hidden: true
				});
				return out;
			}).call(this),
			totalProperty: 'total',
			sortInfo: Garp.dataTypes[this.model].sortInfo || null,
			baseParams: (function(){
				var out = {
					start: 0,
					limit: Garp.pageSize
				};
				if(this.rule){
					out.rule = this.rule; 
				}
				if(this.rule2){
					out.rule2 = this.rule2;
				}
				if(this.bindingModel){
					out.bindingModel = this.bindingModel;
				}
				out.bidirectional = this.bidirectional;
				return out; 
			}).call(this),
			api: {
				create: Ext.emptyFn,
				read: Garp[this.model].fetch || Ext.emptyFn,
				update: Ext.emptyFn,
				destroy: Ext.emptyFn
			}
		};
	},
	
	/**
	 * @function getGridCfg
	 * @param hideHeader
	 * @return defaultGridObj
	 */
	getGridCfg: function(hideHeaders){
		return {
			border: true,
			region: 'center',
			hideHeaders: hideHeaders,
			enableDragDrop: true,
			ddGroup: 'dd',
			cm: new Ext.grid.ColumnModel({
				defaults:{
					sortable: true
				},
				columns: (function(){
					var cols = [], cmClone, c, l;
					if (this.columns) {
						cmClone = Garp.dataTypes[this.model].columnModel;
						var shown = 0;
						for (c = 0, l = cmClone.length; c < l; c++) {
							col = Ext.apply({}, cmClone[c]);
							col.hidden = this.columns.indexOf(col.dataIndex) == -1;
							cols.push(col);
						}
						return cols;
					} else {
						cmClone = Garp.dataTypes[this.model].columnModel;
						for (c = 0, l = cmClone.length; c < l; c++) {
							col = Ext.apply({}, cmClone[c]);
							cols.push(col);
						}
						return cols;
					}
				}).call(this)
			}),
			pageSize: Garp.pageSize,
			title: __('Available'),
			iconCls: this.iconCls,
			viewConfig: {
				scrollOffset: -1, // No reserved space for scrollbar. Share it with last column
				forceFit: true,
				autoFill: true
			}
		};
	},
	
	/**
	 * @function getButtonPanel
	 * @return defaultButtonPanelObj
	 */
	getButtonPanel: function(){
		return {
			border: false,
			xtype: 'container',
			bodyBorder: false,
			region: 'east',
			width: 50,
			margins: '0 0 0 20',
			bodyCssClass: 'garp-relatepanel-buttons',
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch',
				pack: 'center',
				defaultMargins: {
					top: 5,
					left: 5,
					right: 5,
					bottom: 5
				}
			},
			items: [{
				xtype: 'button',
				iconCls: 'icon-relatepanel-relate',
				ref: '../../relateBtn',
				tooltip: __('Relate selected item(s)'),
				handler: this.moveRecords.createDelegate(this, [this.relatePanel, this.relateePanel, false, false])
			}, {
				xtype: 'button',
				iconCls: 'icon-relatepanel-unrelate',
				ref: '../../unrelateBtn',
				tooltip: __('Unrelate selected item(s)'),
				handler: this.moveRecords.createDelegate(this, [this.relateePanel, this.relatePanel, false, false])
			}]
		};
	},
	
	/**
	 * @function saveRelations
	 * @param options {Object}
	 * 
	 * Use options.force = true to disable dirty check and save anyway
	 */
	saveRelations: function(options){
		if(!Garp[Garp.currentModel].relate){
			return;
		}
		if(typeof options == 'undefined'){
			options = {};
		}
		
		if (!this.relateeStore) {
			return;
		}
		if (!options.force) {
			// check to see if there are any pending changes
			if (this.relateeStore.getModifiedRecords().length === 0 && this.relateStore.getModifiedRecords().length === 0) {
				return;
			}
		}
		
		this.loadMask = new Ext.LoadMask(this.getEl(), {
			msg: __('Relating&hellip;')
		});
		this.loadMask.show();
		
		var data = {
			model: this.model,
			unrelateExisting: this.unrelateExisting,
			primaryKey: this.localId,
			foreignKeys: (function(){
				var records = [];
				this.relateeStore.each(function(rec){
					records.push({
						key: rec.data.id,
						relationMetadata: rec.data.relationMetadata ? rec.data.relationMetadata[Garp.currentModel] : []
					});
				});
				records.reverse();
				return records;
			}).call(this)
		};
		if(this.rule){
			data.rule = this.rule;
		}
		if(this.rule2){
			data.rule2 = this.rule2;
		}
		if(this.bindingModel){
			data.bindingModel = this.bindingModel;
		}
		data.bidirectional = this.bidirectional;
		
		Garp[Garp.currentModel].relate(data, function(res) {
			this.loadMask.hide();
			if(res){
				
				if (this.model == Garp.currentModel) {
					// on homophile relations, refetch parent to get the right ID first:
					var gpStore = Garp.gridPanel.getStore(); 
					this.relateStore.rejectChanges();
					this.relateeStore.rejectChanges();
					gpStore.on({
						load: {
							fn: function(){
								var sm = Garp.gridPanel.getSelectionModel();
								this._selectionChange(sm);
								this.relateStore.reload();
								this.relateeStore.reload();
							},
							scope: this,
							buffer: 100,
							single: true
						}
					});
					gpStore.reload();
				} else {
					// it doesn't matter, the parent ID doesn't change, fetch easily:
					this.relateStore.reload();
					this.relateeStore.reload();
				}
			}
		}, this);
	},
	
	/**
	 * @function checkDirty
	 */
	checkDirty: function(continueAction){
		if(typeof continueAction != 'function') {
			continueAction = Ext.emptyFn;
		}
		
		if (this.relateStore.getModifiedRecords().length > 0 || this.relateeStore.getModifiedRecords().length > 0) {
			Ext.Msg.show({
				animEl: Garp.viewport.getEl(),
				icon: Ext.MessageBox.QUESTION,
				title: __('Garp'),
				msg: __('Would you like to save your changes?'),
				buttons: Ext.Msg.YESNOCANCEL,
				scope: this,
				fn: function(btn){
					switch (btn) {
						case 'yes':
							this.saveRelations();
							var c = 2;
							function async(){
								c--;
								if(c === 0){
									this.relateStore.rejectChanges();
									this.relateeStore.rejectChanges();
									continueAction();
								}
							}
							
							this.relateeStore.on({
								'load': {
									fn: async,
									scope: this,
									single: true
								}
							});
							this.relateStore.on({
								'load': {
									fn: async,
									scope: this,
									single: true
								}
							});
							break;
						case 'no':
							this.relateStore.rejectChanges();
							this.relateeStore.rejectChanges();
							continueAction();
						//case 'cancel':
						//default:
							break;
					}
				}
			});
			return false;
		} else {
			return true;
		}
	},
	
	/**
	 * @function _selectionChange
	 * 
	 * binds a new query (model:[id]) to relate/relatee store
	 */
	_selectionChange: function(sm){
		this.setDisabled(sm.getCount() !== 1);
		
		if (sm.getCount() !== 1) {
			if (this.ownerCt && this.ownerCt.setActiveTab) {
				this.ownerCt.setActiveTab(0);
			}
			return;
		}

		var id = sm.getSelected().get(this.foreignKey);
		if (!id) {
			this.localId = sm.getSelected().get('id');
			if (this.localId === null) {
				this.setDisabled(true);
				this.ownerCt.setActiveTab(0);
			}
		} else {
			this.localId = id;
		}
		
		/// TODO: REFACTOR DUPLICATES BELOW!
		
		var q;
		q = {};
		if (typeof this.filterColumn === 'undefined') {
			q[Garp.currentModel + '.id <>'] = id;
		} else {
			q[this.filterColumn+' <>'] = id;
		}
		var baseParams = Ext.apply(this.relateStore.baseParams, {
			query: q,
			rule: this.rule
		});
		if(this.rule2){
			baseParams.rule2 = this.rule2;
		}
		if(this.bindingModel){
			baseParams.bindingModel = this.bindingModel;
		}
		baseParams.bidirectional = this.bidirectional;
		
		this.relateStore.setBaseParam(baseParams);
		
		q = {};
		if (typeof this.filterColumn === 'undefined') {
			q[Garp.currentModel + '.id'] = id;
		} else {
			q[this.filterColumn] = id;
		}
		baseParams = Ext.apply(this.relateeStore.baseParams, {
			query: q,
			rule: this.rule
		});
		if(this.rule2){
			baseParams.rule2 = this.rule2;
		}
		if(this.bindingModel) {
			baseParams.bindingModel = this.bindingModel;
		}
		baseParams.bidirectional = this.bidirectional;
		this.relateeStore.setBaseParam(baseParams);
		this.searchbar.setBaseParams();
		
		if(!this.hidden && this.rendered) {
			this.relateePanel.getSelectionModel().clearSelections(true);
			if (this.metaPanel) {
				this.metaDataPanel.hide();
				this.metaDataPanel.ownerCt.doLayout();
			}
			this.relateStore.reload();
			this.relateeStore.reload();
		}
	},
	
	/**
	 * @function _onActivate
	 */
	_onActivate: function(){
		if (!this._onActivate.isLoaded) {
			if (!this.minimalUI) {
				this.relateStore.on({
					'load': {
						scope: this,
						single: true,
						fn: this.setupDD
					}
				});
			}
			this.relateStore.load();
			this.relateeStore.load();
			this._onActivate.isLoaded = true;
			this.relatePanel.doLayout();
		}
	},
	
	/**
	 * @function updateOnNewWindow
	 *  
	 */
	updateOpenNewWindow: function(){
		function xor(a,b){
			return !a != !b;
		}
		if(xor(this.relatePanel.getSelectionModel().getCount() == 1, this.relateePanel.getSelectionModel().getCount() == 1)){
			this.getTopToolbar().buttonopennewwindow.show();
		} else {
			this.getTopToolbar().buttonopennewwindow.hide();
		}
	},
	
	/**
	 * @function initComponent
	 */
	initComponent: function(){
		if (Garp.dataTypes[this.model]) {
			var RELATEESTORE_LIMIT = null;
			if (!this.iconCls) {
				this.setIconClass(Garp.dataTypes[this.model].iconCls);
			}
			if (!this.title) {
				this.setTitle(__(Garp.dataTypes[this.model].text));
			}
			if (!this.quickCreateBtnLabel) {
				this.quickCreateBtnLabel = this.title;
			}
			this.bodyCssClass = 'garp-relatepanel-buttons';
			
			this.relateStore = new Ext.data.DirectStore(Ext.apply({}, {
				listeners: {
					load: {
						scope: this,
						single: true,
						fn: function(){
							this.relateeStore.load();
						}
					}
				},
				api: {
					create: Ext.emptyFn,
					read: Garp[this.model].fetch || Ext.emptyFn,
					//read: Garp[this.model].fetch_unrelated,
					update: Ext.emptyFn,
					destroy: Ext.emptyFn
				}
			}, this.getStoreCfg()));
			
			function checkCount(store){
				if (this.maxItems !== null) {
					if (store.getCount() == this.maxItems) {
						this.relateBtn.disable();
					} else {
						this.relateBtn.enable();
					}
				}
				this.relateStore.filter([{
					scope: this,
					fn: function(rec){
						return !(this.relateeStore.getById(rec.get('id')));
					}
				}]);
			}
			
			this.relateeStore = new Ext.data.DirectStore(Ext.apply({}, {
				baseParams: {
					limit: this.paginated ? Garp.pageSize : RELATEESTORE_LIMIT
				},
				writer: new Ext.data.JsonWriter({
					paramsAsHash: false,
					writeAllFields: true,
					encode: false
				}),
				listeners: {
					'load': checkCount.createSequence(this.undirty),
					'add': checkCount,
					'remove': checkCount,
					'save': checkCount,
					'update': checkCount,
					scope: this
				}
			}, this.getStoreCfg()));
			
			this.relatePanel = new Ext.grid.GridPanel(Ext.apply({}, {
				itemId: 'relatePanel',
				store: this.relateStore,
				bbar: new Ext.PagingToolbar({
					pageSize: Garp.pageSize,
					store: this.relateStore,
					beforePageText: '',
					displayInfo: false
				}),
				tbar: this.searchbar = new Ext.ux.Searchbar({
					xtype: 'searchbar',
					store: this.relateStore
				}),
				listeners: {
					'rowdblclick': function(){
						this.moveRecords(this.relatePanel, this.relateePanel, false, false);
					},
					scope: this
				}
			}, this.getGridCfg(false)));
			
			var relateePanelCfg = Ext.apply({}, {
				itemId: 'relateePanel',
				title: __('Related'),
				iconCls: 'icon-relatepanel-related',
				store: this.relateeStore,
				tbar: (this.maxItems !== null ? new Ext.Toolbar({
					items: [{
						xtype: 'tbtext',
						text: this.maxItems + __(' item(s) maximum')
					}]
				}) : null),
				monitorResize: true,
				layout: 'fit',
				pageSize: RELATEESTORE_LIMIT,
				listeners: (this.minimalUI ? {} : {
					'rowdblclick': function(){
						this.moveRecords(this.relateePanel, this.relatePanel, false, false);
					},
					scope: this
				})
			}, this.getGridCfg(this.maxItems !== null));
			if (this.paginated) {
				relateePanelCfg.pageSize = Garp.pageSize;
				relateePanelCfg.bbar = new Ext.PagingToolbar({
					pageSize: Garp.pageSize,
					store: this.relateeStore,
					beforePageText: '',
					displayInfo: false
				});
			}
			this.relateePanel = new Ext.grid.GridPanel(relateePanelCfg);
			
			if (this.minimalUI) {
				this.items = [{
					xtype: 'container',
					layout: 'border',
					border: false,
					margins: '20 20 20 20',
					region: 'center',
					items: this.relateePanel
				}];
			} else {
				
				var scope = this;
				function validateMetaPanel(){
					if (scope.rendered && scope.isVisible()) {
						if (scope.metaDataValidator(scope.metaDataPanel.getSource(), scope.relateePanel.store.data)) {
							//scope.undirty();
							scope.getTopToolbar().saveBtn.enable();
						} else {
							//scope.dirty();
							scope.getTopToolbar().saveBtn.disable();
						}
					}
				}
				
				this.metaDataPanel = new Ext.grid.PropertyGrid({
					split: true,
					__relationPanel: this,
					layout: 'fit',
					region: 'south',
					minHeight: 250,
					height: 200,
					collapsed: false,
					customEditors: this.metaDataEditors,
					foceValidation: true,
					hidden: true,
					collapsible: false,
					source: this.source || {},
					listeners:{
						propertychange: validateMetaPanel
					}
				});
				this.metaDataPanel.store.on('load', validateMetaPanel, this);
				
				
				
				this.items = [{
					xtype: 'container',
					layout: 'border',
					border: false,
					width: '50%',
					margins: '15 15 20 15',
					region: 'center',
					items: [this.relatePanel, this.getButtonPanel()]
				}, {
					xtype: 'container',
					layout: 'border',
					width: '50%',
					region: 'east',
					margins: '15 15 20 5',
					border: false,
					bodyCssClass: 'garp-relatepanel-buttons',
					items: [this.relateePanel, this.metaDataPanel]
				}];
			}
			
			this.tbar = new Ext.Toolbar({
				style: 'border:0; padding: 15px 15px 0 15px;',
				border: false,
				items: [{
					iconCls: 'icon-save',
					text: __('Save'),
					ref: 'saveBtn',
					disabled: true,
					hidden: this.minimalUI,
					handler: function(){
						this.saveRelations();
					},
					scope: this
				}, {
					iconCls: 'icon-cancel',
					text: __('Cancel'),
					ref: 'cancelBtn',
					hidden: this.minimalUI,
					disabled: true,
					handler: function(){
						//this.relateStore.reload();
						this.relateeStore.reload();
						this.metaDataPanel.hide();
						this.metaDataPanel.setSource(this.source || {});
						this.relateePanel.getSelectionModel().selectRange(-1,-1);
					},
					scope: this
				}, (Garp.dataTypes[this.model].quickCreatable ? {
					iconCls: 'icon-new',
					text: __('New ' + this.quickCreateBtnLabel),
					handler: function(){
						var cfg = {
							model: this.model,
							iconCls: this.iconCls,
							title: this.title,
							quickCreateReference: this.quickCreateReference
						};
						if (this.quickCreateConfig) {
							cfg = Ext.apply(cfg, this.quickCreateConfig);
						}
						var win = new Garp.RelateCreateWindow(cfg);
						
						win.show();
						win.on('aftersave', function(rcwin, rec){
							this.relateeStore.add(rec);
							this.saveRelations({
								force: true
							});
						}, this);
					},
					scope: this
				} : {
					hidden: true
				}), ' ',{
					iconCls: 'icon-open-new-window',
					hidden: true,
					ref: 'buttonopennewwindow',
					text: __('Open in new window'),
					
					handler: function(){
						var selected;
						if (this.relatePanel.getSelectionModel().getCount() > 0) {
							selected = this.relatePanel.getSelectionModel().getSelected();
							Garp.eventManager.on('external-relation-save', function(){
								this.relatePanel.getStore().reload();
							}, this);
						} else {
							selected = this.relateePanel.getSelectionModel().getSelected();
							Garp.eventManager.on('external-relation-save', function(){
								this.relateePanel.getStore().reload();
							}, this);
						}
						if (selected) {
							var id = selected.get('id');
							var url = BASE + 'admin?model=' + this.model + '&id=' + id;
							
							var win = window.open(url);
						}
					},
					scope: this
				}, '->', {
					text: __('Export'),
					iconCls: 'icon-export',
					hidden: false,
					handler: function(){
						var win = new Garp.ExportWindow();
						win.show();
					}
				}, ' ', {
					iconCls: 'icon-help',
					text: __('How does this work?'),
					hidden: this.minimalUI,
					handler: function(){
						var tt = new Ext.ToolTip({
							target: this.getEl(),
							anchor: 'top',
							preventBodyReset: true,
							bodyCssClass: 'garp-tooltip',
							//@TODO : style this:
							html: __('Relate or reorder items by dragging them around, or use the arrow buttons in the middle.'),
							autoHide: true
						});
						tt.show();
					}
				}]
			});
			
			/**
		 *  Event handling:
		 */
			//this.on('afterlayout', this._onActivate, this); // was bugy, caused weired layout issues sometimes, changed event order... 
			this.on('activate', this._onActivate, this); // @TODO: refactor method names to cope with new event names
			this.on('hide', function(){
				this._onActivate.isLoaded = false;
			}, this);
			
			this.on('deactivate', this.checkDirty, this);
			
			this.relatePanel.getSelectionModel().on({
				'selectionchange': {
					scope: this,
					buffer: 25,
					fn: this.updateOpenNewWindow
				}
			});
			
			this.relateePanel.getSelectionModel().on({
				'selectionchange': {
					scope: this,
					buffer: 25,
					fn: function(sm){
					
						this.updateOpenNewWindow();
						if (!this.metaDataPanel) {
							return;
						}
						var hasSource = false;
						for(var i in this.metaDataPanel.getSource()){
							hasSource = true;
							break;
						}
						if (!sm || sm.getCount() != 1 || !hasSource) {
							this.metaDataPanel.hide();
							this.metaDataPanel.ownerCt.doLayout();
							
						} else if (sm && sm.getCount() == 1 && this.relateeStore && this.relateeStore.fields.containsKey('relationMetadata')) {
							this.metaDataPanel.show();
							this.metaDataPanel.ownerCt.doLayout();
							this.metaDataPanel.startEditing(0, 1);
						}
					}
				},
				'rowselect': {
					scope: this,
					buffer: 20,
					fn: function(sm, ri, rec){
						if (!this.metaDataPanel) {
							return;
						}
						if (sm.getCount() == 1) {
							if (rec.data.relationMetadata && rec.data.relationMetadata[Garp.currentModel]) {
								this.metaDataPanel._recordRef = rec.id;
								var r = rec.data.relationMetadata[Garp.currentModel];
								// null values don't get shown in propertygrid. Make it empty strings
								// @TODO: check to see if this is Ok.
								for (var i in r) {
									if(r[i] === null){
										r[i] = '';
									}
								}
								this.metaDataPanel.setSource(r);
							}
						} else {
							this.metaDataPanel.hide();
							this.metaDataPanel.ownerCt.doLayout();
						}
					}
				}
			});
			
			if (this.metaDataPanel) {
				this.metaDataPanel.on('propertychange', function(source, recId, val, oldVal){
					if (val != oldVal) {
						//this.dirty();
						var rec = this.relateeStore.getById(this.metaDataPanel._recordRef);
						if (!rec) {
							return;
						}
						rec.beginEdit();
						var k = rec.data.relationMetadata[Garp.currentModel];
						rec.set({
							k: source
						});
						rec.markDirty();
						rec.endEdit();
						this.relateePanel.getView().refresh();
					}
				}, this);
			}
			
			if (Garp.eventManager) { // Test environment doesn't include Garp.eventManager
				Garp.eventManager.on({
					'after-save': {
						scope: this,
						fn: this._selectionChange
					},
					'selectionchange': {
						scope: this,
						fn: this._selectionChange,
						buffer: 400
					},
					'save-all': {
						scope: this,
						fn: function(){
							this.saveRelations();
						}
					},
					'beforerowselect': {
						scope: this,
						fn: function(sm, ri){
							return this.checkDirty(function(){
								sm.selectRow(ri);
							});
						}
					}
				});
			}
		} else {
			this.ownerCt.on('afterrender', function(){
				this.destroy();
			}, this);
		}
		Ext.ux.RelationPanel.superclass.initComponent.call(this);
	},
	
	/**
	 * @function onDestroy
	 */
	onDestroy: function(){
		this.un('activate', this._onActivate, this);
		if (Garp.eventManager) {
			Garp.eventManager.un('save-all', this.saveRelations, this);
			Garp.eventManager.un('selectionchange', this._selectionChange, this);
			Garp.eventManager.un('after-save', this._selectionChange, this);
		}
		Ext.ux.RelationPanel.superclass.onDestroy.call(this);	
		this._onActivate.isLoaded = false;
	}
});
Ext.reg('relationpanel',Ext.ux.RelationPanel);
