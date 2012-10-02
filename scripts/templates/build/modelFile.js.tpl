Ext.ns('Garp.dataTypes');
Garp.dataTypes.${modelName} = new Garp.DataType({
	
	text: '${modelName}',
	iconCls:'icon-${modelIcon}',
	
	displayFieldRenderer : function(rec){
		return rec.get('name') || __('New ${modelName}');
	},
	
	defaultData: ${defaultData},
	sortInfo: ${sortInfo},
	columnModel: [${columnModel}],
			
	formConfig: [${formConfig}]
});