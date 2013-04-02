Garp.InlineRelator = Ext.extend(Ext.Panel, {

	model: '',
	rule: null,
	rule2: null,
	unrelateExisting: true,
	
	id: 'InlineRelator', // TEMP: REMOVE ME
	
	border: false,
	bodyBorder: false,
	autoHeight: true,
	autoScroll: true,
	
	localId: null,

	getEmptyRecord: function(){
		return new this.relationStore.recordType(); //Ext.apply({}, Garp.dataTypes[this.model].defaultData));
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
				inlineRelator: this
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
		form.focusFirstField();
	},
	
	relate: function(){
	
	
		var data = {
			model: this.model,
			unrelateExisting: this.unrelateExisting,
			primaryKey: this.localId,
			foreignKeys: (function(){
				var records = [];
				this.relationStore.each(function(rec){
					records.push({
						key: rec.data.id,
						relationMetadata: rec.data.relationMetadata ? rec.data.relationMetadata[Garp.currentModel] : []
					});
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

Garp.InlineForm = Ext.extend(Ext.form.FormPanel, {

	rec: null,
	inlineRelator: '',
	
	border: false,
	bodyBorder: false,
	hideBorders: true,
	style:'border:0; border-bottom: 1px dotted #ccc; padding-bottom: 2px;',
	
	hideRemoveButton: false,
	
	focusFirstField: function(){
		this.items.get(0).items.each(function(i){
			if (i && i.isVisible && i.isVisible() && i.focus) {
				i.focus();
				return false;
			}
		});
	},

	initComponent: function(ct){
		this.items = Ext.apply({}, Garp.dataTypes[this.model].formConfig[0].items[0]); // better copy
		this.tbar = new Ext.Toolbar({
			style: 'border:0;',
			items: [{
				iconCls: 'icon-new',
				text :__('Add'),
				handler: function(){
					this.inlineRelator.addForm(this);
				},
				scope: this
			},{
				iconCls: 'icon-delete',
				text: __('Remove'),
				//hidden: this.hideRemoveButton,
				handler: function(){
					this.inlineRelator.removeForm(this);
				},
				scope: this
			}]
		});

		Garp.InlineForm.superclass.initComponent.call(this);
		
		if (this.rec) {
			this.getForm().loadRecord(this.rec);
		}
		
	}
});
