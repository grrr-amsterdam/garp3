Ext.ns('Ext.ux.form');

Ext.ux.form.UploadField = Ext.extend(Ext.ux.form.FileUploadField, {
	
	uploadURL: BASE + 'g/content/upload',
	loadMask: new Ext.LoadMask(Ext.getBody(), {msg: __('Uploading...')}),
	
	/**
	 * Override, because MySQL null values != ''
	 */
	getValue: function(){
		var val = Ext.ux.form.UploadCombo.superclass.getValue.call(this);
		if(val == ''){
			val = null;
		}
		return val;
	},
	
	setUploadState: function(){
		this.form.fileUpload = true;
		this.oName = this.name;
		this.name = 'file';
		this.form.url = this.uploadURL;
	},
	
	revertState: function(){
		this.form.fileUpload = false;
		this.name = this.oName;
		this.form.url = '';
	},
	
	performUpload: function(){
		this.setUploadState();
		this.loadMask.show();
		this.form.submit({
			scope: this,
			clientValidation: false,
			success:  function(form, action){
				if(!this.el.dom){
					return;
				}
				this.revertState();
				this.setValue(action.result.filename);
				this.loadMask.hide();
				this.fireEvent('change', this, action.result.filename);
			},
			failure: function(form, action){
				if(!this.el.dom){
					return;
				}
				this.revertState();
				this.loadMask.hide();
				var msg = '';
				if(action && action.result && action.result.messages && action.result.messages.length){
					var msg = action.result.messages.join('<br />');
				}
				Ext.Msg.alert(__('Error'), '<b>'+__('Error uploading file') + '</b>:<br />' + msg);
			}
		});
	},

	onDestroy: function(){
		this.loadMask.hide();
		Ext.ux.form.UploadField.superclass.onDestroy.call(this);
	},
	
	initComponent: function(){
		this.on('fileselected', this.performUpload);
		Ext.ux.form.UploadField.superclass.initComponent.call(this, arguments);
	}
});
Ext.reg('uploadfield', Ext.ux.form.UploadField);
