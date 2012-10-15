/**
 * @class Searchbar
 * 
 * Toolbar with searchfield and option menu
 * 
 * @extends Ext.Toolbar   
 * @author: Peter
 */

Ext.ns('Ext.ux');

Ext.ux.Searchbar = Ext.extend(Ext.Toolbar, {

	/**
	 * @cfg height
	 * fixed to enable show/hide
	 *
	 * @TODO: Not sure why we need to hardcode this. Bug in Ext?
	 */
	height: 27,
	
	/**
	 * @cfg grid
	 * reference to the gridpanel this toolbar is bound to:
	 */
	grid: null,
	
	/**
	 * @function makeQuery
	 * 
	 * preserves possible "Model.id" queryStrings for relatePanel
	 * 
	 * @param {Object} queryStr
	 */
	makeQuery: function(queryStr){
		if (!queryStr) {
			this.grid.getStore().baseParams = this.originalBaseParams;
		} else {
			var q = this.grid.getStore().baseParams ? this.grid.getStore().baseParams.query : '';
			var dt = new Ext.util.MixedCollection();
			dt.addAll(Garp.dataTypes);
			var preserve = {};
			
			if (Ext.isObject(q)) {
				dt.eachKey(function(key){
					var keyId = key + '.id';
					if (q[keyId]) {
						preserve[keyId] = q[keyId];
					}
					var keyNotId = key + '.id <>';
					if (q[keyNotId]) {
						preserve[keyNotId] = q[keyNotId];
					}
				});
			}
			
			this.grid.getStore().setBaseParam('query', Ext.apply(this.convertQueryString(queryStr, this.getSelectedSearchFields()), preserve));
			this.grid.getStore().setBaseParam('pageSize', Garp.pageSize);
		}
		this.fireEvent('search', this);
		this.grid.getStore().load();
		//this.grid.getSelectionModel().selectFirstRow();
	},
	
	/**
	 * @function convertQueryString
	 * converts string to object
	 * @param {String} query
	 * @return {Object}
	 */
	convertQueryString: function(query, fields){
		if (query === '') {
			return {};
		}
		var q = '%' + query + '%';
		if (fields && fields.length) {
			var obj = {};
			Ext.each(fields, function(f){
				obj[f + ' like'] = q;
			});
			return {
				'or': obj
			};
		}
	},
	
	/**
	 * @function getSelectedSearchFields
	 * @return {Array} fields
	 */
	getSelectedSearchFields: function(){
		var fields = [];
		if (this.searchOptionsMenu) {
			Ext.each(this.searchOptionsMenu.items.items, function(item){
				if (item.xtype === 'menucheckitem' && item.checked && item._dataIndex) {
					fields.push(item._dataIndex);
				}
			});
		}
		return fields;
	},
	
	/**
	 * @function getAllSearchFields
	 * @return {Array} all fields searchable
	 */
	getAllSearchFields: function(){
		var fields = [];
		if (!this.grid) {
			return;
		}
		Ext.each(this.grid.getColumnModel().config, function(col){
			if (col.searchable === false) {
				return;
			} else if (col.searchable || (col.dataIndex !== 'relationMetadata')) {
				fields.push(col);
			}
		});
		return fields;
	},
	
	/**
	 * searchOptionsMenu
	 * The search options menu (Placeholder. It will get rebuild later, when the grid finishes loading):
	 */
	searchOptionsMenu: new Ext.menu.Menu({
		items: {
			xtype: 'menutextitem',
			cls: 'garp-searchoptions-menu',
			text: Ext.PagingToolbar.prototype.emptyMsg
		}
	}),
	
	/**
	 * @function buildSearchOptionsMenu
	 */
	buildSearchOptionsMenu: function(){
		var fields = this.getAllSearchFields();
		var menuItems = [{
			xtype: 'menutextitem',
			cls: 'garp-searchoptions-menu',
			text: __('Search in:')
		}, {
			xtype: 'menucheckitem',
			hideOnClick: false,
			checked: false,
			text: __('Select All'),
			checkHandler: function(ci, checked){
				if(!ci.parentMenu){
					return;
				}
				ci.parentMenu.items.each(function(){
					if(this._dataIndex){
						this.setChecked(checked, true);
					}
				});
			}
		}, '-'];
		Ext.each(fields, function(f){
			menuItems.push({
				text: f.header,
				checked: f.searchable || !f.hidden,
				_dataIndex: f.dataIndex
			});
		});
		this.searchOptionsMenu = new Ext.menu.Menu({
			defaults: {
				xtype: 'menucheckitem',
				hideOnClick: false
			},
			items: menuItems
		});
	},
	
	setBaseParams: function(){
		this.originalBaseParams  = Ext.apply({},this.store.baseParams);
	},
	
	/**
	 * @function initComponent
	 */
	initComponent: function(){
		
		this.addEvents('search');
		
		// Build the options menu, when the grid finishes loading its data:
		this.store.on({
			'load': {
				scope: this,
				single: true,
				fn: function(){
					if (!this.grid) {
						this.grid = this.ownerCt; // Can this be done in a better way?
					}
					if(!this.grid){
						return;
					}
					this.grid.getColumnModel().on('hiddenchange', function(){
						this.buildSearchOptionsMenu();
					}, this);
					this.buildSearchOptionsMenu();
				}
			}
		});
		
		// Add menu and searchfield to the toolbar: 
		var scope = this;
		this.layout = 'hbox';
		this.items = [{
			xtype: 'tbbutton',
			ref: 'searchoptionsbtn',
			iconCls: 'icon-searchoptions',
			//cls: 'garp-searchoptions',
			tooltip: __('Zoekopties'),
			width: 22,
			flex: 0,
			scope: this,
			handler: function(btn){
				this.searchOptionsMenu.show(btn.el);
			}
		}, this.searchField = new Ext.ux.form.SearchField({
			xtype: 'twintrigger',
			flex: 1,
			//style: 'paddingLeft: 22px;',
			store: this.store,
			value: Ext.isObject(this.store.baseParams.query) ? '' : this.store.baseParams.query,
			listeners:{
				'change': function(){
					var v = this.getValue();
					if(v.length <1){
						this.removeClass('has-search');
					} else {
						this.addClass('has-search');
					}
					
				}
			},
			onTrigger1Click: function(){
				if (this.hasSearch) {
					this.triggers[0].hide();
					this.el.dom.value = '';
					scope.makeQuery('');
					this.removeClass('has-search');
				}
			},
			onTrigger2Click: function(){
				var v = this.getRawValue();
				if (v.length < 1) {
					this.onTrigger1Click();
					return;
				}
				this.triggers[0].show();
				this.hasSearch = true;
				scope.makeQuery(v);
				
			}
		})];
		
		Ext.ux.Searchbar.superclass.initComponent.call(this);
	},
	
	/**
	 * @function blur
	 * Extended blur to cause searchField to blur as well
	 */
	blur: function(){
		this.searchField.blur();
		Ext.ux.Searchbar.superclass.blur.call(this);
	},
	
	/**
	 * Sets the UI as if one searched for an id
	 * @param {Object} id
	 */
	searchById: function(id){
		// show the id in the search bar & update menu to only set 'id' checked 
					var bb = this;
					var sf = this.searchField;
					var sm = this.searchOptionsMenu;
					sf.setValue(id);
					sf.triggers[0].show();
					sf.hasSearch = true;
					sf.fireEvent('change');
					sm.items.each(function(item){
						if (item.setChecked) {
							item.setChecked(item.text == 'id' ? true : false);
						}
					});
					bb.fireEvent('change');

	},
	
	/**
	 * @function afterRender
	 */
	afterRender: function(){
		var kn = new Ext.KeyNav(this.getEl(), {
			'esc': function(){
				this.blur();
				this.fireEvent('defocus');
			},
			scope: this
		});
		this.setBaseParams();
		Ext.ux.Searchbar.superclass.afterRender.call(this);
	}
});
 
 // register the component to allow lazy instantiating:
Ext.reg('searchbar', Ext.ux.Searchbar); 