Ext.ns('Garp');

Garp.ImportWindow = Ext.extend(Ext.Window, {
	width: 810,
	height: 360,
	modal: true,
	layout: 'card',
	activeItem: 0,
	preventBodyReset: false,
	title: __('Import'),
	iconCls: 'icon-import',
	maximizable: true,
	border: false,	
	defaults: {
		border: true,
		frame: false,
		style: 'background-color: #fff;',
		bodyStyle: 'background-color: #fff;padding-top: 10px; '
	},
	
	navHandler: function(dir){
		var page = this.getLayout().activeItem.id;
		page = parseInt(page.substr(5, page.length), 10);
		page += dir;
		
		var form = this.get('page-1').getForm();
		if (page <= 0) {
			page = 0;
			this.prevBtn.disable();
			this.nextBtn.setText(__('Next'));
			this.nextBtn.setDisabled(!form.findField('datafile').getValue());
			//form.findField('datafile').resumeEvents();
		} else if (page == 1) {// not realy a UI page..
			this.progress.reset(); 
			this.progress.wait({
				text: __('Processing')
			});
			Ext.select('.x-progress-bar').setHeight(18);
			this.prevBtn.disable();
			this.nextBtn.disable(); 
			form.submit();
			//form.findField('datafile').suspendEvents();
		} else if (page == 2){
			this.nextBtn.setText(__('Ok'));
			this.prevBtn.enable();
			this.nextBtn.enable();
		} else if (page == 3){
			this.proceedImport();
		} else{
			this.prevBtn.enable();
			this.nextBtn.setText(__('Next'));
		}
		this.getLayout().setActiveItem('page-' + page);
	},
	
	showMappingUI: function(data){
		var items = [];
		var options = [];
		Ext.each(Garp.dataTypes[Garp.currentModel].columnModel, function(c){
			options.push([c.dataIndex, c.header]);
		});
		options.push(['',__('  (Ignore)')]);
		
		var columnCount = data[0].length;
		var selectListener = function(field, value){
			var elms = Ext.select('.'+field.name, null, this.el.body);
			if(!value){
				elms.addClass('garp-import-hidden-col');
			} else {
				elms.removeClass('garp-import-hidden-col');
			}
		};
		
		for(var i=0; i < columnCount; i++){
			var col = [];
			col.push({
				name: 'col-' + i,
				xtype: 'combo',
				allowBlank: false,
				editable: false,
				triggerAction: 'all',
				typeAhead: false,
				mode: 'local',
				store: options,
				submitValue: false,
				value: i < columnCount ? options[i][0] : columnCount,
				width: 140,
				listeners: {
					'select': selectListener,
					scope: this
				}
			});
			for(var rows = 0; rows< data.length; rows++){
				col.push({
					xtype: 'box',
					html: (data[rows][i] || '' ) + '', // convert null to ''
					cls: 'row-' + rows + ' col-' + i,
					style: 'background-color: #fff; margin: 5px 10px 5px 2px;'
				});
				
			}
			items.push({
				items:col
			});
		}
		
		var width = (150 * columnCount);
		
		this.mappingColumns = new Ext.Panel({
			layout: 'column',
			columns: options.length,
			width: width + 10,
			items: items,
			defaults: {
				width: 150
			}
		});
		
		this.get('page-2').add({
			xtype: 'panel',
			style: 'margin: 10px;',
			frame: true,
			bodyStyle: 'padding:10px;',
			autoScroll: true,
			items: this.mappingColumns
		});
		
		this.get('page-2').add({
			xtype: 'fieldset',
			//style: 'background-color: #fff;',
			items: [{
				xtype: 'checkbox',
				fieldLabel: __('Ignore first row'),
				name: 'ignore-first-row',
				checked: false,
				submitValue: false,
				handler: function(cb, checked){
					var elms = Ext.select('.row-0', null, this.el.body);
					if(checked){
						elms.addClass('garp-import-hidden-row');
					} else {
						elms.removeClass('garp-import-hidden-row');
					}
				},
				scope: this
			},{
				xtype: 'checkbox',
				fieldLabel: __('Continue on error(s)'),
				name: 'ignoreErrors'
			}]
		});
		
		this.getLayout().setActiveItem('page-2');
		this.get('page-2').doLayout();
		this.navHandler(0);
		this.get('page-2').getForm().findField('ignore-first-row').setValue(true);
	},
	
	proceedImport: function(){
		var mapping = [];
		var combos = this.get('page-2').findByType('combo');
		Ext.each(combos, function(c){
			mapping.push(c.getValue());
		});
		var form = this.get('page-2').getForm();
		Ext.apply(form.baseParams,{
			datafile: this.get('page-1').getForm().findField('datafile').getValue(),
			mapping: Ext.encode(mapping),
			firstRow: this.get('page-2').getForm().findField('ignore-first-row').checked ? '1' : '0'
		});
		this.lm = new Ext.LoadMask(this.getEl());
		this.lm.show();
		form.submit();
	},
	
	initComponent: function(){
		this.progress = new Ext.ProgressBar({
			animate: true
		});
		this.items = [{
			id: 'page-0',
			xtype: 'form',
			timeout: 0,
			items: [{
				xtype: 'fieldset',
				labelWidth: 150,
				title: __('Specify file to import'),
				style: 'padding-top: 50px; ',
				items: [{
					xtype: 'uploadfield',
					uploadURL: BASE+'g/content/upload/type/document',
					supportedExtensions: ['xls', 'xlsx', 'xml'],
					allowBlank: false,
					name: 'filename',
					fieldLabel: __('Filename'),
					listeners: {
						'change' : function(f,v){
							this.get('page-1').getForm().findField('datafile').setValue(v);
							this.navHandler(1);
						},
						scope: this
					}
				},{
					xtype: 'displayfield',
					value: __('Excel filetypes are supported')
				}]
			}]
		},{
			id: 'page-1',
			timeout: 0,
			xtype: 'form',
			url: BASE + 'g/content/import/',
			baseParams:{
				'model': Garp.currentModel
			},
			listeners: {
				'actioncomplete': function(form, action){
					if (action.result && action.result.success) {
						this.showMappingUI(action.result.data);
					} 
				},
				'actionfailed': function(form, action){
						var msg = __('Something went wrong. Please try again.');
						if(action.result && action.result.message){
							msg += '<br>' + action.result.message;
						}
						Ext.Msg.alert(__('Error'), msg);
						this.close();
					
				},
				scope: this
			},
			items: [{
				xtype: 'fieldset',
				labelWidth: 150,
				title: __('Please wait'),
				style: 'padding-top: 50px; ',
				items: [this.progress, {
					xtype: 'textfield',
					hidden: true,
					fieldLabel: __('Filename'),
					ref: 'datafile',
					name: 'datafile'
				}]
			}]
		},{
			id: 'page-2',
			xtype: 'form',
			timeout: 1200,
			url: BASE + 'g/content/import/',
			baseParams:{
				'model': Garp.currentModel
			},
			listeners: {
				'actioncomplete': function(form, action){
					this.lm.hide();
					if (action.result && action.result.success) {
						this.close();
						Garp.gridPanel.getStore().reload();
					}
				},
				'actionfailed': function(form, action){
						this.lm.hide();
						var msg = __('Something went wrong. Please try again.');
						if(action.result.message){
							msg += '<br>' + action.result.message;
						}
						Ext.Msg.alert(__('Error'), msg);
				},
				scope: this
			},
			items: [{
				xtype: 'fieldset',
				labelWidth: 0,
				title: __('Fields'),
				items: []
			}]
		}];
		
		this.buttonAlign = 'left';
		this.buttons = [{
			text: __('Previous'),
			disabled: true,
			ref: '../prevBtn',
			handler: this.navHandler.createDelegate(this, [-2]) // !
		}, '->', {
			text: __('Cancel'),
			handler: this.close.createDelegate(this)
		}, {
			text: __('Next'),
			ref: '../nextBtn',
			disabled: true,
			handler: this.navHandler.createDelegate(this, [1])
		}];
		
		//this.on('show', this.navHandler.createDelegate(this, [-1]));
		Garp.ImportWindow.superclass.initComponent.call(this);
	}
});
