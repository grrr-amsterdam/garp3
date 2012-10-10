Ext.ns('Garp.dataTypes');
Garp.dataTypes.Image.on('init', function(){
	
	this.iconCls = 'icon-img';
	
	// Thumbnail column:
	this.insertColumn(0, {
		header: '<span class="hidden">' + __('Image') + '</span>',
		dataIndex: 'id',
		width: 84,
		fixed: true,
		renderer: Garp.renderers.imageRelationRenderer,
		hidden: false
	});
	
	this.addListener('loaddata', function(rec, formPanel){
		function updateUI(){
			formPanel.preview.update(Garp.renderers.imagePreviewRenderer(rec.get('filename'), null, rec));
			formPanel.download.update({
				filename: rec.get('filename')
			});
		}
		if (formPanel.rendered) {
			updateUI();
		} else {
			formPanel.on('show', updateUI, null, {
				single: true
			});
		}
		// if we're in a relateCreateWindow, set it's height again, otherwise it might not fit.
		if (typeof formPanel.center == 'function' && rec.get('filename')) {
			formPanel.setHeight(440);
			formPanel.center();
		}
	}, true);
	
	
	// Remove these fields, cause we are about to change the order and appearance of them... 
	this.removeField('caption');
	this.removeField('filename');
	this.removeField('id');
	
	// ...include them again:
	this.insertField(0, {
		xtype: 'fieldset',
		style: 'margin: 0;padding:0;',
		items: [{
			name: 'id',
			hideFieldLabel: true,
			disabled: true,
			xtype: 'numberfield',
			hidden: true,
			ref: '../../../../_id'
		}, {
			name: 'filename',
			fieldLabel: __('Filename'),
			xtype: 'uploadfield',
			allowBlank: false,
			emptyText: __('Drag image here, or click browse button'),
			uploadURL: BASE + 'g/content/upload/type/image',
			ref: '../../../../filename',
			
			listeners: {
				'change': function(field, val){
					if (this.refOwner._id.getValue()) {
						var url = BASE + 'admin?' +
						Ext.urlEncode({
							model: Garp.currentModel,
							id: this.refOwner._id.getValue()
						});
						if (DEBUG) {
							url += '#DEBUG';
						}
						
						// because images won't reload if their ID is 
						// still the same, we need to reload the page 
						this.refOwner.formcontent.on('loaddata', function(){
							document.location.href = url;
						});
						this.refOwner.fireEvent('save-all');
					} else {
						field.originalValue = ''; // mark dirty
						this.refOwner.preview.update(Garp.renderers.uploadedImagePreviewRenderer(val));
						this.refOwner.download.update({
							filename: val
						});
						this.refOwner.fireEvent('save-all');
					}
					return true;
				}
			}
		}, {
			xtype: 'box',
			cls: 'garp-notification-boxcomponent',
			html: __('Only {1} and {2} files with a maximum of {3} MB are accepted', 'jpg, png', 'gif', '20'),
			fieldLabel: ' '
		}, {
			name: 'caption',
			xtype: 'textfield',
			fieldLabel: __('Caption')
		}, {
			xtype: 'box',
			ref: '../../../../preview',
			fieldLabel: __('Preview'),
			cls: 'preview',
			html: ''
		}, {
			xtype: 'box',
			hidden: false,
			// style: 'visibility:hidden',
			ref: '../../../../download',
			fieldLabel: ' ',
			hideFieldLabel: false,
			tpl: new Ext.XTemplate('<tpl if="filename">', '<a href="' + IMAGES_CDN + '{filename}" target="_blank">' + __('View original file') + '</a>', '</tpl>')
		}]
	});
	
	/*
	text: 'Image',
	
	displayFieldRenderer : function(rec){
		return rec.get('filename') || __('New Image');
	},
	
	// override: icon-image (GarpDataType default) is already in use by instances of Ext.ux.form.RichTextEditor.js
	iconCls:'icon-img',
	
	defaultData: {
		id: null,
		filename: null,
		caption: null,
		created: null,
		modified: null
	},
	
	sortInfo: {
		field: 'created',
		direction: 'DESC'
	},
	
	columnModel: [{
				header: '<span class="hidden">' + __('Image') + '</span>',
				dataIndex: 'id',
				width: 84,
				fixed: true,
				renderer: Garp.renderers.imageRelationRenderer,
				hidden: false
			}, {
				header: __('Filename'),
				dataIndex: 'filename',
				hidden: true
			}, {
				header: __('Caption'),
				dataIndex: 'caption'
			}, {
				header: __('Created'),
				dataIndex: 'created',
				hidden: true,
				renderer: Garp.renderers.dateTimeRenderer
			},{
				header: __('Modified'),
				dataIndex: 'modified',
				hidden: true,
				renderer: Garp.renderers.dateTimeRenderer
			}],
			
	formConfig: [{
		layout: 'form',
		
		defaults: {
			defaultType: 'textfield'
		},
		
		listeners: {
			'loaddata': function(rec, formPanel){
				function updateUI(){
					formPanel.preview.update(Garp.renderers.imagePreviewRenderer(rec.get('filename'),null,rec));
					formPanel.download.update({
						filename: rec.get('filename')
					});
				}
				if (formPanel.rendered) {
					updateUI();
				} else {
					formPanel.on('show', updateUI, null, {
						single: true
					});
				}
				// if we're in a relateCreateWindow, set it height again, otherwise it might not fit.
				if(typeof formPanel.center == 'function' && rec.get('filename')){
					formPanel.setHeight(440);
					formPanel.center();
				}
			}
		},
		
		items:[
		}]
	}]*/
});
