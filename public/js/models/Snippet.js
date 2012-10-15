Ext.ns('Garp.dataTypes');
Garp.dataTypes.Snippet.on('init', function(){

	this.disableDelete = true;
	this.disableCreate = true;
	
	this.addListener('loaddata', function(rec, formPanel){
		var form = formPanel.getForm();
		var toggleFields = ['name', 'html', 'text'];
		
		// Show or hide fields based on their 'has_*' counterparts 
		function toggleMode(){
			Ext.each(toggleFields, function(fieldName){
				var el = form.findField(fieldName).getEl().up('div.x-form-item');
				el.setVisibilityMode(Ext.Element.DISPLAY).setVisible(rec.data['has_' + fieldName] == '1');
			});
			// because imagePreview_image_id is not a formField but a button, we cannot find it with form.findField():
			formPanel.ImagePreview_image_id.setVisible(rec.data.has_image == 1);
		}
		
		// Be sure we are rendered; otherwise there simply won't be any elements to show/hide
		if (formPanel.rendered) {
			toggleMode();
		} else {
			formPanel.on('show', toggleMode, null, {
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
	Ext.each(['has_text','has_name','has_image','has_html'], function(i){
		this.getField(i).hidden = true;
	}, this);

});