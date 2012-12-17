Ext.ns('Garp.dataTypes');
Garp.dataTypes.Image.on('init', function(){
	
	this.iconCls = 'icon-img';
	
	// Thumbnail column:
	this.insertColumn(0, {
		header: '<span class="hidden">' + __('Image') + '</span>',
		dataIndex: 'id',
		width: 84,
		fixed: true,
		renderer: Garp.renderers.imageRelationRenderer,
		hidden: false
	});
	
	this.addListener('loaddata', function(rec, formPanel){
		function updateUI(){
			formPanel.preview.update(Garp.renderers.imagePreviewRenderer(rec.get('filename'), null, rec));
			formPanel.download.update({
				filename: rec.get('filename')
			});
		}
		if (formPanel.rendered) {
			updateUI();
		} else {
			formPanel.on('show', updateUI, null, {
				single: true
			});
		}
		// if we're in a relateCreateWindow, set it's height again, otherwise it might not fit.
		if (typeof formPanel.center == 'function' && rec.get('filename')) {
			formPanel.setHeight(440);
			formPanel.center();
		}
	}, true);
	
	
	// Remove these fields, cause we are about to change the order and appearance of them... 
	this.removeField('caption');
	this.removeField('filename');
	this.removeField('id');
	
	// ...include them again:
	this.insertField(0, {
		xtype: 'fieldset',
		style: 'margin: 0;padding:0;',
		items: [{
			name: 'id',
			hideFieldLabel: true,
			disabled: true,
			xtype: 'numberfield',
			hidden: true,
			ref: '../../../../_id'
		}, {
			name: 'filename',
			fieldLabel: __('Filename'),
			xtype: 'uploadfield',
			allowBlank: false,
			emptyText: __('Drag image here, or click browse button'),
			uploadURL: BASE + 'g/content/upload/type/image',
			ref: '../../../../filename',
			
			listeners: {
				'change': function(field, val){
					if (this.refOwner._id.getValue()) {
						var url = BASE + 'admin?' +
						Ext.urlEncode({
							model: Garp.currentModel,
							id: this.refOwner._id.getValue()
						});
						if (DEBUG) {
							url += '#DEBUG';
						}
						
						// because images won't reload if their ID is 
						// still the same, we need to reload the page 
						this.refOwner.formcontent.on('loaddata', function(){
							document.location.href = url;
						});
						this.refOwner.fireEvent('save-all');
					} else {
						field.originalValue = ''; // mark dirty
						this.refOwner.preview.update(Garp.renderers.uploadedImagePreviewRenderer(val));
						this.refOwner.download.update({
							filename: val
						});
						this.refOwner.fireEvent('save-all');
					}
					return true;
				}
			}
		}, {
			xtype: 'box',
			cls: 'garp-notification-boxcomponent',
			html: __('Only {1} and {2} files with a maximum of {3} MB are accepted', 'jpg, png', 'gif', '20'),
			fieldLabel: ' '
		}, {
			name: 'caption',
			xtype: 'textfield',
			fieldLabel: __('Caption')
		}, {
			xtype: 'box',
			ref: '../../../../preview',
			fieldLabel: __('Preview'),
			cls: 'preview',
			html: ''
		}, {
			xtype: 'box',
			hidden: false,
			// style: 'visibility:hidden',
			ref: '../../../../download',
			fieldLabel: ' ',
			hideFieldLabel: false,
			tpl: new Ext.XTemplate('<tpl if="filename">', '<a href="' + IMAGES_CDN + '{filename}" target="_blank">' + __('View original file') + '</a>', '</tpl>')
		}]
	});
	
	
	// Wysiwyg Editor
	this.Wysiwyg = Ext.extend(Garp.WysiwygAbstract, {
	
		imgage: null,
		margin: 0,
		
		getData: function(){
			return {
				id: this.image.id
			};
		},
		
		// override: we don't need filtering for images:
		filterHtml: function(){
			return true;
		},
		
		beforeInit: function(afterInitCb){
			var args = arguments;
			var picker = new Garp.ModelPickerWindow({
				model: 'Image',
				listeners: {
					select: function(sel){
						if (sel.selected) {
							var imgId = sel.selected.data.id;
							this.image = {
								id: imgId
							};
							afterInitCb.call(this, args);
						} else {
							this.destroy();
						}
						picker.close();
					},
					scope: this
				}
			});
			picker.show();
		},
		
		initComponent: function(ct){
			
			this.addClass('wysiwyg-image');
			this.addClass('wysiwyg-box');
			this.addClass(this.col);
				
			this.on('user-resize', function(w, nw){
				var i = this.image;
				var aspct = i.height / i.width;
				var nHeight = (nw * aspct) - this.margin;
				this.contentEditableEl.setHeight(nHeight);
				this.contentEditableEl.child('.img').setHeight(nHeight);
				this.setHeight(nHeight);
			});
			
			this.on('afterrender', function(){
				console.warn('afterrender img');
				this.contentEditableEl = this.el.child('.contenteditable');
				this.contentEditableEl.update('');
				this.contentEditableEl.dom.setAttribute('contenteditable', false);
				
				var i = new Image();
				var scope = this;
				var path = IMAGES_CDN + 'scaled/cms_preview/' + this.image.id;
				i.onload = function(){
					Ext.apply(scope.image, {
						width: i.width,
						height: i.height
					});
					console.warn(scope.ownerCt.getWidth());
					
					var aspct = i.height / i.width;
					var nHeight = (scope.ownerCt.getWidth() * aspct) - scope.margin;
					
					scope.contentEditableEl.setStyle({
						position: 'relative',
						padding: 0,
						height: nHeight + 'px'
					});
					
					scope.contentEditableEl.update('<div class="img"></div>');
					scope.contentEditableEl.child('.img').setStyle({
						height: nHeight + 'px',
						backgroundImage: 'url("' + path + '")'
					});
					
					scope.setHeight(nHeight);
					scope.ownerCt.doLayout();
					
					console.warn(i);
					
				};
				i.src = path;
				if (i.complete) {
					i.onload();
				}
				
			}, this);
			
			Garp.dataTypes.Image.Wysiwyg.superclass.initComponent.call(this, arguments);
			
		}
	});
});
