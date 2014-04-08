/**
 * @class UploadCombo
 * 
 * Displays an upload dialog when triggering the trigger button of the triggerfield
 * 
 * @extends Ext.form.TriggerField 
 * @author: Peter
 */

Ext.ns('Ext.ux.form');

Ext.ux.form.UploadCombo = Ext.extend(Ext.form.TriggerField, {
	
	title: __('Upload'),
	fieldLabel: __('File'),
	iconCls: 'icon-image',
	emptyText: __('Select file'),
	
	previewTpl: new Ext.Template('<img src="{0}uploads/images/{1}" title="{1}" style="max-width:400px;max-height:260px;" /><br><a href="{0}uploads/images/{1}" target="_blank">' + __('Download') + '&hellip;</a>'),
	uploadURL: BASE + 'g/content/upload',
	
	/**
	 * Displays the dialog
	 */
	showUploadDialog: function(){
		
		var previewBox = {
			ref: 'previewBox',
			hideMode: 'visibility',
			frame: true,
			hidden: true
		};
		
		if (this.getValue()) {
			Ext.apply(previewBox, {
				html: this.previewTpl.apply([BASE, this.getValue()]),
				style: 'overflow: auto;margin-bottom: 10px;text-align:center;',
				height: 290,
				hidden: false
			});
		}
		
		this.win = new Ext.Window({
			modal: true,
			title: this.title,
			iconCls: this.iconCls,
			width: 450,
			height: (this.getValue() ? 420 : 130),
			border: false,
			items: [{
				xtype: 'form',
				ref: 'formpanel',
				fileUpload: true,
				border: false,
				frame: true,
				bodyStyle: 'padding: 10px 10px 0 10px',
				items: [ previewBox, {
					anchor: '95%',
					value: this.value,
					height: 40,
					name: 'file',
					fieldLabel: this.fieldLabel,
					allowBlank: false,
					buttonText: __('Browse&hellip;'),
					hideFieldLabel: true,
					emptyText: this.emptyText,
					listeners: {
						'fileselected': this.performUpload.createDelegate(this)
					},
					xtype: 'fileuploadfield'
				}]
			}],
			buttons: [/*{
				text: __('Upload'),
				handler: this.performUpload.createDelegate(this)
			}, */
			{
				text: __('Cancel'),
				scope: this,
				handler: function(){
					this.win.close();
				}
			}]
		});
		this.win.show();
		this.setupFileDrop();
	},
	
	/**
	 * Uploads the dialog's form's file input
	 */
	performUpload: function(){
		var form = this.win.formpanel.getForm();
		if (form.isDirty()) {
			var mask = new Ext.LoadMask(Ext.getBody(), {
				msg: __('Uploading...')
			});
			mask.show();
			var scope = this;
			form.submit({
				clientValidation: false,
				url: this.uploadURL,
				success: this.uploadCallback.createSequence(function(){
					mask.hide();
				}).createDelegate(this),
				failure: function(form, action){
					mask.hide();
					var msg = '';
					if (action && action.result && action.result.messages && action.result.messages.length) {
						msg = action.result.messages.join('<br />');
					}
					Ext.Msg.alert(__('Error'), '<b>' + __('Error uploading file') + '</b>:<br />' + msg);
					
				}
			});
		} else {
			this.win.close();
		}
	},
	
	/**
	 * Callback
	 * @param {Object} form
	 * @param {Object} action
	 */
	uploadCallback: function(form, action){
		this.win.close();
		if (action.result.file) {
			this.setValue(action.result.file);
		}
		this.fireEvent('change', this, action.result.file, '');
	},
	
	
	/**
	 * Sets up FF file d'n drop functionality
	 */
	setupFileDrop: function(){
		
		var el = this.win.getEl();
		var scope = this;
		
		function cancel(e){
			e.stopPropagation();
			e.preventDefault();
			return false;
		}
		
		function dropHandler(e){
			cancel(e);
			e = e.browserEvent;
			
			function randNumber(digits){
				var out = '';
				for (var i = 0; i < digits; i++) {
					out += '' + Math.floor(Math.random() * 10);
				}
				return out;
			}
			
			var wait = new Ext.LoadMask(scope.win.getEl(), __('Uploading...'));
			
			if (e.dataTransfer && e.dataTransfer.files) {
				var file = e.dataTransfer.files[0];
				var reader = new FileReader();
				reader.onload = function(e) {
  					var bin = e.target.result;
					var xhr = new XMLHttpRequest();
					var header = '', footer = '';
					var lf = '\r\n';

					xhr.addEventListener('load', function(e){
						wait.hide();
						var result = Ext.decode(e.target.responseText);
						var action = {
							result: result
						};
						scope.uploadCallback.call(scope, null, action);
					}, false);

					var boundary = '---------------------------' + randNumber(13);

					xhr.open('POST', scope.uploadURL, true);
					xhr.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + boundary);
					xhr.setRequestHeader('Content-Length', file.fileSize);
					
					header += '--' + boundary + lf;
					header += 'Content-Disposition: form-data; name="file"; filename="' + file.fileName + '"' + lf + lf;
					footer = lf + '--' + boundary + '--' + lf; 
					
					xhr.sendAsBinary(header + '' + bin + '' +  footer);
					wait.show();
				};
				reader.readAsBinaryString(file);	
			}
			return false;
		}
		
		Ext.EventManager.on(el, 'dragenter', function(e){
			el.highlight();
			cancel(e);
		});
		Ext.EventManager.on(el, 'dragexit', cancel);
		Ext.EventManager.on(el, 'dragover', cancel);
		Ext.EventManager.on(el, 'drop', dropHandler);
	},
	
	/**
	 * Override
	 */
	onTriggerClick: function(){
		this.showUploadDialog();
	},
	
	/**
	 * Override, because MySQL null values != ''
	 */
	getValue: function(){
		var val = Ext.ux.form.UploadCombo.superclass.getValue.call(this);
		if(!val){
			val = null;
		}
		return val;
	},
	
	/**
	 * Init
	 */
	initComponent: function(){
		Ext.ux.form.UploadCombo.superclass.initComponent.call(this, arguments);
	}
});

Ext.reg('uploadcombo', Ext.ux.form.UploadCombo);
