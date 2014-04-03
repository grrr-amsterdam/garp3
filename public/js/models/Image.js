Ext.ns('Garp.dataTypes');
Garp.dataTypes.Image = new Garp.DataType({
	
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
				header: __('ID'),
				dataIndex: 'id',
				hidden: true
			}, {
				header: __('Filename'),
				dataIndex: 'filename',
				renderer: Garp.renderers.imageRenderer
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
			},{
				header: 'Meta',
				dataIndex:'relationMetadata',
				hidden: true
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
			}
		},
		
		items:[{
			xtype: 'fieldset',
			items: [{
				name: 'id',
				hideFieldLabel: true,
				disabled: true,
				xtype: 'numberfield',
				hidden: true,
				ref: '../../../_id'
			}, {
				name: 'filename',
				fieldLabel: __('Filename'),
				xtype: 'uploadfield',
				allowBlank: false,
				emptyText: __('Please specify an image'),
				uploadURL: BASE + 'g/content/upload/image',
				ref: '../../../filename',
				
				listeners: {
					'change': function(field,val){
						if (this.refOwner._id.getValue()) {
							var url = BASE + 'admin?' +
							Ext.urlEncode({
								model: Garp.currentModel,
								id: this.refOwner._id.getValue()
							});
							if(DEBUG){
								url += '#DEBUG';
							}
							
							// because images won't reload if their ID is 
							// still the same, we need to reload the page 
							this.refOwner.formcontent.on('loaddata', function(){
								document.location.href = url;
							})
							this.refOwner.fireEvent('save-all');
						} else {
							field.originalValue = ''; // mark dirty
							this.refOwner.preview.update(Garp.renderers.uploadedImagePreviewRenderer(val));
							this.refOwner.download.update({
								filename: val
							});
						}
						return true;	
					}
				}
			}, {
				xtype: 'box',
				cls: 'garp-notification-boxcomponent',
				html: __('Only jpg, png and gif files with a maximum of 20 MB are accepted'),
				fieldLabel: ' '
			}, {
				name: 'caption',
				fieldLabel: __('Caption')
			}, {
				xtype: 'box',
				ref: '../../../preview',
				fieldLabel: __('Preview'),
				cls: 'preview',
				html: ''
			},{
				xtype:'box',
				hidden: false,
				// style: 'visibility:hidden',
				ref:'../../../download',
				fieldLabel: ' ',
				hideFieldLabel: false,
				tpl: new Ext.XTemplate('<tpl if="filename">','<a href="' + IMAGES_CDN + '{filename}" target="_blank">' + __('View original file') + '</a>','</tpl>')
			}]
		}]
	}]
});