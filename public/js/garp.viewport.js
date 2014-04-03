/**
 * garp.viewport.js
 * 
 */

Garp.Viewport = function(cfg){
	
	Garp.toolbar = new Garp.Toolbar();
	
	Garp.Viewport.superclass.constructor.call(this, Ext.applyIf(cfg, {
		layout: 'border',
		stateful: true,
		stateId:'viewport',
		items: [{
			region: 'north',
			border: false,
			height: 28,
			bodyCssClass: 'garp-bg',
			border: false,
			cls: 'toolbarCt',
			bbar: Garp.toolbar
		}, {
			region: 'center',
			layout: 'border',
			border: false,
			defaults: {
				split: true,
				animCollapse: false,
				border: true,
				frame: false
			},
			items: [{
				ref: '../gridPanelCt',
				region: 'west',
				cls: 'gridPanelCt',
				layout: 'fit',
				margins: '10 2 10 10',
				width: 300,
				minHeight: 200,
				height: 200,
				minWidth: 300,
				collapseMode: 'mini',
				collapsible: true,
				header: false,
				stateful: true,
				stateId: 'gridPanelCt',
				items: [Garp.gridPanel]
			}, {
				ref: '../formPanelCt',
				region: 'center',
				cls: 'formPanelCt',
				stateful: true,
				stateId: 'formPanelCt',
				layout: 'card',
				activeItem: 0,
				defaults:{
					border: false
				},			
				margins: '10 10 10 2',
				items: [new Garp.InfoPanel({
					ref: '../../infoPanel'
				})]
			}]
		}]
	}));
}

Ext.extend(Garp.Viewport, Ext.Viewport, {});