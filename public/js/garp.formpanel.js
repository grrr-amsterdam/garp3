/**
 * @class Garp.FormPanel
 * @extends Ext.FormPanel
 * @author Peter
 */

Garp.FormPanel = Ext.extend(Ext.FormPanel, {

	/**
	 * @cfg defaults:
	 */
	layout: 'fit',
	hideMode: 'offsets',
	monitorValid: true,
	trackResetOnLoad: false,
	clientValidation: true,
	
	/**
	 * private
	 */
	state: null,
	
	/**
	 * Sets the UI for all dirty / undirty / phantom combinations
	 * It enables / disabled buttons and such. It also fires 'dirty' & 'undirty' events
	 * It keeps track of the previous state for performance reasons
	 */
	updateUI: function(){
		if(!this.rec || !this.formcontent.rendered || this.hidden){
			return;
		}
		
		var  
		PHANTOM_VALID = 1,
		PHANTOM_INVALID = 2,
		EXISTING_NON_DIRTY_VALID = 4,
		EXISTING_NON_DIRTY_INVALID = 8,
		EXISTING_DIRTY_VALID = 16,
		EXISTING_DIRTY_INVALID = 32;
		
		var valid = this.getForm().isValid();
		var dirty = this.getForm().isDirty();
		
		var prevState = this.state;
		if(this.rec.phantom){
			if(valid){
				this.state = PHANTOM_VALID;
			} else {
				this.state = PHANTOM_INVALID;
			}
		} else {
			if (dirty) {
				if (valid) {
					this.state = EXISTING_DIRTY_VALID;
				} else {
					this.state = EXISTING_DIRTY_INVALID;
				}
			} else {
				if (valid) {
					this.state = EXISTING_NON_DIRTY_VALID;
				} else {
					this.state = EXISTING_NON_DIRTY_INVALID;
				}
			}
		}
		
		if(this.state != prevState){
			var tb = this.formcontent.getTopToolbar();
			
			switch(this.state){
				
				case PHANTOM_VALID:
					this.fireEvent('dirty');
					this.disableTabs();
					this.metaPanel.enable();
					tb.saveButton.enable();
					tb.saveAsDraftButton.enable();
					tb.cancelButton.enable();
					tb.previewButton.disable();
				break;
				
				case PHANTOM_INVALID:
					this.fireEvent('dirty');
					this.disableTabs();
					this.metaPanel.disable();
					tb.saveButton.disable();
					tb.saveAsDraftButton.disable();						
					tb.cancelButton.enable();
					tb.previewButton.disable();
				break;
				
				case EXISTING_NON_DIRTY_VALID:
					this.fireEvent('undirty');
					this.enableTabs();
					this.metaPanel.enable();
					tb.saveButton.disable();	
					tb.saveAsDraftButton.disable();					
					tb.cancelButton.disable();
					tb.previewButton.enable();
				break;
				
				case EXISTING_NON_DIRTY_INVALID:
				//.. SHOULD NOT OCCUR! (possible if model file is not valid)
					//this.fireEvent('dirty');
					//this.disableTabs();
					//tb.saveButton.disable();						
					//tb.cancelButton.enable();
					//tb.previewButton.enable();
					
					this.fireEvent('undirty');
					this.enableTabs();
					this.metaPanel.enable();
					tb.saveButton.disable();
					tb.saveAsDraftButton.disable();						
					tb.cancelButton.disable();
					tb.previewButton.disable();
					
				break;
				
				case EXISTING_DIRTY_VALID:
					this.fireEvent('dirty');
					this.disableTabs();
					this.metaPanel.enable();
					tb.saveButton.enable();	
					tb.saveAsDraftButton.enable();					
					tb.cancelButton.enable();
					tb.previewButton.disable();
				break;
				
				//case EXISTING_DIRTY_INVALID:
				default:
					this.fireEvent('dirty');
					this.disableTabs();
					this.metaPanel.disable();
					tb.saveButton.disable();	
					tb.saveAsDraftButton.disable();					
					tb.cancelButton.enable();
					tb.previewButton.disable();
				break;
			}
		}
	},
	
	/**
	 * Disables the other tabpanels (relatepanels)
	 */
	disableTabs: function(){
		//console.log('disableTabs');
		this.get(0).items.each(function(i){
			if (i != this.formcontent) {
				i.disable();
			}
		}, this);
	},
	
	/**
	 * Enables the other tabpanels (relatepanels) (duh)
	 */
	enableTabs: function(){
		//console.log('enableTabs');
		this.get(0).items.each(function(i){
			if (i != this.formcontent) {
				i.enable();
			}
		}, this);
	},
	
	/**
	 * Retrieves relationTabPanel based on modelName 
	 * @param {Object} modelName
	 */
	getTab: function(modelName){
		return this.get(0).items.find(function(i){
			return (i.model == modelName);
		});
	},
	
	
	/**
	 * @function newItem
	 * Makes sure the panel is shown and focuses the first field.
	 */
	newItem: function(){
		this.form.reset();
		(function(){
			this.stopMonitoring();
			this.focusFirstField();
			this.getForm().items.each(function(i){
				if (typeof i.blur == 'function') {
					i.on('blur', this.startMonitoring.createDelegate(this), this, {
						single: true
					});
					i.on('keyup', this.startMonitoring.createDelegate(this), this, {
						single: true
					});
				}
			}, this);
			this.getForm().clearInvalid();
			this.updateUI();
			this.rec = Garp.gridPanel.getSelectionModel().getSelected();
			this.formcontent.fireEvent('loaddata', this.rec, this);
		}).defer(100,this);
		this.getForm().clearInvalid();
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
		var form = this.getForm();
		this.stopMonitoring();
		if(form.isDirty() || sm.getCount() != 1){
			return;
		}
		this.rec = sm.getSelected();
		if (!this.rec) {
			return;
		}
		
		this.state = null;
		form.loadRecord(this.rec);
		
		if (!this.ownerCt) {
			return;
		}
		this.updateTitle();
		
		this.fireEvent('defocus');
		
		function relayEvent(){
			if(this.formcontent.rendered){
				this.formcontent.fireEvent('loaddata', this.rec, this);
			}
			if(this.metaPanel.rendered){
				this.metaPanel.fireEvent('loaddata', this.rec, this);
			}
			this.state = null;
			this.startMonitoring();
			this.getForm().clearInvalid();
		}
		
		relayEvent.call(this);
		this.formcontent.on('activate', relayEvent, this, {
			single: true
		});
		this.metaPanel.on('activate', relayEvent, this, {
			single: true
		});
			var draftable = Garp.dataTypes[Garp.currentModel].getColumn('online_status') && Garp.dataTypes[Garp.currentModel].getColumn('published') ? true : false;
			this.formcontent.getTopToolbar().saveAsDraftButton.setVisible(draftable);
		
		form.unDirty();
	},
	
	/**
	 * @function focusFirstField
	 * Focuses the first editable & visible field
	 */
	focusFirstField: function(){
		var fp = this;
		this.getForm().items.each(function(item){
			
			// See Garp changelist 3.4 'compositefield' not supported anymore
			
			/*if(item.xtype == 'compositefield'){
				item.items.each(function(item){
					if (!item.hidden && !item.disabled && item.focus && Ext.isFunction(item.focus)) {
						item.focus(100);
						return false;
					} else {
						return true;
					}
				});
			}*/ 
			
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
	 * Sets up keyboard handlin & events for the tabs 
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
		this.get(0).items.each(function(i){
			if (i != this.formcontent) {
				this.relayEvents(i, ['dirty', 'undirty']);
			}
		}, this);
		Garp.FormPanel.superclass.afterRender.call(this);
	},
	
	/*
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
	*/
	
	/**
	 * init
	 */
	initComponent: function(){
		
		this.id = Ext.id();
		this.addEvents('save-all','cancel','preview','open-new-window','lock','unlock','dirty','undirty','delete');
		
		this.on({
			scope: this,
			'new': {
				buffer: 30,
				fn: this.newItem
			},
			'save-all': {
				fn: function(){
					var tb = this.formcontent.getTopToolbar();
					tb.saveButton.disable();
					tb.saveAsDraftButton.disable();
					tb.cancelButton.disable();
				}
			},
			'after-save': {
				fn: function(sm){
					this.state = null;
					this.form.unDirty();
					this.form.reset();
					this.loadData(sm);
					this.updateUI();
				}
			},
			'rowselect': {
				buffer: 100,
				fn: this.loadData
			},
			'clientvalidation': {
				fn: function(fp, valid){
					this.updateUI(valid);
				}
			}
		});
		
		var items = [];
		Ext.each(Garp.dataTypes[Garp.currentModel].formConfig, function(o){
			items.push(Ext.apply({}, o));
		});
		
		Ext.apply(items[0],{
			ref: '../formcontent',
			title: '&nbsp;', // misformed tab otherwise
			layout: 'border'
		});
		// fieldset properties override:
		Ext.apply(items[0].items[0], {
			region: 'center',
			autoScroll: true,
			margins: '0 0 0 10'
		});
		
		items[0].tbar = {
			cls: 'garp-formpanel-toolbar',
			items: [{
				text: __('Save'),
				iconCls: 'icon-save',
				ref: 'saveButton',
				disabled: true,
				handler: function(){
					this.dirtyState = null;
					this.fireEvent('save-all');
				},
				scope: this
			},{
				text: __('Save as draft'),
				hidden: !this.draftable,
				iconCls: 'icon-save-draft',
				ref: 'saveAsDraftButton',
				disabled: true,
				handler: function(){
					this.dirtyState = null;
					this.rec.set('online_status',0);
					this.fireEvent('save-all');
				},
				scope: this
			},{
				text: __('Cancel'),
				iconCls: 'icon-cancel',
				ref: 'cancelButton',
				disabled: true,
				handler: function(){
					function revertPhantom(){
						this.getForm().reset();
						this.updateUI();
						this.rec = null;
						this.fireEvent('delete');
					}
					if(this.getForm().isDirty()){
						Ext.Msg.confirm(__('Garp'), __('Are you sure you want to revert your changes?'), function(btn){
							if(btn == 'yes'){
								if (this.rec.phantom) {
									revertPhantom.call(this);
								} else {
									this.getForm().reset();
									this.updateUI();
									this.fireEvent('cancel', this);
								}
							}
						}, this);
						return;
					}
					if(!this.rec || this.rec.phantom){
						revertPhantom.call(this);
						return;
					}
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
				hidden: Garp.currentModel && !Garp.dataTypes[Garp.currentModel].previewLink,
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
		};
		
		items[0].items.push(this.metaPanel = new Garp.MetaPanel());

		this.items = {
			xtype:'tabpanel',
			deferredRender: false,
			activeTab: 0,
			resizeTabs: true,
			tabWidth: 150,
			tabMargin: 15,
			enableTabScroll: true,
			border: false,
			defaults: {
				border: false,
				deferredRender: false,
				bodyCssClass: 'garp-formpanel' // visual styling
			},
			items: items
		};
		
		this.relayEvents(this.metaPanel, ['save-all', 'dirty', 'undirty']);
		
		Garp.FormPanel.superclass.initComponent.call(this, arguments);
		this.stopMonitoring();
	}
	
});

Ext.reg('garpformpanel', Garp.FormPanel);