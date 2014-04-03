/**
 * @class PagingSearchBar
 * 
 * PagingToolbar with searchfield and option menu
 * 
 * @extends Ext.PagingToolbar   
 * @author: Peter
 */

Ext.ns('Ext.ux');

Ext.ux.PagingSearchBar = Ext.extend(Ext.PagingToolbar, {

	/**
	 * @cfg height
	 * fixed to enable show/hide
	 *
	 * @TODO: Not sure why we need to hardcode this. Bug in Ext?
	 */
	height: 27,
	
	/**
	 * @cfg displayInfo
	 * override pagingtoolbar defaults:
	 */
	displayInfo: true,
	
	/**
	 * @cfg enableOverflow
	 * override pagingtoolbar defaults:
	 */
	enableOverflow: true,
	
	/**
	 * @cfg grid
	 * reference to the gridpanel this toolbar is bound to:
	 */
	grid: null,
	
	
	/**
	 * @function _setSearchFieldWidth
	 * @private
	 *
	 * sets the width of the searchfield
	 */
	_resizeSearchFieldWidth: function(){
		var w = this.getWidth();
		 
		if (this.searchField) {
			var margin = this.displayInfo ? 460 : 230;
			this.searchField.setWidth(w - margin);
			
			if(w< 320){
				this.searchoptionsbtn.hide();
			} else {
				this.searchoptionsbtn.show();	
			}
		}
	},
	
	/**
	 * @function makeQuery
	 * 
	 * preserves possible "Model.id" queryStrings for relatePanel
	 * 
	 * @param {Object} queryStr
	 */
	makeQuery: function(queryStr){
		var q = this.grid.store.baseParams.query;
		var dt = new Ext.util.MixedCollection();
		dt.addAll(Garp.dataTypes);
		var preserve = {};
		
		if(Ext.isObject(q)){
			dt.eachKey(function(key){
				key = key + '.id';
				if(q[key]){
					preserve[key] = q[key]; 
				}
			});
		}
		this.grid.store.baseParams.query = Ext.apply(this.convertQueryString(queryStr, this.getSelectedSearchFields()), preserve);
		this.moveFirst();
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
		var obj = {};
		Ext.each(fields, function(f){
			obj[f + ' like'] = q;
		});
		return {
			'or': obj
		};
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
		if(!this.grid) return;
		Ext.each(this.grid.getColumnModel().config, function(col){
			if (col.dataIndex !== 'relationMetadata') {
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
			text: __('Zoeken in:')
		}, '-'];
		Ext.each(fields, function(f){
			menuItems.push({
				text: f.header,
				_dataIndex: f.dataIndex
			});
		});
		this.searchOptionsMenu = new Ext.menu.Menu({
			defaults: {
				xtype: 'menucheckitem',
				hideOnClick: false,
				checked: true
			},
			items: menuItems
		});
	},
	
	/**
	 * @function initComponent
	 */
	initComponent: function(){
		
		// Build the options menu, when the grid finishes loading its data:
		this.store.on({
			'load': {
				scope: this,
				single: true,
				fn: function(){
					if (!this.grid) {
						this.grid = this.ownerCt; // Can this be done in a better way?	
					}
					this.buildSearchOptionsMenu();
				}
			}
		});
		
		// Add menu and searchfield to the toolbar: 
		var scope = this;
		this.items = ['->', ' ', {
			xtype: 'tbbutton',
			ref: 'searchoptionsbtn',
			iconCls: 'icon-searchoptions',
			cls: 'garp-searchoptions',
			tooltip: __('Zoekopties'),
			scope: this,
			handler: function(btn){
				this.searchOptionsMenu.show(btn.el);
			}
		}, this.searchField = new Ext.ux.form.SearchField({
			xtype: 'twintrigger',
			style: 'paddingLeft: 22px;',
			store: this.store,
			value: this.store.baseParams.query,
			listeners:{
				'change': function(){
					var v = this.getValue();
					if(v.length <1){
						this.removeClass('has-search');
					} else {
						this.addClass('has-search')
					}
					
				}
			},
			onTrigger1Click: function(){
				if (this.hasSearch) {
					this.triggers[0].hide();
					this.el.dom.value = '';
					scope.makeQuery('');
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
				
			},
			width: 80
		})];
		
		
		/**
		 * listen to various events:
		 */
		// Resize searchField:
		
		this.on({
			'resize': this._resizeSearchFieldWidth,
			'afterlayout': this._resizeSearchFieldWidth
		}, this);
		
		
		// Hide when not necessary:
		// Disabled. See JIRA issue [GARP-40]
		
		/*
		this.on('change', function(){
			if (this.store.getTotalCount() < this.pageSize) {
				if (this.searchField && this.searchField.getValue() == '') {
					if (!this.hidden) {
						this.hide();
						this.doLayout(false, true);
					}
				} else {
					if (this.hidden) {
						this.show();
						this.doLayout(false, true);
					}
				}
			}
		}, this);
		*/
		Ext.ux.PagingSearchBar.superclass.initComponent.call(this);
	},
	
	/**
	 * @function blur
	 * Extended blur to cause searchField to blur as well
	 */
	blur: function(){
		this.searchField.blur();
		Ext.ux.PagingSearchBar.superclass.blur.call(this);
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
		Ext.ux.PagingSearchBar.superclass.afterRender.call(this);
	}
});
 
 // register the component to allow lazy instantiating:
Ext.reg('pagingsearchbar', Ext.ux.PagingSearchBar); 