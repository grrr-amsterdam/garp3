

/* 
 * Note on saving content to the server:
 * Can't use wysiwygct.items as their position doesn't change on D 'n D
 * So have to traverse childNodes and grab content & classes & such like so:
 * 
 * Ext.getCmp(Ext.getCmp('rte-container').body.dom.childNodes[].id).content 
 * 
 */


Garp.Wysiwygct = Ext.extend(Ext.Panel,{

	cls: 'wysiwyg-ct',
	autoScroll: true,
	autoHeight: true,
	padding: 30,
	//height: '100%',
	//height: 300,
	saveItems: function(){
		var output = [];
		Ext.each(this.body.dom.childNodes, function(el){
			var box = Ext.getCmp(el.id);
			var item = {
				col: box.col,
				columns: box.col.split('-')[1],
				data: box.getData(),
				model: box.model
			};
			output.push(item);
		});
		console.dir(output);
	},
	
	setupTbar: function(){
		this.tbar = new Ext.Toolbar({
			defaults: {
				xtype: 'button',
				scope: this
			},
			items: [{
				iconCls: 'icon-new',
				text: __('Add'),
				ref: 'addBtn',
				menu: [{
					text: __('Text'),
					iconCls: 'icon-snippet',
					handler: this.addWysiwygBox,
					scope: this
				}, {
					text: __('Image'),
					iconCls: 'icon-img',
					handler: this.addWysiwygImgBox,
					scope: this
				}]
			},/* {
				iconCls: 'icon-delete',
				text: __('Delete'),
				ref: 'removeBtn'
			
			}, '-',*/ {
				text: '<b style="text-transform: none;">' + __('Bold') + '</b>',
				ref: 'boldBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Bold', false, null);
				}
			}, {
				text: '<b style="text-transform: none;font-style: italic;">' + __('Italic') + '</b>',
				ref: 'italicBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Italic', false, null);
				}
			},{
				text: __('Save'),
				handler: this.saveItems
			}]
		});
	},
	
	setupTbarWatcher: function(){
		var states = ['bold','italic'];
		var scope = this;
		setInterval(function(){
			var tbar = scope.getTopToolbar();
			if(!tbar || Ext.select('.wysiwyg-box').elements.length === 0) {
				return;
			}
			for(var c=0, l = states.length; c<l; c++){
				var state = states[c];
				tbar[state + 'Btn'].toggle(document.queryCommandState(state), false);
			}
		}, 100);
	},
	
	setupKeyboardHandling: function(){
		Ext.EventManager.on(document, 'keypress', function(e){
			if (e.ctrlKey) {
				var c = e.getCharCode(), cmd;
				if (c > 0) {
					c = String.fromCharCode(c);
					switch (c) {
						case 'b':
							cmd = 'Bold';
							break;
						case 'i':
							cmd = 'Italic';
							break;
					}
					if(cmd){
						document.execCommand(cmd, false, null);
						e.preventDefault();
					}
				}
			}
		}, this);
	},
	
	addWysiwygBox: function(){
		var wysiwyg = new Garp.Wysiwyg();
		this.add(wysiwyg);
		this.doLayout();
		this.setupDD();
	},
	
	addWysiwygImgBox: function(){
		var picker = new Garp.ModelPickerWindow({
			model: 'Image',
			listeners: {
				select: function(sel){
					if (sel.selected) {
						var imgId = sel.selected.data.id;
						var wysiwyg = new Garp.WysiwygImg({
							image: {
								id: imgId
							}
						});
						this.add(wysiwyg);
						this.doLayout();
						this.setupDD();
					}
					picker.close();
				},
				scope: this
			}
		});
		picker.show();
	},
	
	removeWysiwygBox: function(box){
		//var box = Ext.get(Ext.get(window.getSelection().focusNode.parentNode).findParent('.wysiwyg-box'));
		this.remove(box.id);
		this.doLayout();
	},
	
	maxCols: 12,
	
	/**
	 * ColClasses be gone
	 * @param {Object} el
	 */
	removeColClasses: function(el){
		if (!el) {
			return;
		}
		for (var i = 1; i <= this.maxCols; i++) {
			Ext.get(el).removeClass('grid-' + i + '-' + this.maxCols);
		}
	},
	
	/**
	 * What width does this item have?
	 * @param {Object} el
	 */
	getCurrentColCount: function(el){
		for (var i = this.maxCols; i > 0; i--) {
			if (el.hasClass('grid-' + i + '-' + this.maxCols)) {
				return i;
			}
		}
		return this.maxCols;
	},
	
	/**
	 * Setup Drag 'n Drop handlers & Ext resizer for all 'boxes'
	 */
	setupDD: function(){
	
		var wysiwygct = this;
		var dragables = Ext.query('.wysiwyg-box');
		this.getEl().addClass('disabled-targets');
		
		Ext.dd.ScrollManager.register(this.body);
		Ext.apply(Ext.dd.ScrollManager, {
	        vthresh: 50,
	        hthresh: -1,
	        animate: false, // important! Otherwise positioning will remain; resulting in off-set wysiwyg's
	        increment: 200
	    });
		
		Ext.each(dragables, function(elm){
		
			if (elm.resizer) {
				elm.resizer.destroy();
			}
			
			var w = null;
			var currentSize;
			
			// set up resizer:
			elm.resizer = new Ext.Resizable(elm.id, {
				handles: 'e',
				dynamic: true,
				transparent: true,
				listeners: {
					'beforeresize': function(){
						w = this.getEl().getWidth();
					},
					'resize': function(){
						var el = this.getEl();
						
						var nw = el.getWidth();
						var count = wysiwygct.getCurrentColCount(el);
						el.setStyle('width', '');
						
						var newCol = Math.ceil(wysiwygct.maxCols / (wysiwygct.getWidth() / nw));
						if (newCol < 1) {
							newCol = 1;
						}
						if (w < nw) {
							if (count >= wysiwygct.maxCols) {
								return;
							}
							wysiwygct.removeColClasses(el);
							el.addClass('grid-' + (newCol) + '-' + wysiwygct.maxCols);
						} else {
							if (count <= 1) {
								return;
							}
							wysiwygct.removeColClasses(el);
							el.addClass('grid-' + (newCol) + '-' + wysiwygct.maxCols);
						}
						Ext.getCmp(el.id).fireEvent('user-resize', w, nw, 'grid-' + newCol + '-' + wysiwygct.maxCols);
					}
				}
			});
			
			var dd = new Ext.dd.DD(elm, 'wysiwyg-dragables-group', {
				isTarget: false,
				ignoreSelf: true,
				scroll: true
			});
			
			Ext.apply(dd, {
			
				wysiwygct: wysiwygct,
				
				possibleSuspect: null,
				
				b4StartDrag: function(){
					if (!this.el) {
						this.el = Ext.get(this.getEl());
					}
					this.el.addClass('in-drag');
					this.originalXY = this.el.getXY();
				},
				
				startDrag: function(){
					this.wysiwygct.getEl().removeClass('disabled-targets');
				},
				
				onInvalidDrop: function(){
					this.invalidDrop = true;
				},
				
				removeDropHiglight: function(){
					Ext.select('.wysiwyg-box .active').each(function(el){
						el.removeClass('active');
					});
					delete this.possibleSuspect;
				},
				
				onDragOver: function(e, id){
					var possible = Ext.get(id);
					if (possible.parent('#' + this.el.id)) {
						return; // Don't allow DD on self!
					}
					this.removeDropHiglight();
					possible.addClass('active');
					this.possibleSuspect = possible;
				},
				
				onDragOut: function(e, id){
					this.removeDropHiglight();
				},
				
				endDrag: function(e, id){

					this.el.removeClass('in-drag');
					this.wysiwygct.getEl().addClass('disabled-targets');
					
					if (this.possibleSuspect) {
					
						var p = Ext.get(this.possibleSuspect);
						var top = p.hasClass('top');
						var bottom = p.hasClass('bottom');
						var left = p.hasClass('left');
						var right = p.hasClass('right');
						
						p = p.findParent('.wysiwyg-box');
					
						if (top || left) {
							this.el.insertBefore(p);
						} else {
							this.el.insertAfter(p);
						}
						this.el.frame(null,1);
						
					} else {
						this.el.moveTo(this.originalXY[0], this.originalXY[1]);
						delete this.invalidDrop;
					}
					
					this.removeDropHiglight();
					wysiwygct.setupDD();
					this.el.clearPositioning();
					this.el.setStyle('position','relative');
				}
				
			});
			var handle = Ext.get(elm).child('.dd-handle').id;
			dd.setHandleElId(handle);
			
			var targetTop = new Ext.dd.DDTarget(Ext.get(elm).child('.top').id, 'wysiwyg-dragables-group', {});
			var targetRight = new Ext.dd.DDTarget(Ext.get(elm).child('.right').id, 'wysiwyg-dragables-group', {});
			var targetBottom = new Ext.dd.DDTarget(Ext.get(elm).child('.bottom').id, 'wysiwyg-dragables-group', {});
			var targetLeft = new Ext.dd.DDTarget(Ext.get(elm).child('.left').id, 'wysiwyg-dragables-group', {});
			
		});
		
		this.getEl().addClass('disabled-targets');

	},
	
	/**
	 * I.N.I.T.
	 * @param {Object} ct
	 */
	initComponent: function(ct){
		
		this.setupTbar();
		this.setupTbarWatcher();
		this.setupKeyboardHandling();
		
		Garp.Wysiwygct.superclass.initComponent.call(this, ct);
		
		this.on('afterlayout', this.setupDD, this, {
			single: true
		});
		/*
		this.on('render', function(){
			///this.getTopToolbar().addBtn.setHandler(this.addWysiwygBox.createDelegate(this));
			this.getTopToolbar().removeBtn.setHandler(this.removeWysiwygBox.createDelegate(this));
		}, this);
		*/
	}
	
});
Ext.reg('wysiwygct', Garp.Wysiwygct);


