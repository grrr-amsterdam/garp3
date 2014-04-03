/**
 * @class Garp.DataType
 * Provides basic DataType skeleton to 
 * 
 * @author Peter
 */

Ext.ns('Garp');

Garp.DataType = Ext.extend(Ext.util.Observable, {
	
	/**
	 * Sets permissions according to Garp.API offered methods
	 * @param {Object} model
	 * @return false if model is not accesible in the first place 
	 */
	setupACL: function(model){
		if(!Ext.isDefined(model.create)){
			Ext.apply(this, {
				disableCreate: true,
				quickCreatable: false
			});
		}
		if(!Ext.isDefined(model.destroy)){
			Ext.apply(this, {
				disableDelete: true
			});
		}
		if(!Ext.isDefined(model.fetch)){
			Ext.apply(this, {
				hidden: true,
				disabled: true,
				disableCreate: true,
				quickCreatable: false
			});
			return false;
		}
		return true;
	},
	
	/** 
	 * Removes a column
	 * @param {String} dataIndex
	 */
	removeColumn: function(dataIndex){
		this.columnModel.remove(this.getColumn(dataIndex));
	},

	/**
	 * Retrieve a column
	 * @param {String} dataIndex
	 */
	getColumn: function(dataIndex){
		var column;
		Ext.each(this.columnModel, function(c){
			if(c.dataIndex == dataIndex){
				column = c;
				return;
			}
		});
		return column;
	},
	
	/** 
	 * Remove a field
	 * @param {String} name
	 */
	removeField: function(name){
		this.formConfig[0].items[0].items.remove(this.getField(name));
	},
	
	/**
	 * Get a Field by its name
	 * @param {String} name
	 */
	getField: function(name){
		var field;
		Ext.each(this.formConfig[0].items[0].items, function(items){
			Ext.each(items, function(i){
				if(i.name == name){
					field = i;
					return;
				}
			});
		});
		return field;
	},
	
	/**
	 * Grab your RelationPanel
	 * @param {Object} modelName
	 */
	getRelationPanel: function(modelName){
		var panel;
		Ext.each(this.formConfig, function(items){
			Ext.each(items, function(i){
				if(i['model'] == modelName){
					panel = i;
					return;
				}
			});
		});
		return panel;
	},
	
	
	constructor: function(cfg){
		this.addEvents('init');
		this.listeners = cfg.listeners;
		
		Ext.apply(this, cfg);
		
		Ext.applyIf(this, {
		
			description: '',
			
			countTpl: new Ext.XTemplate([__('Total'), ': {count} ', '<tpl if="count == 1">', __('item'), '</tpl>', '<tpl if="count != 1">', __('items'), '</tpl>']),
			
			// disabling will prevent this model from being accesible altogether
			disabled: false,
			
			// prevents User to delete record  
			disableDelete: false,
			
			// prevents User to create record
			disableCreate: false,
			
			// For relationpanels and fields. If this model is too complicated, one might want to set this to false: 
			quickCreatable: true,
			
			// how to display a record:
			displayFieldRenderer: function(rec){
				return rec.get('name') ? rec.get('name') : rec.phantom ? __('New ' + this.text) : __('Unnamed ' + this.text);
			},
			
			iconCls: 'icon-' + this.text.toLowerCase()
		});
		
		Ext.applyIf(this.defaultData, {
			'created': null,
			'modified': null
		});
		
		Ext.applyIf(this.formConfig[0].defaults, {
			boxMaxWidth: 835
		});
		
		Ext.applyIf(this, {
			columnModel: []
		});
		
		for (var column in this.defaultData) {
			var found = false;
			Ext.each(this.columnModel, function(item){
				if (item.dataIndex && item.dataIndex == column) {
					found = true;
				}
			});
			if (!found) {
				var txt = Ext.util.Format.capitalize(column.replace('_', ' '));
				
				this.columnModel.push({
					hidden: true,
					renderer: (column == 'created' || column == 'modified') ? Garp.renderers.dateTimeRenderer : null,
					dataIndex: column,
					header: __(txt)
				});
			}
		}
		
		Garp.DataType.superclass.constructor.call(this, cfg);
	}
});