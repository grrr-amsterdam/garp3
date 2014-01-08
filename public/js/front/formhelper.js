/**
 * FormHelper
 * @package Garp
 */

 /**
 * Garp FormHelper Singleton.
 * Provides several goodies for forms:
 *  - validation
 *  - file upload
 *  - submit button disable to prevent duplicate entries
 *  - possible ajaxify (form element needs class 'ajax') 
 */
Garp.FormHelper = Garp.FormHelper || {};
Garp.apply(Garp.FormHelper, {

	/**
	 * @cfg {jQuery} The form(s) reference(s)
	 */
	form: $('.garp-form'),
	
	/**
	 * @cfg {String} class to put on the @cfg form element(s) when ajax calls are being made.
	 */
	ajaxBusyCls: 'loading',
	
	/**
	 * @cfg {Function} onAjaxComplete. Get's called on error, success or timeout. Passes: @param {Object} jqXhr @param {Object} status
	 */
	onAjaxComplete: function(jqXhr, status){
		var valid = false;
		var resp = false;
		if (status === 'success') {
			resp = $.parseJSON(jqXhr.responseText);
			if (resp && resp.html) {
				this.form.html(resp.html);
				valid = true;
			}
		}
		if (console && console.log && !valid) {
			console.log('Garp formHelper: server status ' + status);
		}
	},
	
	/**
	 * @cfg {Function} onBeforeAjax Possible to prevent ajax submission here; just return false.
	 */
	onBeforeAjax: function(){
		return true;
	},
	
	/**
	 * @cfg {Number} ajaxTimeout
	 */
	ajaxTimeout: 30000,
	
	/**
	 * @cfg {String} response type of the server
	 */
	ajaxDataType: 'json',
	
	
	// private
	/**
	 * Hijacks upload fields into nice AJAX things. Uses qq.FileUploader for this, but only includes it if necessary
	 * @param cb Callback after setup
	 * @param fh FormHelper reference
	 */
	hijackUploadFields: function(cb, fh){
		var fields = $('.hijack-upload', this.form);
		var scope = this;
		
		if (fields.length) {
			Garp.asyncLoad(BASE + 'js/fileuploader.js', 'js', function(){
				fields.each(function(i){
					var $target = $(this);
					var orig = $target;
					var name = $target.attr('name') || $target.data('name');
					$target.attr('name', name + '-filefield');
					var prepopulate = $target.data('prepopulate-filelist');
					var uploadType = $target.data('type') || 'image', imgTemplate = $target.data('image-template') || 'cms_list', url = BASE + 'g/content/upload/insert/1/mode/raw/type/' + uploadType, urlNonRaw = BASE + 'g/content/upload/insert/1/type/' + uploadType;
					
					$target = $target.parent();
					if (!$target[0]) {
						return;
					}
					var multiple = false;
					var maxItems = orig.data('max-files') || null;
					if (orig.data('max-files') && orig.data('max-files') > 1) {
						multiple = true;
					}
					var cfg = {
						element: $target[0],
						action: url,
						actionNonRawMode: urlNonRaw,
						debug: false,
						allowedExtensions: orig.attr('data-allowed-extensions') ? orig.attr('data-allowed-extensions').split(',') : null,
						sizeLimit: orig.attr('data-max-file-size') ? parseInt(orig.attr('data-max-file-size'), 10) * 1024 * 1024 : null,
						showMessage: function(msg){
							new Garp.FlashMessage({
								msg: msg,
								parseCookie: false
							});
						},
						template: '<div class="qq-uploader">' +
							'<div class="qq-upload-drop-area"><span>Sleep files hier om up te loaden</span></div>' +
							'<div class="qq-upload-button-container"><div class="qq-upload-button">uploaden</div></div>' +
							'<ul class="qq-upload-list"></ul>' +
							'</div>',
						fileTemplate: '<li>' +
							'<span class="qq-upload-file"></span>' +
							'<span class="qq-upload-spinner"></span>' +
							'<span class="qq-upload-size"></span>' +
							'<span class="qq-upload-failed-text">Mislukt</span>' +
							'<a class="qq-upload-cancel" href="#">Cancel</a>' +
							'<a class="remove">Verwijder</a>' +
							'</li>',
						multiple: multiple,
						maxItems: maxItems,
						onSubmit: function(id, filename){
							if(this.beforeUpload){
								this.beforeUpload($target, filename);
							}
							this.params = {
								'filename': filename
							};
							$target.parent('div').addClass('uploading');
						},
						onComplete: function(id, filename, responseJSON){
							var element = this.element;
							$(element).parent().removeClass('uploading').addClass('done-uploading');
							
							function checkCount(){
								var maxItems = $(element).data('max-items');
								var count = $('ul.qq-upload-list li', element).length;
								if (maxItems && maxItems == count) {
									$('.qq-upload-button-container', element).hide();
								} else {
									$('.qq-upload-button-container', element).show();
								}
							}
							checkCount();
							
							var ref = filename;
							
							if (responseJSON && responseJSON.id) {
								id = responseJSON.id;
								filename = responseJSON.filename;
								
								$('a.remove', element).unbind().bind('click', function(){
									var name = $(this).parent('li').find('span.qq-upload-file').text();
									$('input[data-filename="' + name + '"]', element).remove();
									$(this).parent('li').remove();
									checkCount();
								});
								
								$(element).append('<input type="hidden" value="' + id + '" name="' + name + '[]" data-filename="' + filename + '">');
								
								if(this.afterUpload){
									this.afterUpload(responseJSON, $target, ref);
								}
								
							} else {
								$('a.remove:last', element).click();
								Garp.FlashMessage({
									msg: __("Something went wrong while uploading. Please try again later")
								});
							}
						}
					};
					
					var uploaderConfig = Garp.apply(cfg, fh.uploaderConfig);
					var uploader = new qq.FileUploader(uploaderConfig);
					$(uploader._element).data('max-items', maxItems);
					if (cb && typeof cb === 'function') {
						cb($target);
					}
					if (fh.uploaderConfig.afterInit) {
						fh.uploaderConfig.afterInit(fh, $target);
					}
					
					// Add existing files to the list
					if (prepopulate) {
						for (var j = 0; j < prepopulate.length; j++) {
							var id = prepopulate[j].id;
							var filename = prepopulate[j].filename;
							// Create a fake onComplete call
							// @todo Is this really the way?
							var args = {};
							args = {
								id: id,
								filename: filename,
								fake: true
							};
							uploader._addToList(id, filename);
							uploaderConfig.onComplete.call({
								element: $target[0]
							}, args, args, args);
						}
					}
					if (fh.uploaderConfig.initComplete) {
						fh.uploaderConfig.initComplete(fh, $target);
					}
					
				});
			}, this);
		}
	},
	
	/**
	 * Turns the form into an Ajaxable thing
	 */
	ajaxify: function(){
		var fh = this, form = $(this.form);
		form.bind('submit', function(e){
			if (fh.validator && fh.validator.validateForm()) {
				e.preventDefault();
				if (fh.onBeforeAjax.call(fh)) {
					form.addClass(fh.ajaxBusyCls);
					$.ajax(form.attr('action'), {
						data: form.serializeArray(),
						type: form.attr('method'),
						dataType: fh.ajaxDataType,
						timeOut: fh.ajaxTimeout,
						complete: function(jqXhr, status){
							form.removeClass(fh.ajaxBusyCls);
							fh.onAjaxComplete.call(fh, jqXhr, status);
						}
					});
					return false;
				}
			}
		});
		return this;
	},
	
	/**
	 * Sets up duplicatable form elements
	 */
	setupDuplicators: function(){
		this._duplicators = [];
		var fh = this;
		$('.duplicatable', this.form).each(function(){
			fh._duplicators.push(new Garp.FormHelper.Duplicator(this, fh));
		});
	},

	/**
	 * Sets up validation
	 */	
	setupValidation: function(){
		
		var disabler = function(){
			this.disable(); // this == the submit button
		};
		
		this.validator = new Garp.FormHelper.Validator({
			form: this.form,
			listeners: {
				'formvalid': function(validator){
					$('button[type="submit"]', validator.form).bind('click.validator',disabler);
				},
				'forminvalid': function(validator){
					$('button[type="submit"]', validator.form).unbind('click.validator', disabler);
				},
				'fieldvalid':function(field){
					field.closest('div').removeClass('invalid');
				},
				'fieldinvalid': function(field){
					field.closest('div').addClass('invalid');
				}
			}
		}).bind();
		
	},
	
	/**
	 * Placeholders aren't supported in older navigators 
	 */
	fixPlaceholdersIfNeeded: function(){
		var i = document.createElement('input');
		if (typeof i.placeholder === 'undefined') {
			Garp.asyncLoad(BASE + 'js/plugins/jquery.addPlaceholder.js', 'js', function(){
				$('input[placeholder], textarea[placeholder]', this.form).addPlaceholder();
				/*
				this.form.bind('submit', function(){
					$('input[placeholder], textarea[placeholder]', this.form).each(function(){
						if($(this).val() == $(this).attr('placeholder')){
							$(this).val('');
						}
					});
					return true;
				});*/
			}, this);
		}
	},

	/**
	 * Escape characters that would otherwise mess up a CSS selector
	 * ("[" and "]")
	 */
	formatName: function(name) {
		if (name) {
			name = name.replace('[', '-sqb-open-').replace(']', '-sqb-close-');
		}
		return name;
	},
	
	/**
	 * Init a.k.a go!
	 */
	init: function(cfg){
		Garp.apply(this, {
			uploaderConfig: {}
			// duplicatorConfig: {},  // not used at the moment.
			// validationConfig: {} // not used at the moment.
		});
		
		Garp.apply(this, cfg);
		
		this.setupDuplicators(this);
		this.hijackUploadFields(null, this);
		this.setupValidation(this);
		
		if(this.form.hasClass('ajax')){
			this.ajaxify();
		}
		this.fixPlaceholdersIfNeeded();
		return this;
	}
});


