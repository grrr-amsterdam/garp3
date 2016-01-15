/** EXTENDED MODEL **/
(function() {
	if (!('User' in Garp.dataTypes)) {
		return;
	}

	/**
	 * Add some fields that trick Chrome into auto-filling them.
	 * This prevents form fuckup on the _actual_ fields below.
	 */
	function addHoneypotFieldsForChromeAutofill(target) {
		target.formConfig[0].items[0].items.unshift({
			hidden: true,
			disabled: false,
			allowBlank: true,
			inputType: 'password',
			name: '__fake_password__',
			fieldLabel: 'fake password'
		});

		target.formConfig[0].items[0].items.unshift({
			hidden: true,
			disabled: false,
			allowBlank: true,
			inputType: 'email',
			name: '__fake_login__',
			fieldLabel: 'fake login'
		});

	}

	Garp.dataTypes.User.on('init', function(){
		['AuthLocal', 'AuthPasswordless', 'AuthFacebook', 'AuthTwitter', 'AuthLinkedin']
			.map(function(rl) {
				this.removeRelationPanel(rl);
			}.bind(this));

		Ext.apply(this.getColumn('fullname'), {
			virtualSortField: 'first_name'
		});

		addHoneypotFieldsForChromeAutofill(this);

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
				if (val || email._keepRequired) {
					email.allowBlank = false;
					email.label.addClass('required-field');
					passwordField.setValue(val);
				} else {
					email.allowBlank = true;
					email.label.removeClass('required-field');
					passwordField.setValue('');
				}
				field.refOwner.refOwner.getForm().items.each(function(){
					if (this.validate) {
						this.validate();
					}
				});
			}
		});

		this.addListener('loaddata', function(rec, formPanel) {

			formPanel.changePassword.collapseAndHide();

			if (rec.get('image_id')) {
				formPanel.ImagePreview_image_id.setText(Garp.renderers.imageRelationRenderer(rec.get('image_id'), null, rec) || __('Add image'));
			}

			// Now remove some roles from the dropdown, based on ACL:
			var roleField = formPanel.getForm().findField('role');
			if (roleField) {
				var ownRole = Garp.localUser.role || 'User';
				Ext.each(Garp.ACL[ownRole].children, function(disabledRole) {
					var idx = roleField.store.find('field1', disabledRole);
					roleField.store.removeAt(idx);
					// roleField.store.remove(disabledRole);
				});
			}
			var email = formPanel.getForm().findField('email');
			if (email && email.allowBlank === false) {
				email._keepRequired = true;
			} else if (email) {
				email._keepRequired = false;
			}

			// Cleanup honeypot fields
			setTimeout(function() {
				//formPanel.getForm().remove(formPanel.getForm().findField('__fake_login__'));
				//formPanel.getForm().remove(formPanel.getForm().findField('__fake_password__'));
			}, 1000);
		});

	});
})();
