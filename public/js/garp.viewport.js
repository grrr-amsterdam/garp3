/**
 * garp.viewport.js
 * 
 */

Garp.Viewport = function(cfg){

	Garp.Viewport.superclass.constructor.call(this, Ext.applyIf(cfg || {}, {
		layout: 'border',
		stateful: false,
		defaults: {
			split: true,
			animCollapse: false,
			border: false,
			frame: false
		},
		//stateId:'viewport',
		items: [{
			region: 'north',
			border: false,
			height: 40,
			bodyCssClass: 'garp-bg',
			border: false,
			cls: 'toolbarCt',
			items: Garp.toolbar = new Garp.Toolbar()
		}, {
			ref: 'gridPanelCt',
			region: 'west',
			cls: 'gridPanelCt',
			layout: 'fit',
			xtype: 'panel',
			margins: '0 0 2 2',
			width: 360,
			minWidth: 360,
			height: 200,
			minHeight: 200,
			collapseMode: 'mini',
			collapsible: true,
			header: false,
			stateful: true,
			stateId: 'gridPanelCt',
			margins: '0 0 4 4',
			items: [Garp.gridPanel]
		}, {
			ref: 'formPanelCt',
			region: 'center',
			cls: 'formPanelCt',
			layout: 'card',
			xtype: 'container',
			layoutConfig: {
				layoutOnCardChange: true
			},
			activeItem: 0,
			border: false,
			defaults: {
				border: false
			},
			margins: '0 4 4 0',
			items: [Garp.infoPanel = new Garp.InfoPanel({
				itemId: 0,
				ref: '../infoPanel'
			})]
		}]
	}));
};

Ext.extend(Garp.Viewport, Ext.Viewport, {});