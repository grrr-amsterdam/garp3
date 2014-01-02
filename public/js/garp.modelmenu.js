/**
 * @class Garp.ModelMenu
 * @author Peter
 * garp.modelmenu.js
 * 
 */

Garp.ModelMenu = function(cfg){

	var menuItems = [{
		text: '<b>' + __('Welcome') + '</b>',
		handler: function(){
			if (Garp.checkForModified()) {
				return;
			}
			this.setText(__('Choose content type'));
			this.setIconClass('icon-no-model');
			Garp.setFavicon();
			
			Garp.viewport.formPanelCt.hide();
			if (Garp.formPanel) {
				Garp.viewport.formPanelCt.remove(Garp.formPanel);
			}
			Garp.viewport.gridPanelCt.removeAll(true);
			Garp.gridPanel = new Garp.WelcomePanel({});
			Garp.viewport.gridPanelCt.add(Garp.gridPanel);
			
			Garp.viewport.infoPanel.clearInfo();
			Garp.viewport.infoPanel.show();
			Garp.viewport.infoPanel.ownerCt.expand();
			Garp.viewport.infoPanel.ownerCt.show();
		
			Garp.viewport.doLayout();
		},
		scope: this
	},'-'];
	var models = (function(){
		var models = [];
		var prevModel;
		for (var i in Garp.dataTypes) {
			var model = Garp.dataTypes[i];
			if(model == '-'){
				if (prevModel != '-') {
					models.push('-');
				}
			} else{
				(function(i){
					models.push({
						hidden: model.hidden,
						text: __(model.text),
						name: model.text,
						iconCls: model.iconCls,
						handler: function(){
							Garp.viewport.formPanelCt.show();
							Garp.eventManager.fireEvent('modelchange', i, true);
						}
					});
				})(i);
			}
			prevModel = model;
		}
		return models;
	})();
	menuItems.push(models);
	
	Garp.ModelMenu.superclass.constructor.call(this, Ext.applyIf(cfg, {
		cls: 'garp-model-menu',
		text: __('Choose content type'),
		iconCls: 'icon-no-model',
		menu: new Ext.menu.Menu({
			items: menuItems
		})
	}))
};

Ext.extend(Garp.ModelMenu, Ext.Button, {});