/**
 * Simple duplicate-a-field or fieldset
 * @param {Object} field the field to upgrade
 * @param {Object} fh FormHelper reference
 * @param {Object} cfg
 */
Garp.FormHelper.Duplicator = function(field, fh, cfg){

	Garp.apply(this, {
	
		/**
		 * @cfg {String} Text for the add button
		 */
		addText: __('Add'),
		
		/**
		 * @cfg {String} Text for the remove button
		 */
		removeText: __('Remove'),
		
		/**
		 * @cfg {String} extra ButtonClass
		 */
		buttonClass: '',
		buttonAddClass: '',
		buttonRemoveClass: '',

		/**
		 * @cfg {Function} Callback function
		 */
		afterDuplicate: null,
		
		// private:
		wrap: $(field).closest('div'),
		field: $(field),
		fh: fh,
		numOfFields: 1,
		maxItems: $(field).data('max-items') || false,
		newId: ($(field).attr('id') || 'garpfield-' + new Date().getTime()) + '-',
		skipElements: '',
		
		/**
		 * updateUI:
		 * Shows or hide add button based on data-max-items property of field
		 */
		updateUI: function(){
			if (this.maxItems) {
				if (this.numOfFields >= this.maxItems) {
					this.addButton.hide();
				} else {
					this.addButton.show();
				}
			}
		},
		
		/**
		 * Now do some duplication
		 * @param jQuery dupl This can be a DOM node acting as the duplicate
		 */
		duplicateField: function(dupl){
		
			if (this.field.attr('type') == 'file') {
				return;
			}
			var newId = this.newId + this.numOfFields;
			var newField;
			var isRuntimeDuplicate = typeof dupl == 'undefined';
			dupl = dupl || this.field.clone();
			dupl.addClass('duplicate');
			var scope = this;
			var numOfFields = this.numOfFields;
			
			// name="aap" -> name="aap[1]"
			function changeName(name){
				if (name[name.length - 1] == ']') {
					return name.replace(/\[*\d\]/, '[' + (parseInt(numOfFields, 10)) + ']');
				}
				return name;
			}
			
			// converts add button into remove button: 
			function setupRemoveBtn(dupl){
				var buttonClass = scope.buttonRemoveClass || scope.buttonClass;
				var removeBtn = $('<input class="remove ' + buttonClass + '" type="button" value="' + scope.removeText + '">');
				removeBtn.appendTo(dupl);
				removeBtn.bind('click', function(){
					if (dupl.is('fieldset')) {
						dupl.remove();
					} else {
						dupl.closest('div').prev('div').find('.duplicatable').focus();
						dupl.closest('div').remove();
					}
					scope.numOfFields--;
					scope.updateUI();
					if (scope.fh.validator) {
						scope.fh.validator.init();
					}
				});
			}
			
			if (this.field.is('fieldset')) {
				this.field.attr('id', newId);
			} else {
				dupl.find('[id]').attr('id', newId);
			}
			
			// file uploads:
			if (this.field.hasClass('file-input-wrapper')) {
				var name = $('input[type=text],input[type=hidden]', this.field).attr('name');
				dupl = $('<input type="file" />');
				dupl.insertAfter(this.field.parent('div'));
				dupl.addClass('hijack-upload duplicatable');
				dupl.wrap('<div></div>');
				newField = dupl;
				this.fh.hijackUploadFields(setupRemoveBtn, this.fh);
				
			} else {
				dupl.find('[for]').attr('for', newId);
				if (isRuntimeDuplicate) {
					dupl.find('.errors').remove();
				}
				if (dupl.attr('name')) {
					dupl.attr('name', changeName(dupl.attr('name')));
				}
				dupl.find('[name]').each(function(){
					$(this).attr('name', changeName($(this).attr('name')));
				});
				if (isRuntimeDuplicate) {
					dupl.find('input').not('[type="radio"], [type="checkbox"], [type="hidden"]').val('');
					var skipElements = this.skipElements.split(',');
					dupl.find('[name]').each(function() {
						var $this = $(this);
						var name  = $this.attr('name');
						for (var i = 0; i < skipElements.length; i++) {
							if (name == skipElements[i] || new RegExp('\\['+skipElements[i]+'\\]$').test(name)) {
								$this.remove();
								break;
							}
						}
					});
						
				}
				dupl.find('.invalid').removeClass('invalid');
				newField = dupl.find('.duplicatable').val('');
				
				setupRemoveBtn(dupl);
				
				dupl.insertBefore(this.addButton);

				if (this.fh.validator) {
					this.fh.validator.init();
				}
				
			}
			this.numOfFields++;
			this.updateUI();
			if (this.afterDuplicate) {
				var fnScope = this.afterDuplicate.split('.');
				for (var x = 0, fn = window, fnParent = window, fo; x < fnScope.length; x++) {
					fo = fnScope[x];
					// fnParent is always one step behind, ending up being the
					// "parent" object of the method, allowed for call() to be
					// used.
					fnParent = fn;
					if (typeof fn[fo] !== 'undefined') {
						fn = fn[fo];
					} else {
						throw new Error('Could not resolve callback path '+this.afterDuplicate);
					}
				}

				if (typeof fn == 'function') {
					fn.call(fnParent, dupl);
				} else {
					throw new Error('Given callback "'+this.afterDuplicate+'" is not a function.');
				}
			}
			if (isRuntimeDuplicate) {
				newField.focus();
			}
		},
		
		/**
		 * Create DOM & listeners
		 */
		createAddButton: function(){
			var that = this;
			var buttonClass = this.buttonAddClass || this.buttonClass;
			this.field.after('<input class="add ' + buttonClass + '" type="button" value="' + this.addText + '">');
			this.addButton = this.field.next('.add');
			this.addButton.bind('click', function(e) {
				that.duplicateField();
				e.preventDefault();
				return false;
			});
			return this;
		},
		
		/**
		 * Go !
		 */
		init: function(){
			// check if we're not already initialized
			if (this.field.attr('data-duplicator') == 'initialized') {
				return; // nothing to do!
			} 
			this.field.attr('data-duplicator', 'initialized');

			if (this.field.attr('data-button-class')) {
				this.buttonClass = this.field.attr('data-button-class');
			}
			if (this.field.attr('data-button-add-class')) {
				this.buttonAddClass = this.field.attr('data-button-add-class');
			}
			if (this.field.attr('data-button-add-text')) {
				this.addText = this.field.attr('data-button-add-text');
			}
			if (this.field.attr('data-button-remove-class')) {
				this.buttonRemoveClass = this.field.attr('data-button-remove-class');
			}
			if (this.field.attr('data-button-remove-text')) {
				this.removeText = this.field.attr('data-button-remove-text');
			}
			if (this.field.attr('data-skip-elements')) {
				this.skipElements = this.field.attr('data-skip-elements');
			}
			if (this.field.attr('data-after-duplicate')) {
				this.afterDuplicate = this.field.attr('data-after-duplicate');
			}
			this.createAddButton();
			var name = this.field.attr('name');
			var scope = this;
			if (!name) {
				// this is a fieldset. Find all elements in it to be renamed:
				$('[name]', this.field).each(function(){
					var name = $(this).attr('name');
					if (name[name.length - 1] !== ']') {
						$(this).attr('name', name + '[' + scope.numOfFields + ']');
					}
				});
				
				// find duplicates added by the server
				var classToLookFor = this.field.attr('class').split(' ')[0];
				this.wrap = this.field.wrap('<div class="fieldset-wrap"></div>').parent();
				this.addButton.detach().appendTo(this.wrap);
				// when the server adds duplicates, move these to the wrapper as
				// well
				var duplicate = this.wrap.next('.'+classToLookFor);
				while (duplicate.length) {
					this.duplicateField(duplicate);
					duplicate = this.wrap.next('.'+classToLookFor);
				}
			} else {
				// normal 'plain' field. Only change this name
				if (name[name.length - 1] !== ']') {
					this.field.attr('name', name + '[' + this.numOfFields + ']');
				}
			}
			return this;
		}
	});
	Garp.apply(this, cfg);
	return this.init();
};


