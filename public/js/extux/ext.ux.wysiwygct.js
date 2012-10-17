
Garp.Wysiwygct = Ext.extend(Ext.Panel,{

	cls: 'wysiwyg-ct',
	autoScroll: true,
	height: 300,
	
	tbar: new Ext.Toolbar({
		scope: this,
		defaults: {
			scope: this,
			xtype: 'button'
		},
		items: [{
			iconCls: 'icon-new',
			text: 'Add',
			ref: 'addBtn'
		
		}, {
			iconCls: 'icon-delete',
			text: 'Remove',
			ref: 'removeBtn'
		
		}, '-', {
			text: '<b style="text-transform: none;">Bold</b>',
			handler: function(){
				document.execCommand('Bold', false, null);
			}
		},{
			text: '<b style="text-transform: none;font-style: italic;">Italic</b>',
			handler: function(){
				document.execCommand('Italic', false, null);
			}
		}]
	}),
	
	addWysiwygBox: function(){
		var wysiwyg = new Garp.Wysiwyg({
			cls: 'grid-col-12-12'
		});
		this.add(wysiwyg);
		this.doLayout();
		this.setupDD();
		
	},
	
	removeWysiwygBox: function(){
		var box = Ext.get(Ext.get(window.getSelection().focusNode.parentNode).findParent('.wysiwyg-box'));
		box.remove();
		this.fixRows();
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
			Ext.get(el).removeClass('grid-col-' + i + '-' + this.maxCols);
		}
	},
	
	/**
	 * What width does this item have?
	 * @param {Object} el
	 */
	getCurrentColCount: function(el){
		for (var i = this.maxCols; i > 0; i--) {
			if (el.hasClass('grid-col-' + i + '-' + this.maxCols)) {
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
		this.body.ddScrollConfig = {
	        vthresh: 50,
	        hthresh: -1,
	        animate: true,
	        increment: 200
	    };
		
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
							el.addClass('grid-col-' + (newCol) + '-' + wysiwygct.maxCols);
						} else {
							if (count <= 1) {
								return;
							}
							wysiwygct.removeColClasses(el);
							el.addClass('grid-col-' + (newCol) + '-' + wysiwygct.maxCols);
						}
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
						this.el.frame();
						
						this.el.clearPositioning();
						
					} else {
						this.el.moveTo(this.originalXY[0], this.originalXY[1]);
						delete this.invalidDrop;
					}
					
					this.removeDropHiglight();
					wysiwygct.setupDD();
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
		Garp.Wysiwygct.superclass.initComponent.call(this, ct);
		
		this.on('afterlayout', this.setupDD, this, {
			single: true
		});
		this.on('render', function(){
			this.getTopToolbar().addBtn.setHandler(this.addWysiwygBox.createDelegate(this));
			this.getTopToolbar().removeBtn.setHandler(this.removeWysiwygBox.createDelegate(this));
		}, this);
	}
	
});
Ext.reg('wysiwygct', Garp.Wysiwygct);



Garp.Wysiwyg = Ext.extend(Ext.Panel, {
	
	html: 
		'<div class="dd-handle icon-move"></div>' + 
		'<div class="contenteditable">Enter text</div>' + 
		'<div class="target top"></div>' +
		'<div class="target right"></div>' +
		'<div class="target bottom"></div>' + 
		'<div class="target left"></div>',
		
	
	initComponent: function(ct){
		Garp.Wysiwyg.superclass.initComponent.call(this, ct);
		
		this.on('afterrender', function(){
			this.addClass('wysiwyg-box');
			this.body.select('.dd-handle, .target').each(function(el){
				el.dom.setAttribute(id, Ext.id());
			});
			
			var mdl = 'Snippet';
			var items = Ext.apply({}, Garp.dataTypes[mdl].formConfig[0].items[0]); 
			
			var i = new Ext.form.FormPanel({
				title: mdl,
				layout: 'form',
				ref: 'form',
				defaults: {
					autoWidth: true,
					border: false,
					bodyCssClass: 'garp-formpanel' // visual styling
				},
				items: items,
				applyTo: this.body.child('.contenteditable')
			});
		}, this);
	}
});
Ext.reg('wysiwyg', Garp.Wysiwyg);
