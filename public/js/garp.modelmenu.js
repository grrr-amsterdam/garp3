/**
 * @class Garp.ModelMenu
 * @author Peter
 * garp.modelmenu.js
 * 
 */

Garp.ModelMenu = function(cfg){
	
	Ext.apply(this, cfg);
	
	var menuItems = [];
	
	for (var key in Garp.dataTypes){
		if(this.menuItems.indexOf(key) == -1){
			this.menuItems.push(key);
		}	
	}
	
	menuItems.push((function(){
		var model, models = [];
		Ext.each(this.menuItems, function(model){
			if ((model == '-') && (models.length > 0) && models[models.length - 1] != '-') {
				models.push('-');
			} else {
				if(!Garp.dataTypes[model]){
					throw 'Oops! JS model "' + model + '" not found! Is it spawned and bugfree?';
				}
				var dataType = Garp.dataTypes[model];
				if(!Garp[model]){
					throw 'Oops! dataType "' + model + '" not found! Does it exist in the smd?';
				}
				if (dataType.setupACL(Garp[model])) {
					dataType.fireEvent('init');	
					models.push({
						hidden: dataType.hidden,
						text: __(dataType.text),
						name: dataType.text,
						iconCls: dataType.iconCls,
						handler: function(){
							Garp.viewport.formPanelCt.show();
							Garp.viewport.gridPanelCt.expand();
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
		text: __('Content'),
		iconCls: 'icon-no-model',
		menu: new Ext.menu.Menu({
			items: menuItems
		})
	}));

	this.on('afterrender', function(){
		this.getEl().on('click', function(){
			Garp.viewport.gridPanelCt.expand();
		});
	}, this);
	
	
};

Ext.extend(Garp.ModelMenu, Ext.Button, {});
