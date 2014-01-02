/**
 * @class Garp.Toolbar
 * @extends Ext.Toolbar
 * @author: Peter
 * 
 * @description Garp.Toolbar, main Garp toolbar, included in Garp.viewport
 */
Garp.Toolbar = Ext.extend(Ext.Toolbar, {

	/**
	 * @function addColumnMenu
	 *
	 * add ColumnMenu to the extraMenu
	 * @param {Object} columnMenu
	 */
	addColumnMenu: function(grid){
		var colModel = grid.getColumnModel(),
            colCount = colModel.getColumnCount(),
			colMenu = new Ext.menu.Menu({id: this.id + '-hcols-menu'}),
            i;

		function resetColMenu(){
			colMenu.removeAll();
			for (i = 0; i < colCount; i++) {
				if (colModel.config[i].hideable !== false) {
					colMenu.add(new Ext.menu.CheckItem({
						text: colModel.getColumnHeader(i),
						itemId: 'col-' + colModel.getColumnId(i),
						checked: !colModel.isHidden(i),
						disabled: colModel.config[i].hideable === false,
						hideOnClick: false
					}));
				}
			}
		}
		resetColMenu();
		
		colMenu.on({
			'scope': this,
			'beforeshow': resetColMenu,
			'itemclick': function(item){
				itemId = item.getItemId();
				var index = colModel.getIndexById(itemId.substr(4));
				if (index != -1) {
					colModel.setHidden(index, item.checked);
				}
			}
		});
		
		this.viewMenu.menu.add({
			ref: 'columnMenu',
			hideOnClick: false,
			text: __('Columns'),
			menu: colMenu,
			iconCls: 'x-cols-icon'
		});
	},
	
	/**
	 * @function removeColumnMenu
	 *
	 * removes ColumnMenu from the extraMenu
	 */
	removeColumnMenu: function(){
		if (this.viewMenu.menu.columnMenu) {
			this.viewMenu.menu.remove(this.viewMenu.menu.columnMenu);
		}
	},
	
	
	initComponent: function(){
		Ext.apply(this, {
			style: 'padding:0px 10px 0px 10px;border:0;',
			cls: 'garp-main-toolbar',
			
			items: [Garp.modelMenu, {
				xtype: 'tbspacer'
			}, {
				xtype: 'buttongroup',
				frame: false,
				cls: 'no-menu-arrows',
				defaults: {
					scale: 'small',
					arrowAlign: 'top'
				},
				columns: 7,
				items: [{
					text: __('File'),
					ref: '../fileMenu',
					menu: new Ext.menu.Menu({
						items: [{
							text: __('New'),
							iconCls: 'icon-new',
							ref: 'newButton', // makes it Garp.toolbar.newButton
							handler: function(){
								this.fireEvent('new');
							},
							scope: this
						}, {
							text: __('Duplicate'),
							iconCls: 'icon-duplicate',
							ref: 'duplicateButton',
							disabled: true,
							hidden: true
						}, {
							text: __('Delete'),
							iconCls: 'icon-delete',
							ref: 'deleteButton',
							handler: function(){
								this.fireEvent('delete');
							},
							scope: this
						}, '-', {
							text: __('Save'),
							iconCls: 'icon-save',
							ref: 'saveButton',
							handler: function(){
								this.fireEvent('save-all');
							}
						}, '-', {
							text: __('Import') + "&hellip;",
							iconCls: 'icon-import',
							ref: 'importButton',
							handler: function(){
								if(!Garp.currentModel){
									return;
								}
								var win = new Garp.ImportWindow();
								win.show();
							}
						},{
							text: __('Export') + "&hellip;",
							iconCls: 'icon-export',
							ref: 'exportButton',
							handler: function(){
								if(!Garp.currentModel){
									return;
								}
								var win = new Garp.ExportWindow();
								win.show();
							}
						}, {
							text: __('Print'),
							iconCls: 'icon-print',
							ref: 'printButton',
							handler: function(){
								if (!Garp.currentModel) {
									return;
								}
								
								if (Garp.gridPanel.getSelectionModel().getCount() == 1) {
									
									Ext.select('body').addClass('print-form');
									Garp.gridPanel.ownerCt.collapse();
									Garp.viewport.doLayout();
									setTimeout(function(){
										window.print();
										
										Garp.gridPanel.ownerCt.expand();
										Ext.select('body').removeClass('print-form');
									}, 500);
									
									
								} else {
									Ext.select('body').addClass('print-grid');
									
									var pw = Garp.gridPanel.ownerCt.getWidth();
									Garp.gridPanel.getSelectionModel().clearSelections();
									Garp.formPanel.ownerCt.collapse();
									Garp.gridPanel.ownerCt.collapse();
									Garp.gridPanel.ownerCt.setWidth(640);
									Garp.gridPanel.ownerCt.expand();
									
									var el = Ext.select('.x-grid3-scroller').first();
									var w = el.getStyle('width');
									var h = el.getStyle('height');
									
									el.setStyle({
										'overflow': 'visible',
										'position': 'fixed',
										'height': 'auto'
									});
									
									setTimeout(function(){
										window.print();
										
										el.first().setStyle({
											'overflow': 'auto',
											'overflow-x': 'hidden',
											'position': 'relative',
											'width': w,
											'height': h
										});
										
										Garp.gridPanel.ownerCt.setWidth(pw);
										Garp.formPanel.ownerCt.expand();
										Garp.gridPanel.ownerCt.expand();
										Garp.viewport.doLayout();
										
										Ext.select('body').removeClass('print-grid');
										
									}, 500);
								}
								
							}
						},'-', {
							text: __('Log out'),
							iconCls: 'icon-logout',
							ref: 'logoutButton',
							handler: function(){
								this.fireEvent('logout');
							},
							scope: this
						}]
					})
				}, {
					text: __('Edit'),
					ref: '../editMenu',
					menu: new Ext.menu.Menu({
						items: [{
							text: __('Select All'),
							ref: 'selectAllButton',
							handler: function(){
								Garp.gridPanel.selectAll();
							}
						}]
					})
				}, {
					text: __('View'),
					ref: '../viewMenu',
					menu: new Ext.menu.Menu({
						items: [{
							text: __('Refresh'),
							ref: 'refreshButton',
							iconCls: 'icon-refresh',
							handler: function(){
								if(!Garp.gridPanel.getStore){
									return;
								}
								Garp.gridPanel.getStore().reload();
							}
						}, {
							text: __('Open in new window'),
							ref: 'openNewWindowButton',
							iconCls: 'icon-open-new-window',
							handler: function(){
								this.fireEvent('open-new-window');
							},
							scope: this
						}, '-', {
							text: __('Number of items per page'),
							iconCls: 'icon-number-of-items',
							menu: new Ext.menu.Menu({
								defaultType: 'menucheckitem',
								defaults: {
									group: 'pageSize',
									handler: function(item){
										if(!Garp.gridPanel.getStore){
											return;
										}
										Garp.pageSize = parseInt(item.text);
										var bbar = Garp.gridPanel.getBottomToolbar();
										bbar.pageSize = Garp.pageSize;
										bbar.doLoad(0);
									}
								},
								items: [{
									text: '10',
									checked: Garp.pageSize === 10
								}, {
									text: '25',
									checked: Garp.pageSize === 25
								}, {
									text: '50',
									checked: Garp.pageSize === 50
								}, {
									text: '100',
									checked: Garp.pageSize === 100
								}]
							})
						}]
					})
				}, {
					text: __('Help'),
					ref: '../helpMenu',
					hidden: true,
					menu: new Ext.menu.Menu({
						items: [{
							text: __('About Garp'),
							handler: function(){
								var w = new Ext.Window({
									width: 320,
									modal: true,
									title: __('About Garp'),
									height: 130,
									buttonAlign: 'center',
									bodyStyle: 'margin: 12px;',
									html: '<p style="font-weight: bold; font-size: 14px;margin-bottom: 10px;">Garp 3</p>Build 3.1.$svn$<br> &copy; 2009 - 2011 - Grrr Amsterdam',
									buttons: [{
										text: 'Ok',
										handler: function(){
											w.close();
										}
									}]
								});
								w.show();
							}
						}]
					})
				}]
			}, {
				xtype: 'spacer',
				ref: '../separator'
			}, ' ', '->', {
				text: String.format('{0} {1}', __('Welcome'), Garp.localUser.nick ? Garp.localUser.nick : ''),
				xtype: 'tbtext'
			}]
		});
		Garp.Toolbar.superclass.initComponent.call(this)
	}
});