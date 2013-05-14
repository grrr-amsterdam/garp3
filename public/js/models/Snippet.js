Ext.ns('Garp.dataTypes');
Garp.dataTypes.Snippet.on('init', function(){

	this.disableDelete = true;
	this.disableCreate = true;
	
	
	// [TEMPORARILY]: SPAWNER HAS NO SUPPORT FOR i18n YET
	/*
	this.displayFieldRenderer = function(rec){
		return rec.get('name') && rec.get('name').nl ? rec.get('name').nl : rec.get('identifier');
	};
 
	this.removeField('name');
	this.removeField('html');
	this.removeField('text');
	this.addField({
		name: 'name',
		xtype: 'i18nsource'
	});
	this.addField({
		name: 'html',
		xtype: 'i18nsource'
	});
	this.addField({
		name: 'text',
		xtype: 'i18nsource'
	});
	
	this.addField({
		xtype: 'i18nfieldset',
		title: __('Dutch'),
		collapsed: false,
		items: [{
			name: '_name_nl',
			fieldLabel: __('Name'),
			disabled: false,
			hidden: false,
			maxLength: 124,
			allowBlank: false,
			xtype: 'textfield'
		},{
			name: '_html_nl',
            fieldLabel: __('Html'),
            disabled: false,
            hidden: false,
            enableMedia: false,
            enableHeading: false,
            enableSourceEdit: false,
            enableEmbed: false,
            enableAlignments: false,
            enableColors: false,
            enableFont: false,
            enableFontSize: false,
            enableUnderline: false,
            enableBlockQuote: false,
            enableDefinitionList: false,
            height: 200,
            allowBlank: true,
            xtype: 'richtexteditor'
		}, {
			name: '_text_nl',
            fieldLabel: __('Text'),
            disabled: false,
            hidden: false,
            allowBlank: true,
            xtype: 'textarea'
		}]
	});
	this.addField({
		xtype: 'i18nfieldset',
		title: __('English'),
		items: [{
			name: '_name_en',
			fieldLabel: __('Name'),
			disabled: false,
			hidden: false,
			maxLength: 124,
			xtype: 'textfield'
		},{
			name: '_html_en',
            fieldLabel: __('Html'),
            disabled: false,
            hidden: false,
            enableMedia: false,
            enableHeading: false,
            enableSourceEdit: false,
            enableEmbed: false,
            enableAlignments: false,
            enableColors: false,
            enableFont: false,
            enableFontSize: false,
            enableUnderline: false,
            enableBlockQuote: false,
            enableDefinitionList: false,
            height: 200,
            allowBlank: true,
            xtype: 'richtexteditor'
		}, {
			name: '_text_en',
            fieldLabel: __('Text'),
            disabled: false,
            hidden: false,
            allowBlank: true,
            xtype: 'textarea'
		}]
	});
	*/
	// [/TEMPORARILY]
	
	
	this.addListener('loaddata', function(rec, formPanel){
		var form = formPanel.getForm();
		var toggleFields = ['name', 'html', 'text'];
		
		// Show or hide fields based on their 'has_*' counterparts 
		function updateUI(){
			Ext.each(toggleFields, function(fieldName){
				//var el = form.findField(fieldName).getEl().up('div.x-form-item');
				//el.setVisibilityMode(Ext.Element.DISPLAY).setVisible(rec.data['has_' + fieldName] == '1');
				form.findField(fieldName).setVisible(rec.data['has_' + fieldName] == '1');
			});
			// because imagePreview_image_id is not a formField but a button, we cannot find it with form.findField():
			formPanel.ImagePreview_image_id.setVisible(rec.data.has_image == 1);
			
			if (typeof rec.data.variables !== 'undefined') {
				form.findField('variables').setVisible(rec.data.variables.length);
			}
		}
		
		// Be sure we are rendered; otherwise there simply won't be any elements to show/hide
		if (formPanel.rendered) {
			updateUI();
		} else {
			formPanel.on('show', updateUI, null, {
				single: true
			});
		}
		formPanel.ImagePreview_image_id.setText(Garp.renderers.imageRelationRenderer(rec.get('image_id'), null, rec) || __('Add image'));
	});
	
	// No header for thumbnail column:
	this.getColumn('image_id').header = '';
	
	// Override some field properties as to hide and disable and such:
	Ext.each(['identifier','uri'], function(i){
		var f = this.getField(i);
		Ext.apply(f, {
			disabled: true,
			xtype: 'textfield',
			allowBlank: true
		});
	}, this);
	
	// "Variables" is kind of an odd platypus, we need to change the xtype and more; but only if it exists. We also move it to the bottom:
	if (this.getField('variables')) {
		this.removeField('variables');
		this.addField({
			allowBlank: true,
			fieldLabel: __('Variables'),
			name: 'variables',
			xtype: 'box',
			hidden: false,
			disabled: false,
			cls: 'garp-notification-boxcomponent',
			style: 'margin-top: 20px;',
			xtype: 'displayfield'
		});
	}
	
	Ext.each(['has_text','has_name','has_image','has_html'], function(i){
		this.getField(i).hidden = true;
	}, this);

});