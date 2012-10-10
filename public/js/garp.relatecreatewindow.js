/**
 * 
 */
Ext.ns('Garp');

Garp.RelateCreateWindow = Ext.extend(Ext.Window,{

	model: '',
	iconCls: '',
	title: '',
	cls: 'relate-create-window',
	modal: true,
	width: 640,
	border: true,
	preventBodyReset: false,
	autoScroll: true,
	
	rec: null,
	
	quickCreatableInit: Ext.emptyFn,
	
	initComponent: function(){
	
		if (!this.iconCls) {
			this.setIconClass(Garp.dataTypes[this.model].iconCls);
		}
		if (!this.title) {
			this.setTitle(Garp.dataTypes[this.model].text);
		}
		this.addEvents('aftersave', 'afterinit');
		
		this.writer = new Ext.data.JsonWriter({
			paramsAsHash: false,
			encode: false
		});
		var fields = [];
		var cm = Garp.dataTypes[this.model].columnModel;
		Ext.each(cm, function(c){
			fields.push(c.dataIndex);
		});
		
		this.store = new Ext.data.DirectStore({
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
				limit: Garp.pageSize
			},
			api: {
				create: Garp[this.model].create || Ext.emptyFn,
				read: Garp[this.model].fetch || Ext.emptyFn,
				update: Garp[this.model].update || Ext.emptyFn,
				destroy: Garp[this.model].destroy || Ext.emptyFn
			},
			writer: this.writer
		});
		
		//var items = Ext.decode(Ext.encode(Ext.apply({}, Garp.dataTypes[this.model]).formConfig[0])); // cheap deep copy //@TODO: improve
		var items = Ext.apply({}, Garp.dataTypes[this.model].formConfig[0].items[0]); // better copy
		var listeners = Ext.apply({}, Garp.dataTypes[this.model].formConfig[0].listeners);
		items = {
			ref: '../formcontent',
			items: [items],
			listeners: listeners,
			bodyCssClass: 'garp-formpanel',
			border: false
		};
		
		// Now hide disabled items, they have no function when adding a new item. It may otherwise confuse users:
		// Also: if the field is not in the columnModel, it has no place here in this window
		/*
		 Ext.each(items.items,function(i){
		 Ext.each(i.items,function(j){
		 var inColumnModel = false
		 
		 delete j.tpl;
		 
		 Ext.each(cm, function(c){
		 if(j.name == c.dataIndex){
		 inColumnModel = true;
		 return true;
		 }
		 });
		 if(j.disabled || !inColumnModel || j.xtype == 'displayfield' &&  j.xtype != 'box'){
		 Ext.apply(j,{
		 hidden: true,
		 fieldLabel: '',
		 hideMode: 'display',
		 hideFieldLabel: true
		 });
		 }
		 });
		 });
		 */
		this.items = [{
			border: false,
			xtype: 'form',
			layout: 'form',
			ref: 'form',
			//height: 440,
			defaults: {
				autoWidth: true,
				border: false,
				bodyCssClass: 'garp-formpanel' // visual styling
			},
			items: items
		}];
		
		if(!this.buttons){
			this.buttons = [];
		}
		this.buttons.push([{
			text: __('Cancel'),
			ref: '../cancelBtn',
			handler: function(){
				this.close();
			},
			scope: this
		},{
			text: __('Ok'),
			ref: '../okBtn',
			handler: function(){
				if (this.form.getForm().isValid()) {
					this.rec = this.rec || this.store.getAt(0);
					this.form.getForm().updateRecord(this.rec);
					this.store.on({
						'save': {
							fn: function(){
								this.rec = this.store.getAt(0);
								this.fireEvent('aftersave', this, this.rec);
								this.loadMask.hide();
								this.close();
							},
							single: true,
							scope: this
						}
					});
					
					
					this.loadMask = new Ext.LoadMask(this.getEl(), {
						store: this.store
					});
					this.rec.markDirty();
					
					this.store.removeAll(true);		// @TODO: @FIXME: Find out why this really is necessary... 
					this.store.insert(0, this.rec); // Somehow, we need to insert the record again, cause the store doesn't see changes to the rec otherwise. Weird!
					
					this.loadMask.show();
					this.store.save();
				}
			},
			scope: this
		}]);
		
		Garp.RelateCreateWindow.superclass.initComponent.call(this);
	},
	
	afterRender: function(){
		Garp.RelateCreateWindow.superclass.afterRender.call(this);
		this.form.getForm().remove(this.form.getForm().findField('id'));
		this.form.getForm().setValues(Garp.dataTypes[this.model].defaultData);
		if (this.onShow) {
			this.onShow.call(this);
		}
		var rec = new this.store.recordType(Ext.apply({}, Garp.dataTypes[this.model].defaultData));
		this.rec = rec;
		this.store.add(rec);
		this.getForm = function(){
			return this.form.getForm();
		};
		this.on('save-all', function(){
		
			this.form.getForm().updateRecord(this.rec);
			this.store.on({
				'save': {
					fn: function(){
						this.rec = this.store.getAt(0);
						this.fireEvent('aftersave', this, this.rec);
						this.formcontent.fireEvent('loaddata', this.rec, this);
						this.loadMask.hide();
					},
					single: true,
					scope: this
				}
			});
			
			this.loadMask = new Ext.LoadMask(this.getEl(), {
				store: this.store
			});
			
			this.loadMask.show();
			this.store.save();
		}, this);
		
		
		this.on('show', function(){
			this.formcontent.fireEvent('loaddata', rec, this);
			this.quickCreatableInit();
			this.getForm().clearInvalid();
			if (this.quickCreateReference) {
				var id = this.parentId || Garp.gridPanel.getSelectionModel().getSelected().get('id');
				this.getForm().findField(this.quickCreateReference).store.on('load', function(){
					this.getForm().findField(this.quickCreateReference).setValue(id);
				}, this, {
					single: true
				});
				this.getForm().findField(this.quickCreateReference).store.load();
			}
			this.keymap = new Ext.KeyMap(this.formcontent.getEl(), [{
				key: Ext.EventObject.ENTER,
				ctrl: true,
				scope: this,
				handler: function(e){
					this.form.getForm().items.each(function(){
						this.fireEvent('blur', this);
					});
					this.okBtn.handler.call(this);
					return false;
				}
			}]);
			this.keymap.stopEvent = true; // prevents browser key handling.
			this.fireEvent('afterinit', this);
		}, this);
		
	}
});