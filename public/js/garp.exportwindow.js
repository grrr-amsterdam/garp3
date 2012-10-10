Ext.ns('Garp');

Garp.ExportWindow = Ext.extend(Ext.Window, {
	width: 580,
	height: 360,
	modal: true,
	layout: 'card',
	activeItem: 0,
	preventBodyReset: true,
	title: __('Export'),
	iconCls: 'icon-export',
	border: false,
	defaults: {
		border: true,
		cls: 'garp-formpanel' // new style doesn't need frame, old style doesn't need this class
		//frame: true
	},
	
	proceedExport: function(){
		var selection;
		var sel = this.get('page-0').getForm().getValues();
		var ids = [];
		
		if (typeof Garp.gridPanel.getSelectionModel().getSelected() != 'undefined') {
			Ext.each(Garp.gridPanel.getSelectionModel().getSelections(), function(item){
				ids.push(item.id);
			});
		}
		
		var exportType = this.get('page-2').getForm().getFieldValues().exporttype;
		var filter = Ext.util.JSON.encode(Garp.gridPanel.store.baseParams.query);
		var sortInfo = Garp.gridPanel.getStore().sortInfo;
		var pageSize = Garp.gridPanel.getBottomToolbar().pageSize;
		var cursor   = Garp.gridPanel.getBottomToolbar().cursor;
		var form1 = this.get('page-1').getForm();
		var form0 = this.get('page-0').getForm();
		var fieldConfig = form1.getValues();
		var fields = [];

		if (!fieldConfig.__all) {
			for (var f in fieldConfig) {
				fields.push(f);
			}
		} else {
			var all = this.getCheckboxesFromModel();
			for (var i in all) {
				if (typeof all[i] != 'function') {
					fields.push(all[i].name);
				}
			}
		}
		
		var parameters = {};
		switch (sel.selection) {
			case 'currentItem':
				parameters = {
					selection: 'id',
					exportType: exportType,
					sortDir: sortInfo.direction,
					sortField: sortInfo.field
				};
				break;
			case 'currentPage':
				parameters = {
					selection: 'page',
					page: (Math.ceil((cursor + pageSize) / pageSize)),
					exportType: exportType,
					pageSize: pageSize,
					sortDir: sortInfo.direction,
					sortField: sortInfo.field,
					filter: filter
				};
				break;
			case 'specific':
				parameters = {
					selection: 'page',
					from: sel.from,
					to: sel.to,
					exportType: exportType,
					pageSize: pageSize,
					sortDir: sortInfo.direction,
					sortField: sortInfo.field,
					filter: filter
				};
				break;
			case 'relation':
				parameters = {
					selection: 'all',
					filter: '{"' +  Garp.currentModel + '.id":' + ids[0] + '}',
					exportType: exportType
				};
				break;
			default: //case 'all':
				parameters = {
					selection: 'all',
					exportType: exportType,
					pageSize: pageSize,
					sortDir: sortInfo.direction,
					sortField: sortInfo.field,
					filter: filter
				};
				break;
		}
		
		var url;
		if (sel.selection == 'relation') {
			url = Ext.urlEncode(parameters, BASE + 'g/content/export/model/' + form0.findField('model').getValue() + '?exporttype=' + exportType);
		} else {
			url = Ext.urlEncode(parameters, BASE + 'g/content/export/model/' + Garp.currentModel + '?exporttype=' + exportType);
		}
		
		if(sel.selection == 'currentItem'){
			url += '&id=[' + ids.join(',') + ']';
		}
		if (sel.selection == 'currentItem' || sel.selection == 'currentPage' || sel.selection == 'specific' || sel.selection == 'all') {
			url += '&filter=' + filter;
			url += '&fields=' + encodeURIComponent(fields);
		}
		
		this.close();
		window.location = url;
		
	},
	
	navHandler: function(dir){
		var page = this.getLayout().activeItem.id;
		page = parseInt(page.substr(5, page.length), 10);
		this._prevPage = page;
		page += dir;
		var selectionForm = this.get('page-0').getForm(); 
		if (page <= 0) {
			page = 0;
			this.prevBtn.disable();
			this.nextBtn.setText(__('Next'));
			selectionForm.findField('selection').handler.call(this);
		} else if (page == 2) {
			this.prevBtn.enable();
			this.nextBtn.setText(__('Ok'));
		} else if (page == 3){
			this.proceedExport();
		} else{
			this.prevBtn.enable();
			this.nextBtn.setText(__('Next'));
		}
		this.getLayout().setActiveItem('page-' + page);
	},
	
	getCheckboxesFromModel: function(){
		var checkboxes = [], cm = Garp.gridPanel.getColumnModel().columns;
		Ext.each(cm, function(field){
			if(field.virtual){
				return;
			}
			checkboxes.push({
				boxLabel: Ext.util.Format.stripTags(__(field.header)), // some headers have <span class="hidden"> around them. Remove that!
				name: field.dataIndex,
				checked: !field.hidden
			});
		});
		return checkboxes;
	},
	
	initComponent: function(){
		
		var selectionDefaults = {
			scope: this,
			allowBlank: true,
			handler: function(cb, checked){
				var form = this.get('page-0').getForm();
				var disableSpecific = true;
				var disableModel = true;
				if (form.getValues().selection == 'specific') {
					disableSpecific = false;
				}
				if (form.getValues().selection == 'relation') {
					disableModel = false;
				}
				form.findField('model').setDisabled(disableModel);
				form.findField('from').setDisabled(disableSpecific);
				form.findField('to').setDisabled(disableSpecific);
			}
		};
		this.items = [{
			id: 'page-0',
			xtype: 'form',
			items: [{
				xtype: 'fieldset',
				labelWidth: 200,
				title: __('Specify selection to export (1/3)'),
				defaults: selectionDefaults,
				items: [{
					fieldLabel: __('Currently selected item(s)'),
					xtype: 'radio',
					name: 'selection',
					checked: Garp.gridPanel.getSelectionModel().getCount() > 1,
					disabled: !Garp.gridPanel.getSelectionModel().getSelected(),
					inputValue: 'currentItem'
				}, {
					fieldLabel: __('Current page'),
					xtype: 'radio',
					name: 'selection',
					checked: Garp.gridPanel.getSelectionModel().getCount() === 0,
					inputValue: 'currentPage'
				}, /*{
					fieldLabel: __('All pages'),
					
					hidden: true,
					hideFieldLabel: true,
					
					xtype: 'radio',
					name: 'selection',
					inputValue: 'all'
				},*/ {
					xtype: 'compositefield',
					fieldLabel: __('Specific pages'),
					defaults: selectionDefaults,
					items: [{
						xtype: 'radio',
						name: 'selection',
						checked: Garp.gridPanel.getSelectionModel().getCount() === 1 && !Garp.formPanel.items.get(0).getLayout().activeItem.model,
						inputValue: 'specific',
						width: 30
					}, {
						xtype: 'displayfield',
						value: __('Page')
					}, {
						xtype: 'numberfield',
						name: 'from',
						value: 1,
						flex: 1
					}, {
						xtype: 'displayfield',
						value: __('to')
					}, {
						xtype: 'numberfield',
						name: 'to',
						value: Math.ceil(Garp.gridPanel.getStore().getTotalCount() / Garp.gridPanel.getBottomToolbar().pageSize), // == total pages ;)
						flex: 1
					}]
				},{
					xtype:'compositefield',
					fieldLabel: __('Related to current item'),
					disabled: Garp.gridPanel.getSelectionModel().getCount() !== 1,
					hidden: !Garp.dataTypes[Garp.currentModel].getRelations().length,
					defaults: selectionDefaults,
					items: [{
						xtype: 'radio',
						name: 'selection',
						inputValue: 'relation',
						checked: Garp.formPanel.items.get(0).getLayout().activeItem.model ? true : false,
						width: 30
					},{
						xtype:'displayfield',
						value: __('Kind')
					},{
						flex: 1,
						xtype: 'combo',
						editable: false,
						name: 'model',
						value: Garp.formPanel.items.get(0).getLayout().activeItem.model || Garp.dataTypes[Garp.currentModel].getRelations()[0] || null,
						store: (function(){
							var out = [];
							Ext.each(Garp.dataTypes[Garp.currentModel].getRelations(), function(model){
								if (Garp.dataTypes[model]) {
									out.push([model, Garp.dataTypes[model].text]);
								}
							});
							return out;
						})()
					}] 
				}]
			}]
		}, {
			id: 'page-1',
			xtype: 'form',
			autoScroll: true,
			listeners:{
				// skip this page, if at previous one 'relation' was chosen: 
				'show': function(){
					var ct = this.ownerCt;
					if(ct._prevPage === 0 && ct.get('page-0').getForm().getValues().selection == 'relation'){
						ct.nextBtn.handler.call(ct);
					}
				}
			},
			items: [{
				xtype: 'fieldset',
				labelWidth: 80,
				title: __('Select fields (2/3)'),
				items: [{
					ref: '../all',
					xtype:'checkbox',
					name: '__all',
					fieldLabel: __('All fields'),
					checked: true,
					handler: function(cb,checked){
						if(checked){
							this.refOwner.specific.disable();
						} else {
							this.refOwner.specific.enable();
						}
					}
				},{
					'xtype': 'box',
					'cls': 'separator'
				},{
					disabled: true,
					xtype: 'checkboxgroup',
					ref: '../specific',
					columns: 3,
					hideLabel: true,
					defaults: {
						xtype: 'checkbox'
					},
					items: this.getCheckboxesFromModel()
				}]
			}]
		}, {
			id: 'page-2',
			xtype: 'form',
			items: [{
				xtype: 'fieldset',
				title: __('Export type (3/3)'),
				items:[{
					xtype: 'combo',
					name: 'exporttype',
					fieldLabel: __('Format'),
					allowBlank: false,
					editable: false,
					triggerAction: 'all',
					typeAhead: false,
					mode: 'local',
					value: 'txt',
					store: [['txt','Text'],['csv','CSV'],['excel','Excel']]
				}]				
			}]
		}];
		
		this.buttonAlign = 'left';
		this.buttons = [{
			text: __('Previous'),
			ref: '../prevBtn',
			handler: this.navHandler.createDelegate(this, [-1])
		}, '->',{
			text: __('Cancel'),
			handler: this.close.createDelegate(this)
		},{
			text: __('Next'),
			ref: '../nextBtn',
			handler: this.navHandler.createDelegate(this, [1])
		}];
		
		Garp.ExportWindow.superclass.initComponent.call(this);
		this.on('show', this.navHandler.createDelegate(this, [-1]));
	}
});
