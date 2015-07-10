/**
 * @class Garp.ModelMenu
 * @author Peter
 * garp.modelmenu.js
 *
 */

Garp.ModelMenu = function(cfg) {
	Ext.apply(this, cfg);

	var definedModelsInMenu = extractConfiguredModelNames(this.menuItems);
	this.menuItems = this.menuItems.concat(Object.keys(Garp.dataTypes).filter(function(modelName) {
		return definedModelsInMenu.indexOf(modelName) == -1;
	}));

	var menuItems = [];
	menuItems.push(createMenuItemsForModels(this.menuItems));

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

	function extractConfiguredModelNames(menuItems) {
		return menuItems.filter(function(item) {
			return !Ext.isObject(item);
		}).concat(Ext.flatten(menuItems.filter(function(item) {
			return Ext.isObject(item);
		}).map(function(item) {
			return extractConfiguredModelNames(item.menu);
		})));
	}

	function createMenuItemsForModels(menuItems) {
		var model, models = [];
		Ext.each(menuItems, function(model) {
			if (typeof model.menu !== 'undefined') {
				models.push({
					iconCls: model.iconCls || '',
					text: model.text || 'submenu',
					menu: createMenuItemsForModels(model.menu)
				});
			} else if (model == '-') {
				// Check if models are already in array, otherwise a separator doesn't make sense.
				if (models.length > 0 && models[models.length - 1] != '-') {
					models.push('-');
				}
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
	};
};

Ext.extend(Garp.ModelMenu, Ext.Button, {});
