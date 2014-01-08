/**
 * @class Garp.DataType
 * Provides basic DataType skeleton to 
 * 
 * @author Peter
 */

Ext.ns('Garp');

Garp.DataType = Ext.extend(Ext.util.Observable, {
	
	/**
	 * Grid store needs field definitions. We create it from the columnModel
	 */
	getStoreFieldsFromColumnModel : function(){
		var fields = [];
		Ext.each(this.columnModel, function(col){
			var o = {};
			if(col.dataIndex){
				o.name = col.dataIndex;
			}
			if(col.convert){
				o.convert = col.convert;
			}
			if(col.mapping){
				o.mapping = col.mapping;
			}
			fields.push(o);
		});
		return fields;
	},
	
	/**
	 * EXPERIMENTAL view tpl (creates HTML view, when editing is not allowed)
	 */
	getViewTpl: function(){
		var str = '<div class="view">';
		Ext.each(this.formConfig[0].items[0].items, function(i){
			if (!i.hidden && !i.disabled && i.fieldLabel && i.name) {
				str += '<h2>' + __(i.fieldLabel) + '</h2>';
				str += '<div>{' + i.name + '}</div>';
			}
		});
		str+='</div>';
		this.viewTpl = new Ext.XTemplate(str,{ compiled: true });
		return this.viewTpl;
	},
	
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
	 * Add a column
	 * @param {Object} column
	 */
	addColumn: function(column){
		this.columnModel.push(column);
	},
	
	/**
	 * Insert a column
	 * @param {Object} index
	 * @param {Object} column
	 */
	insertColumn: function(index, column){
		this.columnModel.splice(index, 0, column);
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
	 * add a Field at the end
	 * @param {Object} config
	 */
	addField: function(config){
		this.formConfig[0].items[0].items.push(config);
	},
	
	/**
	 * Adds an array of fields
	 * @param {Object} arr of configs
	 */
	addFields: function(arr){
		Ext.each(arr, function(config){
			this.addField(config);
		}, this);
	},
	
	/**
	 * add a Field at specified index
	 * @param {Object} index
	 * @param {Object} config
	 */
	insertField: function(index, config){
		this.formConfig[0].items[0].items.splice(index, 0, config);
	},
	
	
	/**
	 * Grab your RelationPanel
	 * @param {Object} modelName
	 * @return panel
	 */
	getRelationPanel: function(modelName){
		var panel;
		Ext.each(this.formConfig, function(items){
			Ext.each(items, function(i){
				if(i.model == modelName){
					panel = i;
					return;
				}
			});
		});
		return panel;
	},
	
	/**
	 * Retrieve all relation data 
	 * @return {Array} modelNames
	 */
	getRelations: function(){
		var out = [];
		Ext.each(this.formConfig, function(item){
			if(item.xtype && item.xtype === 'relationpanel'){
				out.push(item.model);
			}
		});
		return out;
	},
	
	/**
	 * Add a RelationPanel, simple convenience utility
	 * @param {Object} config
	 */
	addRelationPanel: function(cfg){
		Ext.apply(cfg, {
			xtype: 'relationpanel'
		});
		this.formConfig.push(cfg);
	},
	
	/**
	 * Adds a listener
	 * @param {Object} eventName
	 * @param {Object} fn
	 * @param {Object} insertBefore
	 */
	addListener: function(eventName, fn, insertBefore){
		this.setupListeners(eventName);
		var oldListener = this.formConfig[0].listeners[eventName];
		if (insertBefore) {
			this.formConfig[0].listeners[eventName] = function(rec, fp){
				fn(rec, fp);
				oldListener(rec, fp);
			};
		} else {
			this.formConfig[0].listeners[eventName] = function(rec, fp){
				oldListener(rec, fp);
				fn(rec, fp);
			};
		}
	},
	
	// private
	setupListeners: function(eventName){
		Ext.applyIf(this.formConfig[0], {
			listeners : {}
		});
		Ext.applyIf(this.formConfig[0].eventName, {
			loaddata : function(){}
		});		
	},
	
	
	constructor: function(cfg){
		this.addEvents('init');
		this.listeners = cfg.listeners;
		this.initialCfg = Ext.apply({}, cfg);
		
		Ext.apply(this, cfg);
		
		Ext.applyIf(this, {
		
			// simple description text to be used at infopanel
			description: '',
			
			// count is used at infopanel
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
			
			// the items to use for simple tooltips when referenced from a relationfield
			previewItems: [],
			
			// the items that the form's metaPanel will hold
			metaPanelItems: [],
			
			// previewlink can hold an object previewLink{urlTpl, param} for building a dynamic preview path for the user
			// urlTpl is an Ext.Template, and param the field/column reference
			previewLink: null,
			
			// the icon
			iconCls: 'icon-' + this.text.toLowerCase(),
			
			// this datatype does not contain editable fields, so hide the form and focus on the first relation tab:
			isRelationalDataType: false
		});
		
		Ext.applyIf(this.defaultData, {
			'created': null,
			'modified': null
		});
		
		Ext.applyIf(this, {
			columnModel: []
		});
		
		if (this.isRelationalDataType) {
			Ext.apply(this, {
				disableCreate: true,
				disableDelete: true
			});
			this.addListener('loaddata', function(r, fp){
				fp.items.get(0).activate(1);
				fp.items.get(0).hideTabStripItem(0);
			});
		}
		
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
		
		Ext.each(this.columnModel, function(col){
			if(col.defaultData && col.defaultData === null){
				col.useNull = true;
			}
			if (col.virtual) {
				col.sortable = false;
			}
		});
		
		Garp.DataType.superclass.constructor.call(this, cfg);
	}
});
