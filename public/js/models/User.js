/** EXTENDED MODEL **/
Garp.dataTypes.User.on('init', function(){

	Ext.apply(this.getColumn('fullname'), {
		virtualSortField: 'first_name'
	});

	this.addColumn({
		dataIndex: 'password',
		header: 'Password',
		renderer: Ext.emptyFn,
		hidden: true,
		virtual: true
	});
	
	this.addField({
		hidden: true,
		ref: '../../../passwordField',
		name: 'password'
	});
	
	this.addField({
		allowBlank: true,
		xtype: 'passwordfieldset',
		ref: '../../../changePassword',
		callback: function(field, val){
			var passwordField = field.refOwner.refOwner.passwordField;
			var email = field.refOwner.refOwner.getForm().findField('email');
			var keepAllowBlank = (email.allowBlank === false);
			if (val) {
				email.allowBlank = false;
				email.label.addClass('required-field');
				passwordField.setValue(val);
			} else {
				if (!keepAllowBlank) {
					email.allowBlank = true;
					email.label.removeClass('required-field');
				}
				passwordField.setValue('');
			}
			field.refOwner.refOwner.getForm().items.each(function(){
				if (this.validate) {
					this.validate();
				}
			});
		}
	});
	
	this.addListener('loaddata', function(rec, formPanel){
		
		formPanel.changePassword.collapseAndHide();
		
		if (rec.get('image_id')) {
			formPanel.ImagePreview_image_id.setText(Garp.renderers.imageRelationRenderer(rec.get('image_id'), null, rec) || __('Add image'));
		}
		
		// Now remove some roles from the dropdown, based on ACL:
		var roleField = formPanel.getForm().findField('role');
		if (roleField) {
			var ownRole = Garp.localUser.role || 'User';
			Ext.each(Garp.ACL[ownRole].children, function(disabledRole){
				var idx = roleField.store.find('field1', disabledRole);
				roleField.store.removeAt(idx);
				// roleField.store.remove(disabledRole);
			});
		}
	});
	
});
