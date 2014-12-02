Ext.ns('Garp');

/**
 * Renders as a button which opens a dialog for a user to enter a new password.
 * It suposes to find a password field named according to @cfg passwordFieldname in it's refOwner's scope.
 */

Garp.PasswordButton = Ext.extend(Ext.Button, {
	/**
	 * @cfg passwordFieldname
	 */
	passwordFieldname: 'password',
	
	/**
	 * @cfg callback
	 */
	callback: Ext.emptyFn,
	
	/**
	 * Private
	 */
	listeners: {
		click: function(){
			
			var id = this.refOwner.getForm().findField('id').getValue();
			var scope = this;
			var win;
			
			function btnHandler(ref){
				if (ref.text != __('Cancel')) {
					var val = win.password.getValue();
					scope.refOwner.getForm().findField(scope.passwordFieldname).setValue(val ? val : null);
					scope.callback(scope, val);		
				}
				win.close();
			}
			win = new Ext.Window({
				title: __('Password'),
				modal: true,
				iconCls: 'icon-passwordwindow',
				width: 380,
				bodyCssClass: 'garp-formpanel',
				height: 160,
				listeners: {
					'show': function(){
						win.keymap = new Ext.KeyMap(win.getEl(), [{
							key: Ext.EventObject.ENTER,
							fn: btnHandler
						}, {
							key: Ext.EventObject.ESC,
							fn: win.close
						}]);
						
						// we need to delay this, because monitorValid is a taskRunner
						win.passwordform.startMonitoring();
						setTimeout(function(){
							win.password.clearInvalid();
							win.password.focus();
						}, 200);
					}
				},
				items: [{
					xtype: 'form',
					ref: 'passwordform',
					border: false,
					monitorValid: false,
					labelWidth: 140,
					defaults: {
						xtype: 'textfield',
						labelSeparator: '',
						width: 200,
						allowBlank: true,
						fieldLabel: __('New Password')
					},
					items: [{
						ref: '../password',
						inputType: 'password'
					}, {
						hidden: true,
						ref: '../plaintext',
						inputType: 'text'
					}, {
						fieldLabel: __('Show Password'),
						xtype: 'checkbox',
						allowBlank: true,
						ref: '../showpassword',
						handler: function(){
							var r = this.refOwner, c = this.checked;
							r.password.setVisible(!c);
							r.plaintext.setVisible(c);
							r[c ? 
								'plaintext' : 
								'password'].setValue(r[c ? 
									'password' : 
									'plaintext'].getValue());
						}
					}]
				}],
				buttons: [{
					text: __('Cancel'),
					handler: btnHandler
				}, {
					text: __('Save'),
					handler: btnHandler
				}]
			});
			win.show();
		}
	}
});

Ext.reg('passwordbutton', Garp.PasswordButton);