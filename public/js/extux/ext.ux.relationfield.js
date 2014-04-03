Ext.ns('Ext.ux')

Ext.ux.RelationField = Ext.extend(Ext.form.ComboBox,{
	/**
	 * @cfg model
	 */
	model: null,
	allowBlank: false,
	autoLoad: true,
	editable: false,
	triggerAction: 'all',
	typeAhead: false,
	mode: 'remote',
	valueField: 'id',
	displayField: 'name',
	disableCreate: false,
	emptyText: __('(empty)'),
	allQuery: '',
	lastQuery: '',
	
	triggerFn: function(){
		var win = new Garp.ModelPickerWindow({
			model: this.model,
			allowBlank: this.allowBlank
		});
		win.on('select', function(selected){
			var v = this.getValue();
			if (selected && selected.selected) {
				this.setValue(selected.selected.get('id'));
			} else {
				this.setValue(null);
			}
			this.originalValue = v;
			this.fireEvent('select', selected);
		}, this);
		win.show();
	},
	
	// override, to allow for null values (or "unrelate", so to say)
	
	getValue: function(){
		var value = Ext.ux.RelationField.superclass.getValue.call(this);
		if(value == '' || value == this.emptyText){
			value = null;
		}
		return value;
	},
	
	initComponent: function(){
		this.store = new Ext.data.DirectStore({
			autoLoad: Garp.dataTypes[this.model] ? this.autoLoad : false,
			autoSave: false,
			pruneModifiedRecords: true,
			remoteSort: true,
			restful: true,
			autoDestroy: true,
			root: 'rows',
			idProperty: 'id',
			fields: Garp.dataTypes[this.model] ? Garp.getStoreFieldsFromColumnModel(Garp.dataTypes[this.model].columnModel) : [],
			totalProperty: 'total',
			sortInfo: Garp.dataTypes[this.model] && Garp.dataTypes[this.model].sortInfo ? Garp.dataTypes[this.model].sortInfo : null,
			baseParams: {
				start: 0,
				limit: Garp.pageSize
			},
			api: {
				create: Ext.emptyFn,
				read: Garp[this.model].fetch || Ext.emptyFn,
				update: Ext.emptyFn,
				destroy: Ext.emptyFn
			}
		});
		
		
		if (this.triggerFn) {
			this.onTriggerClick = this.triggerFn;
		} 
		
		if(this.allowBlank){
			this.store.on({
				'load': {
					single: true,
					scope: this,
					fn: function(){
						var clear = {
							id: 0
						};
						clear[this.displayField] = this.emptyText;
						var rec = new this.store.recordType(clear, 0);
						this.store.add(rec);
					}
				}
			});
		}
		Ext.ux.RelationField.superclass.initComponent.call(this);
		if(!Garp.dataTypes[this.model]){
			this.disable();
		}
	}
});

Ext.reg('relationfield', Ext.ux.RelationField);