/**
 * @class Garp.FormPanel
 * @extends Ext.FormPanel
 * @author Peter
 */

Garp.FormPanel = Ext.extend(Ext.FormPanel, {

	/**
	 * @cfg defaults:
	 */
	previousValidFlag: true,
	layout: 'border',
	monitorValid: true,
	trackResetOnLoad: false,
	clientValidation: true,
	renderHidden: true,
	
	/**
	 * @function newItem
	 * Makes sure the panel is shown, highlighted and focus the first field.
	 * Also unsets previousValid to assist in syncing Form <-> Grid
	 */
	newItem: function(){
		this.previousValidFlag = null;
		this.show();
		this.getForm().clearInvalid();
		(function(){
			this.highlightPanel();
			this.focusFirstField();
		}).defer(100,this);
		this.getTopToolbar().previewButton.setDisabled(true);
	},
	
	/**
	 * @returns {String} All errors from all fields in the form 
	 */
	getErrors: function(){
		var str = '';
		this.getForm().items.each(function(i){
			if(i.getActiveError()){
				str += i.getActiveError() + ': ' + i.fieldLabel + '<br>';
			}
		});
		return str;
	},
	
	/**
	 * @function updateTitle
	 */
	updateTitle: function(){
		if (Garp.dataTypes[Garp.currentModel].displayFieldRenderer) {
			var panel = this.items.itemAt(0).items.itemAt(0);
			if (panel.xtype !== 'relationpanel') { 
				panel.setTitle(Garp.dataTypes[Garp.currentModel].displayFieldRenderer(this.rec));
			}
		}
	},

	/**
	 * @function loadData
	 * loads Data in the form and makes sure the panel is visible
	 * 
	 * @param {Object} sm selectionModel
	 */
	loadData: function(sm){
		this.show();
		var form = this.getForm();
		this.rec = sm.getSelected();
		if(!this.rec) return;
		
		form.loadRecord(this.rec);
		if (!this.ownerCt) 
			return;
		this.updateTitle();
		
		this.fireEvent('defocus');
		
		function relayEvent(){
			if(this.formcontent.rendered){
				this.formcontent.fireEvent('loaddata', this.rec, this);
			}
			if(this.metaPanel.rendered){
				this.metaPanel.fireEvent('loaddata', this.rec, this);
			}
		}
		
		relayEvent.call(this);
		this.formcontent.on('show', relayEvent, this, {
			single: true
		});
		this.metaPanel.on('show', relayEvent, this, {
			single: true
		});
		
		form.unDirty();
		this.getTopToolbar().previewButton.setDisabled(this.rec.dirty);
	},
	
	/**
	 * @function setModeIndicator
	 * displays a message when in batch mode (true) or single mode (false)
	 * 
	 * @param {bool} mode
	 */
	// NOT USED AT THE MOMENT!
	setModeIndicator: function(mode){
		var tb = this.getBottomToolbar(); 
		if(mode){
			tb.items.items[0].setText(__('Batch mode'));
			tb.show();
		} else {
			tb.hide();
		}
	},
	
	/**
	 * @function updateUI
	 * @param {object} selectionModel
	 * Gets called whenever a selection change occurs. Reflect the UI:
	 * 
	 * 0 items selected: hide  
	 * 1 item : show
	 * 2 items: show & display mode Indicator
	 */
	updateUI: function(sm){
		var count = sm.getCount();
		count > 1 ? count = 2 : true;
		if(this.updateUI.prevCount && this.updateUI.prevCount == count) return

		switch (count) {
			case 0: // no items selected
				//this.hide();
				this.ownerCt.getLayout().setActiveItem(0);
				break;
				
			case 1: // single mode
				//this.show();
				this.ownerCt.getLayout().setActiveItem(1);
				//this.setModeIndicator(false);
				break;
				
			default: // batch mode
				//this.show();
				this.ownerCt.getLayout().setActiveItem(0);
				//this.setModeIndicator(true);
				break;
		}
		this.updateUI.prevCount = count;
		return true;
	},
	
	/**
	 * @function show
	 * shows the panel and makes sure it's container is shown as well
	 */
	show: function(){
	//	if (this.ownerCt.collapsed || this.ownerCt.hidden) {
			this.ownerCt.expand();
			this.ownerCt.show();
	//	}
		//@TODO: Find out whether this is necessary, or can be left out:
		//this.syncSize(); -- seems unecessary...
		
		//this.ownerCt.syncSize();
		//this.ownerCt.ownerCt.doLayout();
		
		Garp.FormPanel.superclass.show.call(this);
		//this.ownerCt.getLayout().setActiveItem(1);
	},

	/**
	 * @function hide
	 * hide the panel and makes sure it's container is hidden as well
	 */	
	/*
	hide: function(){
		if (!this.ownerCt.collapsed || !this.ownerCt.hidden) {
			//this.ownerCt.collapse();
			//this.ownerCt.hide();
			this.ownerCt.ownerCt.doLayout();
			this.ownerCt.getLayout().setActiveItem(0);
		}
		Garp.FormPanel.superclass.hide.call(this);
	},
	*/
	
	
	/**
	 * @function highlightPanel
	 * Visibly highlights the panel for use for example with Garp.eventManager.new 
	 */
	highlightPanel: function(){
		if (this.el && this.el.dom) {
			this.el.fadeIn();
		}
	},
	
	/**
	 * @function focusFirstField
	 * Focuses the first editable & visible field
	 */
	focusFirstField: function(){
		this.getForm().items.each(function(item){
			if(item.xtype == 'compositefield'){
				item.items.each(function(item){
					if (!item.hidden && !item.disabled && item.focus && Ext.isFunction(item.focus)) {
						item.focus(100);
						return false;
					} else {
						return true;
					}
				});
			}
			if (!item.hidden && !item.disabled && item.focus && Ext.isFunction(item.focus)) {
				item.focus(100);
				return false;
			} else {
				return true;
			}
		});
	},
	
	/**
	 * @function afterRender
	 * For now only sets up keyboard handling. 
	 */
	afterRender: function(){
		var keyMap = new Ext.KeyMap(this.getEl(), [{
			key: Ext.EventObject.ESC,
			scope: this,
			handler: function(){
				this.fireEvent('defocus');
			}
		}]);
		keyMap.stopEvent = true;
		
		Garp.FormPanel.superclass.afterRender.call(this);
	},
	
	setLocked: function(lock){
		this.locked = lock;
		this.fireEvent(lock ? 'lock' : 'unlock', this);
		if(lock){
			this.getEl().addClass('locked');	
		} else {
			this.getEl().removeClass('locked');
		}
	},
	
	lock: function(){
		this.setLocked(true);
	},
	
	unlock: function(){
		this.setLocked(false);
	},
	
	isLocked: function(){
		return this.locked;
	},
	
	
	/**
	 * init
	 */
	initComponent: function(){
		this.id = Ext.id();
		this.addEvents('lock','unlock');
		this.updateUI.prevCount = 0;
		var items = Ext.apply({},Garp.dataTypes[Garp.currentModel]).formConfig;
		Ext.apply(items[0],{ref:'../formcontent'});
		
		this.tbar = new Ext.Toolbar({
			style: 'border: 0;',
			items: [{
				text: __('Save'),
				iconCls: 'icon-save',
				ref: 'saveButton',
				handler: function(){
					this.fireEvent('save-all');
				},
				scope: this
			},' ',{
				text: __('Open in new window'),
				iconCls: 'icon-open-new-window',
				handler: function(){
					this.fireEvent('open-new-window');
				},
				scope: this
			}, ' ', {
				text: __('Preview'),
				iconCls: 'icon-preview',
				ref: 'previewButton',
				disabled: true,
				scope: this,
				handler: function(){
					this.fireEvent('preview');
				},
				listeners: {
					'disable': function(){
						this.setTooltip(__('To preview, save this item first'));
					},
					'enable': function(){
						this.setTooltip(__('Preview this item'));
					}
				}
			}]
		});
		
		this.metaPanel = new Garp.MetaPanel();
		
		this.items= [{
			xtype: 'tabpanel',
			plain: false, //true,
			region: 'center',
			activeTab: 0,
			autoDestroy: true,
			enableTabScroll: true,
			//resizeTabs: true,
			minTabWidth: 90,
			border: false,
			defaults: {
				autoScroll: true,
				autoWidth: true,
				bodyCssClass: 'garp-formpanel'	// visual styling
			},
			items: [items]
		},
		this.metaPanel
		//Garp.dataTypes[Garp.currentModel].metaPanelFactory()
		/*{
			region:'east',
			border: false,
			bodyCssClass:'garp-metapanel',
			margins: '0',
			ref: 'metaPanel',
			tpl: Garp.dataTypes[Garp.currentModel].metaPanelTpl,
			width: 190
		}*/
		];

		

		// NOT USED RIGHT NOW (see garp.formpanel.js setModeIndicator) 
		/*
		this.bbar = new Ext.Toolbar({
			hidden: true,
			items: [{
				text: '' // placeholder for 'Batch' message & Future expansion
			}]			 // @TODO: try to find more usefull ways to use this ToolBar 
		});
		*/

		this.on({
			scope: this,
			'new': {
				buffer: 150,
				fn: this.newItem
			},
			'rowselect': {
				buffer: 100,
				fn: this.loadData
			},
			'selectionchange': {
				buffer: 200,
				fn: this.updateUI
			}
		});

		Garp.FormPanel.superclass.initComponent.call(this, arguments);
		
	}
});