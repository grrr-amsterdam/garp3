Ext.ns('Garp.dataTypes');
(function() {
	if (!('Snippet' in Garp.dataTypes)) {
		return;
	}
	Garp.dataTypes.Snippet.on('init', function(){

		this.disableDelete = true;
		this.disableCreate = true;
		
		this.getField('text').height = 300;
		this.getField('html').height = 300;

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
				
				if (typeof rec.data.variables !== 'undefined' && form.findField('variables')) {
					form.findField('variables').setVisible(rec.data.variables && rec.data.variables.length);
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

			// Add readable variables to the variables field
			if (formPanel.variables_box && rec.data.variables) {
				var vars = rec.data.variables.split(',');
				vars = '<ul><li>%' + vars.join('%</li><li>%') + '%</li></ul>';
				formPanel.variables_box.update(vars);
			}
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
				ref: '../../../variables_box',
				allowBlank: true,
				fieldLabel: __('Variables'),
				name: 'variables',
				xtype: 'box',
				hidden: false,
				disabled: false,
				cls: 'garp-notification-boxcomponent',
				style: 'margin-top: 20px;',
				html: ''
			});
			this.addField({
				xtype: 'box',
				html: __('Variables will be replaced with dynamic content at the frontend.'),
				fieldLabel: ' '
			});
		}
		
		Ext.each(['has_text','has_name','has_image','has_html'], function(i){
			this.getField(i).hidden = true;
		}, this);

	});
})();
