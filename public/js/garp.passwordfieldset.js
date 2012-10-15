Ext.ns('Garp');

/**
 * It supposes to find a password field named according to @cfg passwordFieldname in it's refOwner's scope.
 */

Garp.PasswordFieldset = Ext.extend(Ext.form.FieldSet, {
	
	callback: Ext.emptyFn,

	style:'margin:0;padding:0;',
	defaultType: 'textfield',
	defaults:{
		allowBlank: true
	},	
	
	collapseAndHide: function(){
		this.showpassword.hide();
		this.password.hide();
		this.plaintext.hide();
		this.password.setValue('');
		this.password.originalValue = '';
		this.plaintext.setValue('');
		this.plaintext.originalValue = '';
	},
	
	initComponent: function(ct){
	
		this.items = [{
			ref: 'setPasswordBtn',
			fieldLabel : ' &nbsp; ',
			xtype: 'button',
			text: __('Set password'),
			boxMaxWidth: 64,
			handler: function(){
				var r = this.refOwner;
				if (r.showpassword.isVisible()) {
					r.collapseAndHide();
				} else {
					r.showpassword.show();
					r.password.show();
					r.plaintext.hide();
					r.password.focus(20);
				}
			}
		}, {
			ref: 'password',
			fieldLabel : __('Password'),
			inputType: 'password',
			hidden: true,
			listeners: {
				'change': this.callback
			}
		}, {
			ref: 'plaintext',
			fieldLabel : __('Password'),
			inputType: 'text',
			hidden: true,
			listeners: {
				'change': this.callback
			}
		}, {
			ref: 'showpassword',
			fieldLabel: __('Show Password'),
			xtype: 'checkbox',
			allowBlank: true,
			hidden: true,
			handler: function(){
				var r = this.refOwner, c = this.checked;
				r.password.setVisible(!c);
				r.plaintext.setVisible(c);
				r[c ? 'plaintext' : 'password'].setValue(r[c ? 'password' : 'plaintext'].getValue());
			}
		}];
		
		var scope = this;
		this._interval = setInterval(function(){
			if (scope.password) {
				if (scope.password.isVisible() || scope.plaintext.isVisible()) {
					if (scope.password.isVisible() && scope.password.isDirty()) {
						scope.callback(scope.password, scope.password.getValue());
						scope.password.originalValue = scope.password.getValue();
					} else if (scope.plaintext.isDirty()) {
						scope.callback(scope.plaintext, scope.plaintext.getValue());
						scope.plaintext.originalValue = scope.plaintext.getValue();
					}
				}
			} else {
				clearInterval(this._interval);
			}
		}, 100);
		
		Garp.PasswordFieldset.superclass.initComponent.call(this, ct);
		
		
	}
	
	
	
	
	/**
	 * Private
	 */
	
	/*listeners: {
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
	}*/
	
});

Ext.reg('passwordfieldset', Garp.PasswordFieldset);