/**
 * Wysiwyg
 */
Garp.Wysiwyg = Ext.extend(Ext.BoxComponent, {
	
	model: 'Text',
	html: 
		'<div class="dd-handle icon-move"></div>' + 
		'<div class="dd-handle icon-delete"></div>' + 
		'<div class="contenteditable">' +
		 	__('Enter text') +
			
			'<table><tbody><tr><td>Bla</td><td>Blup</td></tr></tbody></table>' +
			 
		'</div>' + 
		'<div class="target top"></div>' +
		'<div class="target right"></div>' +
		'<div class="target bottom"></div>' + 
		'<div class="target left"></div>',
		
	contentEditableEl: null,
	
	col: 'grid-12-12',
	
	allowedTags: ['a','b','i','br','p','ul','ol','li'],
	
	getData: function(){
		return this.contentEditableEl.dom.innerHTML;
	},

	filterHtml: function(){
		var scope = this;
		function walk(nodes){
			Ext.each(nodes, function(el){
				el.normalize();
				if(el.tagName){
					var tag = el.tagName.toLowerCase();
					if(scope.allowedTags.indexOf(tag) == -1){
						if (el.childNodes.length > 0) {
							while (el.childNodes.length > 0 && el.parentNode) {
								var child = el.childNodes[el.childNodes.length - 1];
								var clone = child.cloneNode(true);
								el.parentNode.insertBefore(clone, el);
								el.removeChild(child);
								el.parentNode.removeChild(el);
								walk(scope.contentEditableEl.dom.childNodes);
							}
						} else if(el.parentNode){
							el.parentNode.removeChild(el);
						}
					}
				}
				if (el.childNodes) {
					walk(el.childNodes);
				}
			});
		}
		walk(this.contentEditableEl.dom.childNodes);
	},
	
	afterRender: function(ct){
		Garp.Wysiwyg.superclass.afterRender.call(this, ct);
		
		this.el.select('.dd-handle.icon-delete').on('click', function(){
			this.ownerCt.removeWysiwygBox(this);
		},this);
	},
	
	initComponent: function(ct){
		Garp.Wysiwyg.superclass.initComponent.call(this, ct);
		
		this.on('user-resize', function(w, nw, nwCol){
			this.col = nwCol;
		}, this);
		
		this.on('afterrender', function(){
			this.addClass('wysiwyg-box');
			this.addClass(this.col);
			this.el.select('.dd-handle, .target').each(function(el){
				el.dom.setAttribute(id, Ext.id());
			});
			this.contentEditableEl = this.el.child('.contenteditable'); 
			this.contentEditableEl.dom.setAttribute('contenteditable', true);
			this.contentEditableEl.on('focus', this.filterHtml, this);
			this.contentEditableEl.on('click', this.filterHtml, this);
			this.contentEditableEl.on('blur', this.filterHtml, this);
		}, this);
		
	}
});
Ext.reg('wysiwyg', Garp.Wysiwyg);


/**
 * Wysiwyg Image
 * @param {Object} ct
 */
Garp.WysiwygImg = Ext.extend(Garp.Wysiwyg, {
	
	imgage: null,
	margin: 0,
	model: 'Image',
	
	getData: function(){
		return this.image;
	},
	
	// override: we don't need filtering for images:
	filterHtml: function(){
		return true;
	},
	
	initComponent: function(ct){
		
		Garp.WysiwygImg.superclass.initComponent.call(this, ct); // !!
		
		this.on('user-resize', function(w, nw){
			var i = this.image;
			var aspct = i.height / i.width;
			var nHeight = (nw * aspct) - this.margin;
			this.contentEditableEl.setHeight(nHeight);
			this.contentEditableEl.child('.img').setHeight(nHeight);
			this.setHeight(nHeight);
		});
		
		this.on('afterrender', function(){
			
			this.addClass('wysiwyg-image');
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
				
				var aspct = i.height / i.width;
				var nHeight = (scope.getWidth() * aspct) - scope.margin;
				
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
			};
			i.src = path;
			if(i.complete){
				i.onload();
			}
			
		}, this);
	}
});
Ext.reg('wysiwygimg', Garp.WysiwygImg);