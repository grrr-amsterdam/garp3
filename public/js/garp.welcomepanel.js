/**
 * garp.welcomepanel.js
 * 
 * Convenience Class, creates a panel with a message from HTML markup in de view.
 * 
 * @class Garp.WelcomePanel
 * @extends Panel
 * @author Peter
 */

Garp.WelcomePanel = function(cfg){
	this.html = Ext.getDom('welcome-panel-content').innerHTML;
	Garp.WelcomePanel.superclass.constructor.call(this, cfg);
};

Ext.extend(Garp.WelcomePanel, Ext.Container, {
	border: false,
	hideButtonArr: ['fileMenu', 'editMenu', 'viewMenu'],
	listeners: {
		'render': function(){
			Ext.each(this.hideButtonArr, function(item){
				Garp.toolbar[item].hide();
			});
			this.getEl().fadeIn({duration:2.2});
			if (Garp.viewport && Garp.viewport.formPanelCt) {
				Garp.viewport.formPanelCt.hide();
			}
		},
		'destroy': function(){
			Ext.each(this.hideButtonArr, function(item){
				Garp.toolbar[item].show();
			});
			if (Garp.viewport && Garp.viewport.formPanelCt) {
				Garp.viewport.formPanelCt.show();
			}
		}
	}
});