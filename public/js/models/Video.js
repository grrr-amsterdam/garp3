Ext.ns('Garp.dataTypes');
Garp.dataTypes.Video.on('init', function(){

	// Thumbnail column:
	this.insertColumn(0, {
		header: '<span class="hidden">' + __('Thumbnail') + '</span>',
		width: 84,
		fixed: true,
		dataIndex: 'thumbnail',
		renderer: Garp.renderers.imageRenderer
	});
	
	// Remove 'required' fields that have nothing to do with the UI:
	//this.removeField('identifier');
	//this.removeField('type');
	
	
	// Move url field:
	var urlField = this.getField('url');
	this.removeField('url');
	this.insertField(1, urlField);
	// ..and put some infotexts near it:
	this.insertField(2, {
		xtype: 'box',
		cls: 'garp-notification-boxcomponent',
		html: __('YouTube and Vimeo Url\'s are supported'),
		fieldLabel: ' '
	});
	
	// Preview & other fields:
	this.addFields([{
		xtype: 'box',
		ref: '../../../preview',
		fieldLabel: __('Preview'),
		data: {
			player: '',
			width: 0,
			height: 0
		},
		tpl: Garp.videoTpl
	}, {
		xtype: 'box',
		cls: 'separator'
	}, {
		name: 'tags',
		fieldLabel: __('Tags'),
		xtype: 'displayfield',
		allowBlank: true,
		style: 'max-height: 18px;overflow:hidden;'
	}, {
		name: 'video_author',
		fieldLabel: __('Author'),
		allowBlank: true,
		xtype: 'displayfield'
	}]);
	
	this.getField('player').hidden = true;
	
	this.addListener('loaddata', function(rec, formPanel){
		function updateUI(){
			if (rec.phantom) {
				formPanel.preview.update({
					width: 0,
					height: 0,
					player: ''
				});
			} else {
				var w = formPanel.preview.getWidth();
				var h = Math.floor((9 * w) / 16);
				if (rec.get('player')) {
					formPanel.preview.update({
						width: w,
						height: h,
						player: rec.get('player')
					});
				}
			}
		}
		// We want to calculate the width. If we're to early, we can get strange values for width. First let ExtJS doLayout's get run:
		if (formPanel.rendered) {
			updateUI.defer(500);
		} else {
			formPanel.on('show', updateUI, null, {
				single: true
			});
		}
	});
	

	this.Wysiwyg = Ext.extend(Garp.dataTypes.Image.Wysiwyg, {
		
		model: 'Video',
		idProperty: 'id',
		
		pickerHandler: function(sel, afterInitCb){
		
			this.data = {
				id: sel.data.id,
				image: sel.data.image
			};
			var args = Array.prototype.slice.call(arguments);
			args.shift();
			afterInitCb.call(this, args);
		},
		
		getData: function(){
			return {
				id: this.data.id,
				image: this.data.image
			};
		},
		
		initComponent: function(){
			
			this.html += '<div class="contenteditable"></div>'; 
		
			this.addClass('wysiwyg-image');
			this.addClass('wysiwyg-box');
			if (this.col) {
				this.addClass(this.col);
			}
			
			this.on('user-resize', function(w, nw){
				this.setHeight(this.resizeContent(nw));
			});
			this.on('afterrender', function(){
				this.contentEditableEl = this.el.child('.contenteditable');
				this.contentEditableEl.update('');
				this.contentEditableEl.dom.setAttribute('contenteditable', false);
				
				var i = new Image();
				var scope = this;
				var path = this.data.image;
				i.onload = function(){
					Ext.apply(scope.data, {
						width: i.width,
						height: i.height
					});
					
					scope.contentEditableEl.setStyle({
						position: 'relative',
						padding: 0
					});
					
					scope.contentEditableEl.update('<div class="img"></div>');
					scope.contentEditableEl.child('.img').setStyle({
						backgroundImage: 'url("' + path + '")'
					});
					
					scope.resizeContent(scope.contentEditableEl.getWidth());
					if (scope.ownerCt) {
						scope.ownerCt.doLayout();
					}
					
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
