/**
 * Garp infoPanel. Simplistic panel with info about the currently selected model. setInfo gets called from garp.js->changeModel
 */

Garp.InfoPanel = Ext.extend(Ext.Panel, {
	/**
	 * setInfo. Updates this panel's HTML
	 * @param {Object} model
	 */
	setInfo: function(model){
		this.remove(this.innerpanel);
		this.add([{
			xtype: 'container',
			ref: 'innerpanel',
			cls: 'infopanel cms-branding',
			border: false,
			defaults: {
				border: false
			},
			items: [{
				html: 	'<div class="x-panel-header x-panel-header-noborder x-unselectable" style="-moz-user-select: none;">'+
						'<img class="x-panel-inline-icon ' + model.iconCls + '" src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" alt="">' +
						'<span class="x-panel-header-text">' + __(model.text) + '</span></div>' +
						(model.description ? '<div class="description">' +  __(model.description) + '</div>' : '')
			}, {
				xtype:'box',
				ref: '../count',
				cls: 'total',
				tpl: model.countTpl
			}]
		}]);
	},
	
	updateCount: function(count){
		this.count.update({
			count: count
		});
	},
	
	clearInfo: function(){
		this.update('');
	},
	
	html: '',
	bodyStyle: 'padding: 30px; padding-bottom: 15px;',
	layout:'fit',
	
	listeners:{
		'show': function(){
			if (Garp.history && Garp.currentModel) {
				Garp.history.pushState({
					model: Garp.currentModel
				});
			}
		}
	}
	
});