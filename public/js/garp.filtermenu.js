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
		
		/**
		 * Resets the button and the menu. (Provides visual feedback: no filter applied)
		 */
		this.resetUI = function(){
			this.tb.filterBtn.setIconClass('icon-filter-off');
			this.tb.filterBtn.menu.all.setChecked(true);
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
			menuOptions.push({
				text: __('All'),
				ref: 'all',
				checked: true
			});
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
			
			tb.filterBtn.setIconClass('icon-filter-off');
			delete storeParams.query.online_status;
			delete storeParams.query.author_id;
			
			if(item.ref != 'all'){
				tb.filterBtn.setIconClass('icon-filter-on');
			}
			
			if(typeof Garp.dataTypes[Garp.currentModel].clearFilters == 'function'){
				Garp.dataTypes[Garp.currentModel].clearFilters();
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
		
		/**
		 * Create the button
		 */
		this.filterBtn = tb.add({
			ref: 'filterBtn',
			tooltip: 'Filter',
			iconCls: 'icon-filter-off',
			hidden: this.filterMenu.length <= 1,
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
	};
};