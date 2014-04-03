Ext.ns('Garp');

Garp.ImagePickerWindow = Ext.extend(Ext.Window, {
	
	imgGridQuery: null,
	captionValue: null,
	alignValue: '',
	hideWizardText: false,
	cropTemplateName: null,
	
	// debug only:
	id: 'IPW',
	//@TODO: remove
	
	model: 'Image',
	title: __('Image'),
	iconCls: 'icon-image',
	layout: 'card',
	activeItem: 0,
	width: 640,
	height: 500,
	border: false,
	modal: true,
	buttonAlign: 'left',
	resizable: false,
	defaults: {
		border: false
	},

	navHandler: function(dir){
		var page = this.getLayout().activeItem.id;
		page = parseInt(page.substr(5, page.length));
		page += dir;
		if(page <= 0){
			page = 0;
		}
		switch(page){
			case 1: 
				
				this.tplGrid.getStore().on({
					
					load: {
						single: true,
						scope: this,
						fn: function(){
							if (!this.cropTemplateName) {
								this.tplGrid.getSelectionModel().selectFirstRow();
							} else {
								var rec = this.tplGrid.getStore().getAt(this.tplGrid.getStore().find('name', this.cropTemplateName));
								this.tplGrid.getSelectionModel().selectRecords([rec]);
							}
							if (!this.captionValue) {
								this.caption.getForm().findField('caption').setValue(this.imgGrid.getSelectionModel().getSelected().get('caption'));
							}
						}
					}
				});
				this.tplGrid.getStore().load();
				this.prevBtn.enable();
				this.nextBtn.setText(__('Ok'));
				
			break;
			case 2:
				var img = this.imgGrid.getSelectionModel().getSelected();
				var tpl = this.tplGrid.getSelectionModel().getSelected();
				this.fireEvent('select', {
					image: img,
					template: tpl,
					src: IMAGES_CDN + 'scaled/' + tpl.get('name') + '/' +  img.get('id'),
					align: this.alignment.getForm().getValues().align,
					caption: this.caption.getForm().getValues().caption
				});
				
				this.close();
			break;
			case 0: 
			default:
				this.prevBtn.disable();
				this.nextBtn.setDisabled( this.imgGrid.getSelectionModel().getCount() < 1 );
				this.nextBtn.setText(__('Next'));
			break;
		}
		
		this.getLayout().setActiveItem('page-' + page);
	},

	/**
	 * @function getStoreCfg
	 * @return store Cfg object
	 */
	getStoreCfg: function(){
		return {
			autoLoad: false,
			autoSave: false,
			remoteSort: true,
			restful: true,
			autoDestroy: true,
			root: 'rows',
			idProperty: 'id',
			fields: Garp.getStoreFieldsFromColumnModel(Garp.dataTypes[this.model].columnModel),
			totalProperty: 'total',
			sortInfo: Garp.dataTypes[this.model].sortInfo || null,
			baseParams: {
				start: 0,
				limit: Garp.pageSize,
			},
			api: {
				create: Ext.emptyFn,
				read: Garp[this.model].fetch || Ext.emptyFn,
				update: Ext.emptyFn,
				destroy: Ext.emptyFn
			}
		};
	},
	
	/**
	 * @function getGridCfg
	 * @param hideHeader
	 * @return defaultGridObj
	 */
	getGridCfg: function(hideHeaders){
		return {
			border: true,
			region: 'center',
			hideHeaders: hideHeaders,
			enableDragDrop: true,
			ddGroup: 'dd',
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			cm: new Ext.grid.ColumnModel({
				defaults:{
					sortable: true
				},
				columns: (function(){
					var cols = [];
					var cmClone = Garp.dataTypes[this.model].columnModel;
					for (var c = 0, l = cmClone.length; c < l; c++) {
						var col = Ext.apply({},cmClone[c]);
						cols.push(col);
					}
					return cols;
				}).call(this)
			}),
			pageSize: Garp.pageSize,
			viewConfig: {
				scrollOffset: -1, // No reserved space for scrollbar. Share it with last column
				forceFit: true,
				autoFill: true
			}
		};
	},
	
	updatePreview: function(){
		var tpl = this.tplGrid.getSelectionModel().getSelected().get('name');
		var w = 170;
		var h = 170;
		var id = this.imgGrid.getSelectionModel().getSelected().get('id');
		this.previewpanel.update({
			IMAGES_CDN: IMAGES_CDN,
			BASE: BASE,
			tpl: tpl,
			id: id,
			width: w,
			height: h,
			'float': this.alignment.getForm().getValues().align
		//,
		//marginTop: Math.max((h - (rec.get('h') || h)) / 2, 3),
		//marginLeft: Math.max((w - (rec.get('w') || w)) / 2, 3)
		});
		
	},
	
	/**
	 * 
	 */
	clearSelection: function(){
		this.fireEvent('select', {
			selected: null
		});
		this.close();
	},
	
	/**
	 * 
	 */
	initComponent: function(){
		this.addEvents('select');
		
		this.imgStore = new Ext.data.DirectStore(Ext.apply({}, {
			writer: new Ext.data.JsonWriter({
				paramsAsHash: false,
				writeAllFields: true,
				encode: false
			}),
			api: {
				create: Ext.emptyFn,
				read: Garp[this.model].fetch,
				update: Ext.emptyFn,
				destroy: Ext.emptyFn
			}
		}, this.getStoreCfg()));
		
		this.imgGrid = new Ext.grid.GridPanel(Ext.apply({}, {
			region: 'center',
			title: __('Available'),
			itemId: 'imgPanel',
			margins: '15 15 15 15',
			store: this.imgStore,
			bbar: new Ext.PagingToolbar({
				pageSize: Garp.pageSize,
				store: this.imgStore,
				beforePageText: '',
				displayInfo: false
			}),
			tbar: new Ext.ux.Searchbar({
				xtype: 'searchbar',
				store: this.imgStore
			}),
			listeners: {
				'rowdblclick': this.navHandler.createDelegate(this, [1])
			}
		}, this.getGridCfg(true)));
		
		//
		this.tplGrid = new Ext.grid.GridPanel({
			itemId: 'tplPanel',
			margins: '15 15 0 15',
			title: __('Crop'),
			region: 'center',
			hideHeaders: true,
			store: new Ext.data.DirectStore({
				autoLoad: true,
				autoSave: false,
				autoDestroy: true,
				remoteSort: true,
				restful: true,
				root: 'rows',
				idProperty: 'id',
				fields: [{
					name: 'name'
				}, {
					name: 'w'
				}, {
					name: 'h'
				}],
				api: {
					create: Ext.emptyFn,
					read: Garp['CropTemplate'].fetch,
					update: Ext.emptyFn,
					destroy: Ext.emptyFn
				}
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					'rowselect': this.updatePreview,
					scope: this
				}
			}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true
				},
				columns: [{
					id: 'name',
					dataIndex: 'name',
					header: __('Name'),
					width: 200
				}, {
					id: 'w',
					dataIndex: 'w',
					header: __('Width')
				},{
					id: 'h',
					dataIndex: 'h',
					header: __('Height')
				}, {
					id: 'preview',
					dataIndex: 'name',
					header: __('Preview'),
					renderer: Garp.renderers.cropPreviewRenderer
				}]
			}),
			viewConfig: {
				scrollOffset: -1, // No reserved space for scrollbar. Share it with last column
				forceFit: true,
				autoFill: true
			},
			listeners: {
				'rowdblclick': this.navHandler.createDelegate(this, [1])
			}
		});

		// "validate" upon selection:		
		this.imgGrid.getSelectionModel().on('selectionchange', this.navHandler.createDelegate(this,[0]));
		this.tplGrid.getSelectionModel().on('selectionchange', function(){
			this.nextBtn.setDisabled(this.tplGrid.getSelectionModel().getCount() == 0 );
		}, this);
		
		var headerTpl = new Ext.Template('<h2>',__('Step'),' {step} ', __('of'), ' 2</h2><p>{description}</p>');
		
		this.items = [{
			id: 'page-0',
			layout: 'border',
			bodyBorder: true,
			border: true,
			split: false,
			items: [{
				region: 'north',
				ref: '../northpanel',
				border: false,
				html: this.hideWizardText ? '' : headerTpl.apply({
					description: __('Specify an image or add new one'),
					step: 1
				}),
				margins: '10 15 0 15',
				bodyStyle: 'background: #eee;',
				bodyBorder: false,
				bbar: new Ext.Toolbar({
					style: 'border:0; margin-top: 10px;',
					border: false,
					items: [{
						iconCls: 'icon-new',
						ref: 'newBtn',
						hidden: !Garp.dataTypes[this.model].quickCreatable,
						text: __('New ' + Garp.dataTypes[this.model].text),
						handler: function(){
							var win = new Garp.RelateCreateWindow({
								model: this.model,
								iconCls: this.iconCls,
								title: this.title,
								listeners: {
									scope: this,
									'aftersave': function(rcwin, rec){
										this.imgStore.insert(0, rec);
										this.imgGrid.getSelectionModel().selectRecords([rec], true);
									}
								}
							});
							
							win.show();
						},
						scope: this
					}]
				})
			}, this.imgGrid]
		},{
			id: 'page-1',
			layout: 'border',
			bodyBorder: true,
			border: true,
			split: false,
			items: [{
				region: 'north',
				bodyStyle: 'background: #eee',
				border: false,
				margins: '10 15 0 15',
				html: headerTpl.apply({
					description: __('Specify a crop template, set aligning and/or add a caption'),
					step: 2
				}),
			
			},this.tplGrid, {
				region: 'east',
				margins: '15 15 0 0',
//			
id: 'previewpanel',
//
				ref: '../previewpanel',
				width: 260,
				bodyStyle: 'background:#eee;',
				title: __('Preview'),
				//tpl: '<img src="{BASE}images/{id}/tpl/{tpl}" style="max-width: {width}px; max-height: {height}px; border: 1px #ddd solid; margin: {marginTop}px {marginLeft}px;" />'
				tpl: '<p style="font-size:9px;"><img src="{IMAGES_CDN}scaled/{tpl}/{id}" style="float: {float};max-width: {width}px; max-height: {height}px; border: 1px #ddd solid; margin: 3px;" />Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>'
			},{
				region: 'south',
				border: false,
				bodyBorder: false,
				height: 145,
				margins: '15 15 15 15',
				bodyStyle: 'background-color: #eee',
				layout: 'border',
				defaults: {
					
					xtype: 'form',
					layout: 'form',
					labelWidth: 180,
					labelSeparator: '',
					border: true,
					bodyStyle: 'padding: 5px 0 0 5px',
				},
				items: [{
					title: __('Caption'),
					region: 'center',
					ref: '../../caption',
					bodyStyle: 'margin:0; padding:0;',
					items: [{
						border: false,
						fieldLabel: __('Caption'),
						hideLabel: true,
						xtype: 'textarea',
						style: 'border: 0;',
						height: 118,
						anchor: '100%',
						name: 'caption',
						value: this.captionValue
					}]
				},{
					title: __('Alignment'),
					region: 'east',
					width: 260,
					margins: '0 0 0 15',
					bodyStyle: 'padding: 10px',
					ref: '../../alignment',
					defaults:{
						handler: this.updatePreview,
						scope: this
					},
					items: [{
						fieldLabel: __('No alignment'),
						xtype: 'radio',
						name: 'align',
						checked: this.alignValue == '',
						inputValue: ''
					}, {
						fieldLabel: __('Align left'),
						xtype: 'radio',
						name: 'align',
						checked: this.alignValue == 'left',
						inputValue: 'left'
					}, {
						fieldLabel: __('Align right'),
						xtype: 'radio',
						name: 'align',
						checked: this.alignValue == 'right',
						inputValue: 'right'
					}]
				}]
				/*
				,tbar: new Ext.Toolbar({
					style: 'border:0; padding: 0px 15px 0 15px;',
					border: false,
					items:[
						
					,{
						iconCls: 'icon-crop',
						text: __('Crop manually'),
						handler: function(){
							//@TODO : cleanup / refactor this, for events and maintainability these parts need to work together better: 
							
							var cropper = new Garp.ImageEditor({
									image: BASE + 'images/' + this.imgGrid.getSelectionModel().getSelected().get('filename')
								});
							var win = new Ext.Window({
								title: __('Image Cropper'),
								iconCls: 'icon-image',
								modal: true,
								maximizable: true,
								layout: 'fit',
								width: 800,
								height: 500,
								items: [cropper]
							});
							cropper.on('done', function(args){
								this.fireEvent('select', args[0], 300,300);
								this.close();
								win.close();
							}, this);
							win.show();
						},
						scope: this
					}
					
					]
				})*/
			}]
		}];
		
		this.buttons = [{
			text: __('Previous'),
			ref: '../prevBtn',
			handler: this.navHandler.createDelegate(this, [-1])
		}, '->',{
			text: __('Cancel'),
			handler: this.close.createDelegate(this)
		},{
			text: __('Clear selection'),
			ref: '../clearBtn',
			hidden: true,
			handler: this.clearSelection.createDelegate(this)
		},{
			text: __('Next'),
			ref: '../nextBtn',
			handler: this.navHandler.createDelegate(this, [1])
		}];
		
		if(this.imgGridQuery){
			this.imgGrid.getStore().setBaseParam('query', this.imgGridQuery);
			this.imgGrid.getStore().on({
				load: {
					single: true,
					scope: this,
					fn: function(){
						this.imgGrid.getSelectionModel().selectFirstRow();
						this.navHandler(1); // go to page-2
						//this.imgGrid.getStore().setBaseParam('query', null); // ...and then clear search results for when the user wants to go back to page-1
						//this.imgGrid.getStore().load(); 
					}
				}
			});
			var f = this.imgGrid.getTopToolbar().searchField;
			f.setValue(this.imgGridQuery.id);
			f.hasSearch = true;
			f.fireEvent('change');
			
		}
		
		Garp.ImagePickerWindow.superclass.initComponent.call(this);
		this.on('show', function(){
			this.navHandler(-1);
			this.imgStore.load();
			if(this.allowBlank){
				this.clearBtn.show();
			}
		},this);
	}
	
});