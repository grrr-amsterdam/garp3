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
	
	initComponent: function(){
		if(!this.iconCls){
			this.setIconClass(Garp.dataTypes[this.model].iconCls);
		}
		if(!this.title){
			this.setTitle(Garp.dataTypes[this.model].text);
		}
		this.addEvents('aftersave');
		
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
		
		var items = Ext.apply({},Garp.dataTypes[this.model].formConfig[0]);
		Ext.apply(items, {
			ref: '../formcontent'
		});
		
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
			height: 440,
			autoScroll: true,
			defaults: {
				autoWidth: true,
				border: false,
				bodyCssClass: 'garp-formpanel' // visual styling
			},
			items: items
		}];
		
		this.buttons = [{
			text: __('Ok'),
			handler: function(){
				if (this.form.getForm().isValid()) {
					var rec = new this.store.recordType(Ext.apply({},Garp.dataTypes[this.model].defaultData));
					this.store.add(rec);
					this.form.getForm().updateRecord(rec);
					this.store.on({
						'save': {
							fn: function(){
								this.fireEvent('aftersave', this, rec);
								this.close();
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
					
				}
			},
			scope: this
		}, {
			text: __('Cancel'),
			handler: function(){
				this.close();
			},
			scope: this
		}];
		Garp.RelateCreateWindow.superclass.initComponent.call(this);
	},
	
	afterRender: function(){
		Garp.RelateCreateWindow.superclass.afterRender.call(this);
		this.form.getForm().remove(this.form.getForm().findField('id'));
		this.form.getForm().setValues(Garp.dataTypes[this.model].defaultData);
		if (this.onShow) {
			this.onShow.call(this);
		}
		
	}
});