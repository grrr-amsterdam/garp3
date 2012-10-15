/**
 * @class Garp.ModelMenu
 * @author Peter
 * garp.modelmenu.js
 * 
 */

Garp.ModelMenu = function(cfg){
	
	Ext.apply(this, cfg);
	
	var menuItems = [{
		text: '<b>' + __('Welcome') + '</b>',
		handler: function(){
			if (Garp.checkForModified()) {
				return;
			}
			this.setText(__('Choose content type'));
			this.setIconClass('icon-no-model');
			Garp.setFavicon();
			
			var menu = Garp.toolbar.extraMenu.menu;
			menu.importButton.hide();
			menu.exportButton.hide();
			menu.printButton.hide();
			Garp.toolbar.newButton.hide();
			Garp.toolbar.deleteButton.hide();
			Garp.toolbar.separator.hide();
			Garp.viewport.formPanelCt.hide();
			if (Garp.formPanel) {
				Garp.viewport.formPanelCt.remove(Garp.formPanel);
			}
			Garp.viewport.gridPanelCt.removeAll(true);
			Garp.gridPanel = new Garp.WelcomePanel({});
			Garp.viewport.gridPanelCt.add(Garp.gridPanel);
			Garp.infoPanel.show();
			Garp.infoPanel.ownerCt.show();
			Garp.viewport.doLayout();
			Garp.infoPanel.clearInfo();
		},
		scope: this
	},'-'];
	
	for (var key in Garp.dataTypes){
		if(this.menuItems.indexOf(key) == -1){
			this.menuItems.push(key);
		}	
	}
	
	menuItems.push((function(){
		var model, models = [];
		Ext.each(this.menuItems, function(model){
			if (model == '-') {
				models.push('-');
			} else {
				var dataType = Garp.dataTypes[model];
				if (dataType.setupACL(Garp[model])) {
					dataType.fireEvent('init');	
					models.push({
						hidden: dataType.hidden,
						text: __(dataType.text),
						name: dataType.text,
						iconCls: dataType.iconCls,
						handler: function(){
							Garp.viewport.formPanelCt.show();
							Garp.eventManager.fireEvent('modelchange', true, model, null, null);
						}
					});
				} else {
					delete Garp.dataTypes[dataType.text];
				}
			}
		});
		return models;
	}).call(this));
	
	Garp.ModelMenu.superclass.constructor.call(this, Ext.applyIf(cfg, {
		cls: 'garp-model-menu',
		text: __('Choose content type'),
		iconCls: 'icon-no-model',
		menu: new Ext.menu.Menu({
			items: menuItems
		})
	}));
};

Ext.extend(Garp.ModelMenu, Ext.Button, {});