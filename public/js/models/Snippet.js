Ext.ns('Garp.dataTypes');
Garp.dataTypes.Snippet = new Garp.DataType({

	text: 'Snippet',
	
	description: 'Small pieces of text, images or other media used on the website.',
	
	disableDelete: true,
	disableCreate: true,
	
	displayFieldRenderer: function(rec){
		return rec.get('identifier') ? rec.get('identifier') : rec.phantom ? __('New ' + this.text) : __('Unnamed ' + this.text);
	},
	
	defaultData: {
		id: null,
		identifier: '',
		uri: '',
		name: '',
		html: '',
		text: '',
		image_id: null,
		has_name: '0',
		has_html: '0',
		has_image: '0',
		has_text: '0',
		created: null,
		modified: null
	},
	
	sortInfo: {
		field: 'uri',
		direction: 'ASC'
	},
	
	columnModel: [{
		header: __('ID'),
		dataIndex: 'id',
		hidden: true,
	}, {
		header: __('Identifier'),
		dataIndex: 'identifier'
	}, {
		header: __('Uri'),
		dataIndex: 'uri'
	}, {
		dataIndex: 'image_id',
		renderer: Garp.renderers.imageRelationRenderer,
		width: 74,
		fixed: true,
		header: __('Image')
	}, {
		header: __('Created'),
		dataIndex: 'created',
		renderer: Garp.renderers.dateTimeRenderer,
		sortable: false,
		hidden: true
	}, {
		header: __('Modified'),
		dataIndex: 'modified',
		renderer: Garp.renderers.dateTimeRenderer,
		sortable: false,
		hidden: true
	}],
	
	formConfig: [{
		layout: 'form',
		defaults: {
			defaultType: 'textfield'
		},
		listeners: {
			// custom loaddata event handler:
			loaddata: function(rec, formPanel){
				var form = formPanel.getForm();
				var toggleFields = ['name', 'html', 'text'];
				
				// Show or hide fields based on their 'has_*' counterparts 
				function toggleMode(){
					Ext.each(toggleFields, function(fieldName){
						var el = form.findField(fieldName).getEl().up('div.x-form-item');
						el.setVisibilityMode(Ext.Element.DISPLAY).setVisible(rec.data['has_' + fieldName] == '1');
					});
					// because imagePreview_image_id is not a formField but a button, we cannot find it with form.findField():
					formPanel.ImagePreview_image_id.setVisible( rec.data['has_image'] == 1 );
					
					// image_id is not valid if not set. If we have a non-image snippet, we need to set make sure it validates:
					if(rec.data['has_image'] != 1 ){
						form.findField('image_id').setValue(0);
					}
				}
				
				// Be sure we are rendered; otherwise there simply won't be any elements to show/hide
				if (formPanel.rendered) {
					toggleMode();
				} else {
					formPanel.on('show', toggleMode, null, {
						single: true
					});
				}
				
				//	fancy image button
				formPanel.ImagePreview_image_id.setText(Garp.renderers.imageRelationRenderer(rec.get('image_id'), null, rec) || __('Add image'));
			},
			
			
		},
		items: [{
			xtype: 'fieldset',
			items: [{
				name: 'id',
				fieldLabel: __('ID'),
				disabled: true,
				hidden:true,
				xtype: 'textfield'
			}, {
				name: 'identifier',
				fieldLabel: __('Identifier'),
				disabled: true
			}, {
				name: 'uri',
				fieldLabel: __('Uri'),
				disabled: true
			}, {
				name: 'name',
				fieldLabel: __('Name'),
				xtype: 'textarea',
				height: 40
			}, {
				name: 'html',
				fieldLabel: __('Content'),
				xtype: 'richtexteditor',
				enableLists: true,
				enableMedia: false,
				enableHeading: false,
				enableSourceEdit: false,
				enableAlignments: false,
				enableColors: false,
				enableFont: false,
				enableFontSize: false,
				enableUnderline: false,
				enableBlockQuote: false
			}, {
				name: 'text',
				fieldLabel: __('Content'),
				xtype: 'textarea'
			}, {
				fieldLabel: __('Image'),
				allowBlank: false,
				xtype: 'button',
				ref: '../../../ImagePreview_image_id',
				tooltip: __('Click to change'),
				boxMaxWidth: 64,
				listeners: {
					'click': function(){
						this.refOwner.imageId.triggerFn();
					}
				}
			}, {
				name: 'image_id',
				xtype: 'relationfield',
				allowBlank: false,
				autoLoad: false,
				hidden: true,
				displayField: 'filename',
				ref: '../../../imageId',
				model: 'Image',
				allowCreate: true,
				listeners: {
					select: function(s){
						this.refOwner.ImagePreview_image_id.setText(s.selected ? Garp.renderers.imageRelationRenderer(s.selected.get('id'), null, s.selected) : __('Add image'));
					}
				}
			}, {
				name: 'has_name',
				hidden: true,
				hideLabel: true,
				fieldLabel: __('Has name'),
				xtype: 'checkbox'
			}, {
				name: 'has_html',
				hidden: true,
				hideLabel: true,
				fieldLabel: __('Has html'),
				xtype: 'checkbox'
			}, {
				name: 'has_image',
				hidden: true,
				hideLabel: true,
				fieldLabel: __('Has image'),
				xtype: 'checkbox'
			}, {
				name: 'has_text',
				hidden: true,
				hideLabel: true,
				fieldLabel: __('Has text'),
				xtype: 'checkbox'
			}]
		}]
	}]
});