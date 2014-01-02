Ext.ns('Garp');

Garp.ExportWindow = Ext.extend(Ext.Window, {
	width: 520,
	height: 360,
	modal: true,
	layout: 'card',
	activeItem: 0,
	preventBodyReset: true,
	title: __('Export'),
	iconCls: 'icon-export',
	border: false,
	defaults: {
		border: false,
		frame: true
	},
	
	proceedExport: function(){
		var selection = '';
		var sel = this.get('page-0').getForm().getValues();
		switch(sel.selection){
			case 'currentItem':
				var selection = 'id&id=' + (typeof Garp.gridPanel.getSelectionModel().getSelected() != 'undefined' ? Garp.gridPanel.getSelectionModel().getSelected().id : 0);
			break;
			case 'currentPage':
				var tb = Garp.gridPanel.getBottomToolbar()
				var selection = 'page&page=' + Math.ceil((tb.cursor + tb.pageSize) / tb.pageSize);
			break;
			case 'specific':
				var selection = 'page&from=' + sel.from + '&to=' + sel.to;
			break;
			case 'all': default:
				var selection = 'all';
			break;
		};
		var filter = Ext.util.JSON.encode(Garp.gridPanel.store.baseParams.query);
		var form1 = this.get('page-1').getForm(); 
		var fieldConfig = form1.getValues();
		
		var exportType = this.get('page-2').getForm().getFieldValues().exporttype;
		
		var fields = {};
			
		if (!fieldConfig['__all']) {
			for (var f in fieldConfig) {
				fields[form1.findField(f).boxLabel] = f; 
			}
		} else {
			var all = this.getCheckboxesFromModel();
			for (var i in all) {
				if (typeof all[i] != 'function') {
					fields[all[i].boxLabel] = all[i].name;
				}
			}			
		}
		fields = Ext.encode(fields);
		var sortInfo = Garp.gridPanel.getStore().sortInfo;
		var pageSize = Garp.gridPanel.getBottomToolbar().pageSize;
		var url = BASE + 'g/content/export/model/' + Garp.currentModel + 
			'?selection=' + selection + 
			'&exporttype=' + exportType + 
			'&fields=' + fields + 
			'&filter=' + filter + 
			'&pageSize=' + pageSize + 
			'&sortField=' + sortInfo.field + 
			'&sortDir=' + sortInfo.direction
		;
		//console.warn(url);
		//return;
		
		this.close();
		window.location = url;
		
	},
	
	navHandler: function(dir){
		var page = this.getLayout().activeItem.id;
		page = parseInt(page.substr(5, page.length));
		page += dir;
		if (page <= 0) {
			page = 0;
			this.prevBtn.disable();
			this.nextBtn.setText(__('Next'));
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
			checkboxes.push({
				boxLabel: __(field.header),
				name: __(field.dataIndex),
				checked: !field.hidden
			});
		});
		return checkboxes;
	},
	
	initComponent: function(){
		this.items = [{
			id: 'page-0',
			xtype: 'form',
			items: [{
				xtype: 'fieldset',
				labelWidth: 150,
				title: __('Specify selection to export (1/3)'),
				bodyStyle: 'paddingTop: 10px;',
				defaults: {
					scope: this,
					handler: function(cb, checked){
						var form = this.get('page-0').getForm();
						var disableSpecific = true;
						if (form.getValues().selection == 'specific') {
							disableSpecific = false;
						}
						form.findField('from').setDisabled(disableSpecific);
						form.findField('to').setDisabled(disableSpecific);
					}
				},
				items: [{
					fieldLabel: __('Currently selected item'),
					xtype: 'radio',
					name: 'selection',
					inputValue: 'currentItem'
				}, {
					fieldLabel: __('Current page'),
					xtype: 'radio',
					checked: true,
					name: 'selection',
					inputValue: 'currentPage'
				}, {
					fieldLabel: __('All pages'),
					xtype: 'radio',
					name: 'selection',
					inputValue: 'all'
				}, {
					xtype: 'compositefield',
					fieldLabel: __('Specific pages'),
					items: [{
						xtype: 'radio',
						name: 'selection',
						inputValue: 'specific',
						width: 30
					}, {
						xtype: 'displayfield',
						value: __('Page')
					}, {
						xtype: 'numberfield',
						name: 'from',
						value: 1,
						disabled: true,
						flex: 1
					}, {
						xtype: 'displayfield',
						value: __('to')
					}, {
						xtype: 'numberfield',
						name: 'to',
						value: 1,
						disabled: true,
						flex: 1
					}]
				
				}]
			}]
		}, {
			id: 'page-1',
			xtype: 'form',
			autoScroll: true,
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
					store: [['txt','Text'],['html','HTML'],['csv','CSV'],['pdf','PDF'],['excel','Excel']]
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