/**
 * FormHelper Validator
 * @param {Object} cfg
 */
Garp.FormHelper.Validator = function(cfg){

	// Apply defaults:
	Garp.apply(this, {
		/**
		 * @cfg {jQuery} form, where to check for errors in:
		 */
		form: $('.garp-form'),
		
		/**
		 * @cfg {Array} tags to check. Might choose a subset via selectors e.g. input[type="checkbox"]
		 */
		elms: ['input', 'select', 'textarea'],
		
		/**
		 * @cfg {String}/{jQuery} msgTarget. "below" or jQuery selector where to put errors
		 */
		msgTarget: 'below',
		
		/**
		 * @cfg {String} If 'name' attr not set, use this text:
		 */
		missingNameText: __('This field'),
		
		/**
		 * @cfg {Bool} Whether or not to let the user know of validations error 'live'
		 */
		interactive: true
	});
	
	// Custom config:	
	Garp.apply(this, cfg);
	
	// Validator extends Garp.Observable
	Garp.apply(this, new Garp.Observable(this));
	
	// Internals:
	Garp.apply(this, {
	
		// global flag
		hasErrors: false,
		
		// Our collection of validation rules:
		rules: {
			
		// Required field validation:
			required: {
				init: function(field){
					if(field.parents('.multi-input.required').length){
						field.attr('required', 'required');
					}
				},
				fn: function(field){
					if (!field.attr('required')) {
						return true;
					}
					switch (field.attr('type')) {
						// grouped fields:
						case 'checkbox':
						case 'radio':
							var checked = false;
							var fields = $(this.elements).filter($('input[name="' + field.attr('name') + '"]'));
							fields.each(function(){
								if ($(this).attr('checked')) {
									checked = true;
								}
							});
							return checked;
						// single field:
						default:
							return $(field).val() !== '' && !$(field).hasClass('placeholder');
					}
				},
				errorMsg: __("Value is required and can't be empty")
			},
			
		// Simple email validation
			email: {
				fn: function(field){
					if (field.attr('type') == 'email' && field.val().length) {
						return (/^(\w+)([\-+.\'][\w]+)*@(\w[\-\w]*\.){1,5}([A-Za-z]){2,6}$/.test(field.val()));
					}
					return true;
				},
				errorMsg: __("'${1}' is not a valid email address in the basic format local-part@hostname")
			},
			
		// HTML5-pattern validation (RegExp)
			pattern: {
				RegExCache: {},
				fn: function(field){
					if (!field.attr('pattern') || !field.val().length) {
						return true;
					}
					var key = field.attr('pattern');
					var cache = this.rules.pattern.RegExCache;
					if(!cache[key]){ // compile regexes just once.
						cache[key] = new RegExp('^' + field.attr('pattern') + '$'); 
					}
					return cache[key].test(field.val());
				},
				errorMsg: __("'${1}' does not match against pattern '${2}'")
			},
		
		// URL
		url:{
			mailtoOrUrlRe: /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|((((^https?)|(^ftp)):\/\/)?([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i,
			stricter: /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|(((^https?)|(^ftp)):\/\/([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i,
			init: function(field){
				if(!field.attr('type') || field.attr('type') !== 'url'){
					return;
				}
				var scope = this;
				var mailtoOrUrlRe = this.rules.url.mailtoOrUrlRe;
				var stricter = this.rules.url.stricter;
				$(field).bind('blur.urlValidator', function(){
					if($(this).val() !== '' && !stricter.test($(this).val())){
						if ($.trim($(this).val()).substr(0, 7) !== 'http://') {
							$(this).val('http://' + $.trim($(this).val()));
						}
					}
				}).attr('data-force-validation-on-blur',field.attr('name'));
			}, fn: function(f){
				if(f.attr('type') == 'url' && f.val().length){
					return this.rules.url.mailtoOrUrlRe.test(f.val());
				}
				return true;
			},
			errorMsg: __("'${1}' is not a valid URL")
		},
		
		
		// Dutch postalcode and filter
			dutchPostalCode: {
				init: function(field){
					if(!field.hasClass('dutch-postal-code')){
						return;
					}
					// bind a 'filter' function before validation:
					$(field).attr('maxlength', 8).bind('blur.duthPostalcodeValidator', function(){
						var v = /(\d{4})(.*)(\w{2})/.exec($(this).val());
						if (v) {
							$(this).val(v[1] + ' ' + (v[3].toUpperCase()));
						}
					});
				},
				fn: function(field){
					if(!field.hasClass('dutch-postal-code') || !field.val().length){
						return true;
					}
					return (/^\d{4} \w{2}$/.test( field.val() ));
				},
				errorMsg: __("'${1}' is not a valid Dutch postcode")
			},
			
			identicalTo: {
				init: function(field){
					if (field.attr('data-identical-to')) {
						var name = Garp.FormHelper.formatName(field.attr('data-identical-to'));
						field.attr('data-force-validation-on-blur', name);
						var scope = this;
						$('[name="' + name + '"]').on('blur', function(){
							scope.validateField(field, scope.rules.identicalTo);
						});
					}
				},
				fn: function(field){
					if(!field.attr('data-identical-to') || !field.val().length){
						return true;
					}
					var name = field.attr('name');
					var theOtherName = Garp.FormHelper.formatName(field.attr('data-identical-to'));
					var theOtherField = $('[name="' + theOtherName + '"]');
					var oVal = theOtherField.val();
					
					return field.val() === oVal;
				},
				errorMsg: __("Value doesn't match")
			}
		},
		
		/**
		 * Rules might want to init; filter methods might be bound to various field events, for example
		 */
		initRules: function(){
			var elms = this.elements.toArray();
			var scope = this;
			Garp.each(this.rules, function(rule){
				if (rule.init) {
					Garp.each(elms, function(elm){
						rule.init.call(this, $(elm));
					}, this);
				}
			}, this);
		},
		
		/**
		 * Gives our rules a convenient number
		 */
		setRuleIds: function(){
			var c = 0;
			Garp.each(this.rules, function(rule){
				rule.id = c = c + 1;
			});
		},
		
		/**
		 * Returns the target element for error messages. It might create one first
		 * Possible to override and use a single msgTarget. Use @cfg msgTarget for this
		 * @param {DOM Element} field element
		 * @return {jQuery} Message Target ul
		 */
		getMsgTarget: function(field){
			var t;
			if (this.msgTarget === 'below') {
				if ($('ul.errors', $(field).closest('div')).length) {
					t = $('ul.errors', $(field).closest('div'));
				} else {
					t = $('<ul class="errors"></ul>').appendTo($(field).closest('div'));
				}
			} else {
				t = msgTarget;
			}
			return t;
		},
		
		/**
		 * Some fields need to be grouped: (One error for all 'related' fields)
		 * @param {DOM Element} field
		 * @param {Object} rule
		 * @return {jQuery} unique field
		 */
		getUniqueField: function(field, rule){
			var $field = $(field);
			// do group multi-input fields (radio / checkboxes)...
			if ($field.closest('div').find('.multi-input').length) { // fields are allways wrapped in <div> so we search for that one first.
				return Garp.FormHelper.formatName($field.attr('name')) + rule.id;
			}
			// ...but don't group duplicatable ones:
			return $field.attr('id') + rule.id;
		},
		
		/**
		 * Add an error message to a field
		 * @param {DOM Element} field
		 * @param {Object} rule
		 */
		setError: function(field, rule){
			var t = this.getMsgTarget(field);
			var uf = this.getUniqueField(field, rule);
			if (!$('li[data-error-id="' + uf + '"]').length) { // Don't we already have this message in place?
				var $field = $(field);
				var name = $field.attr('name') ? Garp.FormHelper.formatName($field.attr('name')) : this.missingNameText;
				var errorMsg = Garp.format(rule.errorMsg, $field.val() || '', name );
				t.first().append('<li data-error-id="' + uf + '">' + errorMsg.replace(/\[\]/, '') + '</li>');
			}
		},
		
		/**
		 * Clears errors
		 * @param {DOM Element} field
		 * @param {Object} rule
		 */
		clearError: function(field, rule){
			$('li[data-error-id="' + this.getUniqueField(field, rule) + '"]', this.form).remove();
		},
		
		// private
		_blurredElement: null,
		
		/**
		 * Live Validation events handlers:
		 */
		bindInteractiveHandlers: function(){
			var scope = this;
			this.elements.off('focus.validator blur.validator').on('focus.validator', function(){
				if (scope._blurredElement) {
					scope.validateField(scope._blurredElement);
				}
			}).on('blur.validator', function(){
				scope._blurredElement = this;
				if ($(this).attr('data-force-validation-on-blur')) {
					scope.validateField($(this).attr('data-force-validation-on-blur'));
				}
			});
		},
		
		/**
		 * Util: Adds a rule. A rule needs a fn propery {Function} and an errorMsg {String}
		 * @param {Object} ruleConfig
		 */
		addRule: function(ruleConfig){
			Garp.apply(this.rules, ruleConfig);
			this.setRuleIds();
			return this;
		},
		
		/**
		 * Util: Set a different message for a rule
		 * @param {Object} ruleName
		 * @param {Object} msg
		 */
		setRuleErrorMsg: function(ruleName, msg){
			if (this.rules[ruleName] && msg) {
				this.rules[ruleName].errorMsg = msg;
			}
			return this;
		},
		
		/**
		 * Validates a single field.
		 * @param {jQuery selector string} field
		 * @param {Validator Object} {optional} rule 
		 * @return {Bool} valid or not
		 */
		validateField: function(field, ruleObj){
			field = $(field);
			var valid = true;
			if (ruleObj && ruleObj.fn) {
				if(ruleObj.fn.call(this, field)){
					this.clearError(field, ruleObj);
				} else {
					this.setError(field, ruleObj);
					this.hasErrors = true;
					valid = false;
				}
			} else {
				Garp.each(this.rules, function(rule){
					if (rule.fn.call(this, field)) {
						this.clearError(field, rule);
					} else {
						this.setError(field, rule);
						this.hasErrors = true;
						valid = false;
					}
				}, this);
			}
			this.fireEvent(valid ? 'fieldvalid' : 'fieldinvalid', field);
			return valid;
		},
		
		/**
		 * Clear all error messages in bulk
		 */
		clearErrors: function(){
			$('ul.errors', this.form).remove();
			this.hasErrors = false;
			return this;
		},
		
		/**
		 * Validate the form!
		 * @return {Bool} valid or not
		 */
		validateForm: function(){
			this.hasErrors = false;
			Garp.each(this.elements.toArray(), this.validateField, this);
			this.fireEvent(this.hasErrors ? 'forminvalid' : 'formvalid', this);
			return !this.hasErrors;
		},
		
		/**
		 * Necessary for IE and placeholders:
		 * values might otherwise be sent to the server. yuck!
		 */
		cleanupPlaceholders: function(){
			Garp.each(this.elements.toArray(), function(f){
				f = $(f);
				if(f.attr('placeholder') && f.attr('placeholder') == f.val()){
					f.val('');
				}
			});
		},
		
		/**
		 * Necessary for IE and placeholders:
		 * Re-add the placeholders we just removed.
		 */
		resetPlaceholders: function(){
			Garp.each(this.elements.toArray(), function(f){
				$(f).addPlaceholder();
			});
		},
		
		/**
		 * Binds validateForm to submit or other event. Possible to bind this to a specific element
		 * @param {jQuery} element, defaults to @cfg form
		 * @param {String} event, defaults to 'submit'
		 */
		bind: function(elm, event){
			if (!elm) {
				elm = this.form;
			}
			var s = this;
			elm.on((event || 'submit') + '.validator', function(e){
				s.cleanupPlaceholders();
				if (!s.validateForm()) {
					if ($().addPlaceholder) {
						s.resetPlaceholders();
					}
					e.preventDefault();
					return false;
				}
				return true;
			});
			return s;
		},
		
		/**
		 * INITIALISE!
		 */
		init: function(){
			this.form.attr('novalidate', 'novalidate');
			this.elements = $(this.elms.join(', '), this.form);
			this.initRules();			
			this.setRuleIds();
			if (this.interactive) {
				this.bindInteractiveHandlers();
			}
			return this;
		}
	});
	return this.init();
};
