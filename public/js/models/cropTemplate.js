/**
 * Datatype for debugging purposes only.
 */

/*
Ext.ns('Garp.dataTypes');
Garp.dataTypes.CropTemplate = new Garp.DataType({
	
	text: 'CropTemplate',
	
	displayFieldRenderer : function(rec){
		return rec.get('filename') || __('New Crop Template');
	},
	
	iconCls:'icon-img',
	
	defaultData: {
		id: null,
		w: null,
		h: null,
		crop: null,
		name: null
	},
	
	sortInfo: {
		field: 'created',
		direction: 'DESC'
	},
	
	columnModel: [{
		header: __('ID'),
		dataIndex: 'id'
	},{
		header: __('Width'),
		dataIndex: 'w'
	},{
		header: __('Height'),
		dataIndex: 'h'
	},{
		header: __('Crop'),
		dataIndex: 'crop'
	},{
		header: __('Name'),
		dataIndex: 'name'
	}],
			
	formConfig: [{
		layout: 'form',
		
		defaults: {
			defaultType: 'textfield'
		},
		
		
		items:[{
			xtype: 'fieldset',
			items: [{
				name: 'id',
				hideFieldLabel: true,
				disabled: true,
				xtype: 'numberfield',
				hidden: true
			}, {
				name: 'width',
				fieldLabel: __('Width')
			}, {
				name: 'height',
				fieldLabel: __('Height')
			}, {
				name: 'crop',
				fieldLabel: __('Crop')
			},{
				name: 'name',
				fieldLabel: __('Name')
			}]
		}]
	}]
});*/