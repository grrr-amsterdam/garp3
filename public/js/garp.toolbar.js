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
	},
	
	/**
	 * @function removeColumnMenu
	 *
	 * removes ColumnMenu from the extraMenu
	 */
	removeColumnMenu: function(){
	},
	
	
	initComponent: function(){
		Ext.apply(this, {
			style: 'padding:0px 10px 0px 10px;border:0;',
			cls: 'garp-main-toolbar cms-branding-small',
			
			items: [Garp.modelMenu, '-', {
				xtype: 'tbspacer'
			}, {
				text: __('New'),
				iconCls: 'icon-new',
				ref: 'newButton', // makes it Garp.toolbar.newButton
				handler: function(){
					this.fireEvent('new');
				},
				scope: this
			}, {
				text: __('Delete'),
				iconCls: 'icon-delete',
				ref: 'deleteButton',
				handler: function(){
					this.fireEvent('delete');
				},
				scope: this
			}, ' ', {
				xtype: 'tbseparator',
				ref: 'separator'
			}, {
				text: __('More'),
				iconCls: 'icon-extra',
				ref: 'extraMenu',
				menu: new Ext.menu.Menu({
					items: [{
						text: __('Import') + "&hellip;",
						iconCls: 'icon-import',
						ref: 'importButton',
						hidden: true,
						handler: function(){
							if (!Garp.currentModel) {
								return;
							}
							var win = new Garp.ImportWindow();
							win.show();
						}
					}, {
						text: __('Export') + "&hellip;",
						iconCls: 'icon-export',
						ref: 'exportButton',
						hidden: true,
						handler: function(){
							if (!Garp.currentModel) {
								return;
							}
							var win = new Garp.ExportWindow();
							win.show();
						}
					}, {
						text: __('Print'),
						iconCls: 'icon-print',
						ref: 'printButton',
						hidden: true,
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
					}, '-', {
						hidden: !((document.fullScreenElement && document.fullScreenElement !== null) ||   
      								(!document.mozFullScreen && !document.webkitIsFullScreen)),
						text: __('Full Screen'),
						iconCls: 'icon-fullscreen',
						handler: function(){
							var d = document, de = d.documentElement;
							if (d.mozFullScreen || d.webkitIsFullScreen) {
								if (d.mozCancelFullScreen) {
									d.mozCancelFullScreen();
								} else if (d.webkitCancelFullScreen){
									d.webkitCancelFullScreen();
								}
							} else {
								if (de.mozRequestFullScreen) {
									de.mozRequestFullScreen();
								} else if (de.webkitRequestFullScreen){
									de.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);  
								}
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
			}]
		});
		
		Garp.Toolbar.superclass.initComponent.call(this);
	}
});