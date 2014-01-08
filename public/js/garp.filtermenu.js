/**
 * @class Garp.FilterMenu
 * @plugin Ext.PagingToolbar
 * @author Peter
 *
 * Creates a menu button for the paging toolbar, with basic filter options
 *
 */
Garp.FilterMenu = function(){
	
	this.init = function(tb){
		
		/**
		 * Reference to the toolbar
		 */
		this.tb = tb;
		
		this.defaultFilter = {
			text: __('All'),
			ref: 'all'
		};
		
		/**
		 * Resets the button and the menu. (Provides only visual feedback: no filter applied)
		 */
		this.resetUI = function(){
			this.tb.filterBtn.setIconClass('icon-filter-off');
			this.tb.filterBtn.menu.all.setChecked(true);
			this.tb.filterStatus.hide();
		};
		
		/**
		 * Checks the model for possible fields to filter on. If it's not in the model, we can't put it as a filter option in the menu
		 */
		this.buildMenu = function(){
			
			var menuOptions = [];
			if(Garp.dataTypes[Garp.currentModel].filterMenu){
				Ext.each(Garp.dataTypes[Garp.currentModel].filterMenu, function(i){
					menuOptions.push(i);
				});
			}
			var model = Garp.dataTypes[Garp.currentModel];
			menuOptions.push(this.defaultFilter);
			if(model.getColumn('published')){
				menuOptions.push({
					text: __('Drafts'),
					ref: 'drafts'
				},{
					text: __('Published'),
					ref: 'published'
				});
			}
			if (model.getColumn('author_id')) {
				menuOptions.push({
					text: __('My items'),
					ref: 'my'
				});
			}
			
			Ext.each(menuOptions, function(option){
				if(typeof option.isDefault !== 'undefined' && option.isDefault){
					this.defaultFilter = option;
					return false;
				}
			}, this);
			
			return menuOptions;
		};
		
		/**
		 * Applies the selected filter and reflects the UI
		 * @param {Object} menu item
		 */
		function applyFilter(item){
			
			var grid = tb.ownerCt;
			var storeParams = grid.getStore().baseParams;
			
			if (!storeParams.query) {
				storeParams.query = {};
			}
			
			delete storeParams.query.online_status;
			delete storeParams.query.author_id;
			
			if(typeof Garp.dataTypes[Garp.currentModel].clearFilters == 'function'){
				Garp.dataTypes[Garp.currentModel].clearFilters();
			} else if (item.ref == 'all'){
				storeParams.query = {};
			}
			
			switch (item.ref) {
				case 'published':
					Ext.apply(storeParams.query, {
						online_status: '1'
					});
					break;
				case 'drafts':
					Ext.apply(storeParams.query, {
						online_status: '0'
					});
					break;
				case 'my':
					Ext.apply(storeParams.query, {
						author_id: Garp.localUser.id
					});
					break;
				default:
					break;
			}
			grid.getStore().reload();
			return true;
		}
		
		/**
		 * Build the menu 
		 */
		this.filterMenu = this.buildMenu();
		
		this.filterStatus = tb.add({
			ref: 'filterStatus',
			text: this.defaultFilter.ref !== 'all' ? this.defaultFilter.text : '',
			xtype: 'tbtext',
			hidden: (this.defaultFilter.ref === 'all')
		});
		
		/**
		 * Create the button
		 */
		this.filterBtn = tb.add({
			ref: 'filterBtn',
			tooltip: 'Filter',
			iconCls: (this.defaultFilter.ref == 'all' ? 'icon-filter-off' : 'icon-filter-on'),
			hidden: (this.filterMenu.length <= 1),
			menu: {
				defaultType: 'menucheckitem',
				defaults: {
					group: 'filter',
					handler: applyFilter,
					scope: this
				},
				items: this.filterMenu
			}
		});
		
		// Set default as checked:
		this.filterBtn.menu.find('text', this.defaultFilter.text)[0].setChecked(true);
		
		// Reflect UI on menu changes:
		this.filterBtn.menu.on('itemclick', function(item, evt){
			if(item.ref === 'all'){
				this.filterStatus.update('');
				this.filterStatus.hide();
				this.filterBtn.setIconClass('icon-filter-off');
				return;
			} else if (item.text) {
				this.filterStatus.update(item.text);
			}
			this.filterStatus.show();
			this.filterBtn.setIconClass('icon-filter-on');
		}, this);
		
		// Make sure we don't end up with an "No items to display" AND a filter Status text: 
		this.tb.on('change', function(tb){
			if(tb.store.getCount() === 0){
				this.filterStatus.hide();
			} else {
				this.filterStatus.show();
			}
		});
	};
};