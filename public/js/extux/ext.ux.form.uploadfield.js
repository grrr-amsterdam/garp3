Ext.ns('Ext.ux.form');


	Ext.ux.form.UploadField = Ext.extend(Ext.ux.form.FileUploadField, {
		/**
		 * Max surface area (example size 3000 x 2000)
		 */
		maxSurface: 6000000,
	
		/**
		 * @cfg uploadURL
		 */
		uploadURL: BASE + 'g/content/upload',
		
		/**
		 * @cfg supportedExtensions
		 */
		supportedExtensions: ['gif', 'jpg', 'jpeg', 'png'],
		
		/**
		 * Override, because MySQL null values != ''
		 */
		getValue: function(){
			var val = Ext.ux.form.UploadCombo.superclass.getValue.call(this);
			if (!val) {
				val = null;
			}
			return val;
		},
		
		/**
		 * Simple name based check
		 * @param {Object} fileName
		 */
		validateExtension: function(fileName){
			var extension = fileName.split('.');
			if (!extension) {
				return false;
			}
			extension = extension[extension.length - 1];
			var name = extension[0];
			if (!name.length) {
				return false; // also dont support files with an extension but no name 
			}
			return this.supportedExtensions.indexOf(extension.toLowerCase()) > -1;
		},

		/**
		 * Validate resolution
		 */
		validateResolution: function(file, callback) {
			// What to do for browsers with no FileReader support, such as IE9?
			// For now, let's just admit defeat and allow the upload.
			if (typeof FileReader !== 'function') {
				callback(true);
				return;
			}
			var fr = new FileReader();
			var scope = this;
			fr.onload = function() {   // onload fires after reading is complete
				var img = new Image();
				img.onload = function() {
					var surface = img.width * img.height;
					var success = surface <= scope.maxSurface;
					callback(success);
				};
				img.src = fr.result;
			};
			fr.readAsDataURL(file);    // begin reading
		},

		/**
		 * Drop Handler
		 * @param {Object} e
		 */
		handleFileDrop: function(e){
			this.wrap.removeClass('x-focus');
			if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length == 1) {
				this.performUpload(e.dataTransfer.files[0]);
			}
			return false;
		},
		
		/**
		 * Button Handler
		 */
		handleFileSelect: function(){
			this.performUpload(this.fileInput);
		},

		/**
		 * See if upload is image
		 */
		isImage: function(file) {
			var extension = file.name.split('.').pop();
			if (!extension) {
				return false;
			}

			var img_extensions = ['jpg', 'jpeg', 'png', 'gif'];
			for (var i = 0, ii = img_extensions.length; i < ii; ++i) {
				if (img_extensions[i] === extension) {
					return true;
				}
			}
			return false;
		},
		
		/**
		 * Check extension and go!
		 * @param {Object} fileInput field
		 */
		performUpload: function(fileInput){
			var scope = this;
			
			if (Ext.isIE) {
				
				var lm = new Ext.LoadMask(Ext.getBody(),{
					msg: __('Loading')
				});
				lm.show();
				var dh = Ext.DomHelper;
				var iframe = Ext.get(dh.insertHtml('beforeEnd', Ext.getBody().dom, '<iframe src="javscript:false;" name="uploadFrame" style="display:none;"></iframe>'));
				var form = Ext.get(dh.insertHtml('beforeEnd', Ext.getBody().dom, '<form method="post" target="uploadFrame" action="' + this.uploadURL + '" enctype="multipart/form-data"></form>'));
				Ext.get(fileInput).appendTo(form);
				iframe.on('load', function(){
					
					var result = Ext.decode(iframe.dom.contentDocument.body.innerHTML);
					if(!result || !result.success){
						//scope.setValue(scope.originalValue);
						Ext.Msg.alert(__('Garp'), __('Error uploading file.'));	
					} else {
						scope.setValue(result.filename);
						scope.fireEvent('change', scope, result.filename);
					}
					
					form.remove();
					iframe.remove();
					lm.hide();
					
					
				});
				form.dom.submit();				
				
			} else {
				var file;
				if (fileInput.dom && fileInput.dom.files) {
					file = fileInput.dom.files[0];
				} else {
					file = fileInput;//.dom.files[0];
				}
				if (!file || !file.name) {
					return;
				}

				var error = function(label, message) {
					scope.setValue(scope.originalValue);
					Ext.Msg.alert(label || __('Garp'), message || __('Error uploading file.'));
				};

				var proceedToUpload = function(success) {
					if (!success) {
						var readableMaxSurface = Ext.util.Format.number(scope.maxSurface, "1000.000/i");
						var exampleA = Math.floor(Math.sqrt(scope.maxSurface));
						// round to nearest 1000
						exampleA = Math.round((exampleA + 500) / 1000) * 1000;
						var exampleB = Math.floor(scope.maxSurface / exampleA);
						error(__('Error'), '<b>' + __('Resolution too high. ' + 
								'Please make sure the image\'s surface area does not exceed ' + readableMaxSurface) + 
								' pixels. <br>For instance: ' + exampleA + ' x ' + exampleB + ' pixels.</b>');

					} else if (!scope.validateExtension(file.name)) {

						error(__('Error'),
							'<b>' + __('Extension not supported') + '</b><br /><br />' +
							__('Supported extensions are:') + '<br /> ' + scope.supportedExtensions.join(' ')
						);
						
					} else {
						var fd = new FormData();
						var xhr = new XMLHttpRequest();
						scope.uploadDialog = Ext.Msg.progress(__('Upload'), __('Initializing upload'));
						
						fd.append('filename', file);
						
						xhr.addEventListener('load', function(e){
							var response = Ext.decode(xhr.responseText);
							scope.uploadDialog.hide();
							if (response.success) {
								scope.setValue(response.filename);
								scope.fireEvent('change', scope, response.filename);
							} else {
								error();
							}
						}, false);
						xhr.addEventListener('error', function(e){
							scope.uploadDialog.hide();
							error();
						}, false);
						xhr.upload.addEventListener('progress', function(e){
							if (e.lengthComputable) {
								scope.uploadDialog.updateProgress(e.loaded / e.total);
								scope.uploadDialog.updateText(__('Uploading') + ' ' + (Math.ceil(e.loaded / e.total * 100)) + '%');
							}
						}, false);
						
						xhr.open('POST', scope.uploadURL);
						scope.uploadDialog.updateText(__('Uploading&hellip;'));
						
						// we'll use a timeout to be sure that the dialog is ready, small downloads otherwise result in an ugly flashy UX
						setTimeout(function(){
							xhr.send(fd);
						}, 350);
						
					}
				};

				if (this.isImage(file)) {
					this.validateResolution(file, proceedToUpload);
				} else {
					proceedToUpload(true);
				}
			}
		},
		
		/**
		 * sets up Drag 'n Drop
		 */
		setupDnD: function(){
		
			var opts = {
				normalized: false,
				preventDefault: true,
				stopPropagation: true
			};
			
			this.wrap.on('dragenter', function(e){
				// unfortunately, we can't grab file extension here, so we'll present it as allowed. On drop we'll check extensions 
				this.wrap.addClass('x-focus');
			}, this, opts);
			
			this.wrap.on('dragexit', function(e){
				this.wrap.removeClass('x-focus');
			}, this, opts);
			
			this.wrap.on('dragover', function(e){
			}, this, opts);
			
			this.wrap.on('drop', function(e){
				this.handleFileDrop(e);
			}, this, opts);
		},
		
		onDestroy: function(){
			if (this.uploadDialog) {
				this.uploadDialog.hide();
			}
			Ext.ux.form.UploadField.superclass.onDestroy.call(this);
		},
		
		initComponent: function(){
			this.on('fileselected', this.handleFileSelect, this);
			Ext.ux.form.UploadField.superclass.initComponent.call(this, arguments);
			this.on('afterrender', this.setupDnD, this);
		}
	});

Ext.reg('uploadfield', Ext.ux.form.UploadField);
