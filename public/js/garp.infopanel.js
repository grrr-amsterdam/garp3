/**
 * Garp infoPanel. Simplistic panel with info about the currently selected model. setInfo gets called from garp.js->changeModel
 */

Garp.InfoPanel = Ext.extend(Ext.Container, {
	
	/**
	 * setInfo. Updates this panel's HTML
	 * @param {Object} model
	 */
	setInfo: function(model){
		this.remove(this.innerpanel);
		this.add([{
			xtype: 'container',
			//iconCls: model.iconCls, //html,
			//title: __(model.text),
			ref: 'innerpanel',
			cls: 'infopanel',
			border: false,
			defaults: {
				border: false
			},
			items: [{
				html: 	'<div id="ext-gen104" class="x-panel-header x-panel-header-noborder x-unselectable" style="-moz-user-select: none;">'+
						'<img class="x-panel-inline-icon ' + model.iconCls + '" src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" alt="">' +
						'<span id="ext-gen107" class="x-panel-header-text">' + __(model.text) + '</span></div>' +
						'<div>' +  __(model.description) + '</div>',
			}, {
				xtype:'box',
				ref: '../count',
				style: 'margin: 10px 0 20px 0',
				tpl: model.countTpl
			}, (!model.disableCreate ? {
				xtype: 'button',
				iconCls: 'icon-new',
				text: __('New'),
				ref: 'nwButton',
				handler: function(){
					Garp.eventManager.fireEvent('new');
				}
			} : {})]
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
	bodyStyle: 'padding: 30px;'
	
});