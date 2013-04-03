/**
 * InlineRelator class
 */
Garp.InlineRelator = Ext.extend(Ext.Panel, {

	model: '',
	rule: null,
	rule2: null,
	unrelateExisting: true,
	
	border: false,
	bodyBorder: false,
	autoHeight: true,
	autoScroll: true,
	
	buttonPosition: 'top',
	
	localId: null,

	getEmptyRecord: function(){
		return new this.relationStore.recordType();
	},
	
	setupStore: function(){
		var fields = Garp.dataTypes[this.model].getStoreFieldsFromColumnModel();

		this.writer = new Ext.data.JsonWriter({
			paramsAsHash: false,
			encode: false
		});
		
		this.relationStore = new Ext.data.DirectStore({
			fields: fields,
			autoLoad: false,
			autoSave: false,
			pruneModifiedRecords: true,
			remoteSort: true,
			restful: true,
			autoDestroy: true,
			root: 'rows',
			idProperty: 'id',
			sortInfo: Garp.dataTypes[this.model].sortInfo || null,
			baseParams: {
				start: 0,
				limit: Garp.pageSize,
				query: ''
			},
			api: {
				create: Garp[this.model].create || Ext.emptyFn,
				read: Garp[this.model].fetch || Ext.emptyFn,
				update: Garp[this.model].update || Ext.emptyFn,
				destroy: Garp[this.model].destroy || Ext.emptyFn
			},
			writer: this.writer,
			listeners: {
				'beforeload': {
					fn: function(){
						if (!this.localId) { // new records have no id so we can't load it's relations; there are none
							return false;
						}
						return true;
					},
					scope: this
				},
				'load': {
					fn: function(){
						this.addInlineForms();
						if (this.relationStore.getCount() === 0) {
							this.addForm();
						}
					},
					scope: this
				}
			}
		});
	},
	
	addInlineForms: function(){
		this.relationStore.each(function(rec){
			var form = new Garp.InlineForm({
				rec: rec,
				model: this.model,
				inlineRelator: this,
				buttonPosition: this.buttonPosition
			}, this);
			this.add(form);
		}, this);
		this.doLayout();
	},
	
	addForm: function(prevForm){
		var hideRemoveButton = false;
		if (prevForm) {
			var idx = this.relationStore.findBy(function(r){
				if (r == prevForm.rec) {
					return true;
				}
			}) + 1;
		} else {
			idx = this.relationStore.getCount();
			hideRemoveButton = true;	
		}
		var newRec = this.getEmptyRecord();
		this.relationStore.insert(idx, newRec);
		var form = new Garp.InlineForm({
			rec: newRec,
			model: this.model,
			inlineRelator: this,
			hideRemoveButton : hideRemoveButton
		}, this);
		this.insert(idx, form);
		this.doLayout();
	},
	
	/**
	 * Relates the owning form ID with our records
	 */
	relate: function(){
		var data = {
			model: this.model,
			unrelateExisting: this.unrelateExisting,
			primaryKey: this.localId,
			foreignKeys: (function(){
				var records = [];
				this.relationStore.each(function(rec){
					if (rec.data.id) {
						records.push({
							key: rec.data.id,
							relationMetadata: rec.data.relationMetadata ? rec.data.relationMetadata[Garp.currentModel] : []
						});
					}
				});
				records.reverse();
				return records;
			}).call(this)
		};
		if (this.rule) {
			data.rule = this.rule;
		}
		if (this.rule2) {
			data.rule2 = this.rule2;
		}
		
		var scope = this;
		Garp[Garp.currentModel].relate(data, function(res){
			if (res) {
				scope.relationStore.removeAll(true);
				scope.items.each(function(item){
					scope.remove(item);
				});
				scope.relationStore.reload();
			}
		});
		
	},
	
	saveAll: function(){
		this.relationStore.on({
			save: {
				fn: this.relate,
				scope: this
			}
		});
		this.relationStore.save();
	},
	
	removeForm: function(form){
		this.relationStore.remove(form.rec);
		this.remove(form);
		this.doLayout();
		this.relate();
	},
	
	initComponent: function(ct){
		Garp.InlineRelator.superclass.initComponent.call(this, ct);

		this.ownerForm = this.ownerCt.ownerCt;
		this.setupStore();
		
		this.ownerForm.on('loaddata', function(rec,fp){
			
			this.localId = rec.get('id');
			
			// first remove all "previous" items:
			this.items.each(function(item){
				this.remove(item);
			}, this);
			this.relationStore.removeAll(true);
			
			var q = {};
			q[Garp.currentModel + '.id'] = this.localId;
			this.relationStore.setBaseParam('query', q);
			this.relationStore.reload();
		}, this);
		
		Garp.eventManager.on('save-all', function(){
			this.items.each(function(){
				this.getForm().updateRecord(this.rec);
			});
			this.saveAll();
		}, this);
		
	}
	
});
Ext.reg('inlinerelator',Garp.InlineRelator);

/**
 * InlineRelator uses InlineForm
 */
Garp.InlineForm = Ext.extend(Ext.form.FormPanel, {

	rec: null,
	inlineRelator: '',
	
	border: false,
	bodyBorder: false,
	hideBorders: true,
	style:'padding-bottom: 2px;',
	
	hideRemoveButton: false,
	
	border: false,
	bodyBorder: false,
	layout:'hbox',
	hideLabel: true,
	
	/**
	 * Converts standard formConfig fieldset to a panel with fields
	 * @param {Object} items
	 */
	morphFields: function(items){
		
		var copy = items.items.slice(0);
		
		copy.push({
				iconCls: 'icon-new',
				xtype: 'button',
				width: 32,
				flex: 0,
				handler: function(){
					this.inlineRelator.addForm(this);
				},
				scope: this
			},{
				iconCls: 'icon-delete',
				xtype: 'button',
				width: 32,
				flex: 0,
				handler: function(){
					this.inlineRelator.removeForm(this);
				},
				scope: this
			});
		
		Ext.each(copy, function(item){
			if (!item.hasOwnProperty('flex')) {
				item.flex = 1;
			}
			if (item.xtype == 'textarea') {
				item.xtype = 'textfield';
			}
		});
		return copy;
	},

	initComponent: function(ct){
		this.items = this.morphFields(Ext.apply({}, Garp.dataTypes[this.model].formConfig[0].items[0])); // better copy
		Garp.InlineForm.superclass.initComponent.call(this);
		if (this.rec) {
			this.getForm().loadRecord(this.rec);
		}
	}
});

/**
 * Simple labels to be used in conjunction with inlineRelator
 */
Garp.InlineRelatorLabels = Ext.extend(Ext.Panel, {

	model: null,
	
	layout: 'hbox',
	border: false,
	hideLabel: true,
	defaults: {
		flex: 1,
		style: 'font-weight: bold; margin-bottom: 5px; ',
		xtype: 'label'
	},
	
	initComponent: function(ct){
		var fields = Garp.dataTypes[this.model].formConfig[0].items[0].items.slice(0);
		var labels = [];
		Ext.each(fields, function(f){
			if (!f.disabled && !f.hidden && f.fieldLabel) {
				labels.push({
					text: __(f.fieldLabel)
				});
			}
		});
		labels.push({
			width: 65,
			text: ' ',
			flex: 0
		});
		this.items = labels;
		Garp.InlineRelatorLabels.superclass.initComponent.call(this, ct);
	}
	
});
Ext.reg('inlinerelatorlabels', Garp.InlineRelatorLabels);
