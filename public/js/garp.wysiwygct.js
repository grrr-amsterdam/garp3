Garp.WysiwygField = Ext.extend(Ext.form.TextField, {

	reset: function(){
		this.chapterct.removeAll(true);
		delete this.originalValue;
		//if (this.chapterct.items.length === 0) {
		//	this.chapterct.addWysiwygCt();
		//}
	},
	
	setValue: function(items){
		
		this.reset();
		
		var maxCols = this.maxCols;
		
		if (items) {
			Ext.each(items, function(item){
				this.chapterct.addWysiwygCt();
				var currentWysiwygCt = this.chapterct.items.last();
				Ext.each(item.content, function(node){
					var box = new Garp.dataTypes[node.model].Wysiwyg({
						ct: currentWysiwygCt,
						data: node.data,
						model: node.model,
						type: node.type,
						col: 'grid-' + node.columns + '-' + maxCols,
						maxCols: maxCols
					});
					currentWysiwygCt.add(box);
					currentWysiwygCt.afterAdd();
				});
				
			}, this);
		/*
	 // *after* the added items are layed-out
	 this.wysiwygct.on('afterlayout', function(){
	 this.originalValue = this.getValue();
	 }, this, {
	 single: true
	 });*/
		}
	},
	
	getValue: function(){
		
		var output = [];
		this.chapterct.items.each(function(wysiwygct){
			
				var content = [];
				wysiwygct.items.each(function(node){
					if(node.getValue()){
						content.push(node.getValue());
					}
				});
				if (content.length) {
					output.push({
						content: content,
						type: ''
					});
				}
		});
		
		return output;
		
		if (this.rendered && this.wysiwygct && this.wysiwygct.body.dom.childNodes) {
			var output = [];
			Ext.each(this.wysiwygct.body.dom.childNodes, function(el){
				var box = Ext.getCmp(el.id);
				var item = {
					col: box.col,
					'class': box.extraClass || null,
					columns: box.col.split('-')[1],
					data: box.getData ? box.getData() : null,
					model: box.model
				};
				output.push(item);
			});
			return output;
		} else {
			return '';
		}
	},
	
	isValid: function(){
		return true; // @TODO decide if this needs to go here!
	},
	
	isDirty: function(){
		if (this.getValue() && this.originalValue) {
			var f = Ext.util.JSON.encode;
			return f(this.getValue()) != f(this.originalValue);
		}
	},
	
	afterRender: function(){
		this.wrap = this.el.wrap();
		//this.wysiwygct = new Garp.Wysiwygct({
		//	renderTo: this.wrap
		//});
		this.chapterct = new Garp.Chapterct({
			renderTo: this.wrap,
			maxCols: this.maxCols
		});
		this.on('resize', function(){
			this.chapterct.setWidth(this.getWidth());
		}, this);
		
		this.el.hide();
		Garp.WysiwygField.superclass.afterRender.call(this);

	},
	
	initComponent: function(){
		Garp.WysiwygField.superclass.initComponent.call(this, arguments);
	},
	
	onDestroy: function(){
		this.wysiwygct.destroy();
	}
});
Ext.reg('wysiwygfield', Garp.WysiwygField);

Garp.Wysiwygct = Ext.extend(Ext.Panel,{

	cls: 'wysiwyg-ct',
	bodyCssClass: 'wysiwyg-body',
	autoScroll: true,
	autoHeight: true,
	padding: 30,
	maxCols: null,
	
	getWysiwygDataTypes: function(){
		var dataTypes = [];
		for(var i in Garp.dataTypes){
			if(Garp.dataTypes[i].Wysiwyg){
				dataTypes.push(Garp.dataTypes[i]);
			}
		}
		return dataTypes;
	},
	
	setupTbar: function(){
		
		function addMenuFactory(){
			var menu = [];
			Ext.each(this.getWysiwygDataTypes(), function(model){
				menu.push({
					text: model.text,
					iconCls: model.iconCls,
					handler: function(){
						var box = new model.Wysiwyg({
							ct: this,
							maxCols: this.maxCols,
							data: false
						});
					},
					scope: this
				});
			}, this);
			return menu;
		}
		
		this.tbar = new Ext.Toolbar({
			width: '100%', // @FIXME: width doesnt seem to work
			defaults: {
				xtype: 'button',
				scope: this
			},
			items: [{
				iconCls: 'icon-new',
				text: __('Add'),
				ref: 'addBtn',
				menu: addMenuFactory.call(this)
			}, '-', {
				ref: 'classMenu',
				editable: false,
				forceSelection: true,
				triggerAction: 'all',
				xtype: 'combo',
				store: [['normal', 'Normal']],
				value: 'normal',
				focusedBox: null,
				listeners: {
					change: function(menu, v){
						if (menu.focusedBox) {
							Ext.each(Garp.dataTypes[menu.focusedBox.model].wysiwygConfig.classMenu, function(cl){
								menu.focusedBox.el.removeClass(cl[0]);
							});
							menu.focusedBox.el.addClass(v);
							menu.focusedBox.extraClass = v;
						}
					}
				}
			},'-',
			{
				iconCls:'icon-wysiwyg-bold',
				ref: 'boldBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Bold', false, null);
				}
			}, {
				iconCls:'icon-wysiwyg-italic',
				ref: 'italicBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Italic', false, null);
				}
			}, '->',{text:'remove', handler: function(){
				this.ownerCt.remove(this);
			}}]
		});
	},
	
	setupTbarWatcher: function(){
		var states = ['bold','italic'];
		var scope = this;
		this.tbWatcherInterval = setInterval(function(){
			var tbar = scope.getTopToolbar();
			if(!tbar || Ext.select('.wysiwyg-box').elements.length === 0) {
				return;
			}
			for(var c=0, l = states.length; c<l; c++){
				var state = states[c];
				try {
					tbar[state + 'Btn'].toggle(document.queryCommandState(state), false);
				} catch(e){
					// querycommandstate doesnt always want to run.
					// @TODO find solution??
				}
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
						this.body.dom.execCommand(cmd, false, null);
						e.preventDefault();
					}
				}
			}
		}, this);
	},
	
	afterAdd: function(){
		this.doLayout();
		this.setupDD();
	},
	
	removeWysiwygBox: function(box){
		this.remove(box.id);
		this.doLayout();
	},
	
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
	
	setupClassMenu: function(box){
		var model = box.model;
		var menu;
		var val;
		if (Garp.dataTypes[model].wysiwygConfig) {
			menu = Garp.dataTypes[model].wysiwygConfig.classMenu;
			Ext.each(Garp.dataTypes[model].wysiwygConfig.classMenu, function(cl){
				if(box.el.hasClass(cl[0])){
					val = cl[0];
					return;
				}
			});
		} else {
			menu = [{
				normal: 'Normal'
			}];
		}
		this.getTopToolbar().classMenu.focusedBox = box;
		this.getTopToolbar().classMenu.store.loadData(menu);
		this.getTopToolbar().classMenu.setValue(val || 'normal');
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
			var el = Ext.get(elm);
			var handle = el.child('.dd-handle').id;
			dd.setHandleElId(handle);
			
			var targetTop = new Ext.dd.DDTarget(el.child('.top').id, 'wysiwyg-dragables-group', {});
			var targetRight = new Ext.dd.DDTarget(el.child('.right').id, 'wysiwyg-dragables-group', {});
			var targetBottom = new Ext.dd.DDTarget(el.child('.bottom').id, 'wysiwyg-dragables-group', {});
			var targetLeft = new Ext.dd.DDTarget(el.child('.left').id, 'wysiwyg-dragables-group', {});
			
			el.on('click', function(){
				var clickedBox = Ext.getCmp(el.id);
				wysiwygct.setupClassMenu(clickedBox);
			});
			
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
	},
	
	onDestroy: function(ct){
		if (this.tbWatcherInterval) {
			clearInterval(this.tbWatcherInterval);
		}
	}
	
});
Ext.reg('wysiwygct', Garp.Wysiwygct);

/* * * * */
Garp.Chapterct = Ext.extend(Ext.Panel,{

	autoScroll: true,
	autoHeight: true,
	border: false,
	bodyBorder: false,
	cls: 'chapter-ct',
	maxCols: null,
	
	addWysiwygCt: function(){
		this.add(new Garp.Wysiwygct({
			ct: this,
			maxCols: this.maxCols
		}));
		this.doLayout();
	},
	
	bbar: new Ext.Toolbar({
		width: '100%',
		items: ['->',{
			text: 'Add',
			handler: function(){
				this.ownerCt.ownerCt.addWysiwygCt();
			}
		}]
	}),
		
	/**
	 * I.N.I.T.
	 * @param {Object} ct
	 */
	initComponent: function(ct){
		Garp.Chapterct.superclass.initComponent.call(this, arguments);
	},
	
	afterRender: function(){
		this.on('resize', function(){
			this.items.each(function(i){
				i.setWidth(this.getWidth());
			}, this);
		}, this);
		this.addWysiwygCt();
		Garp.Chapterct.superclass.afterRender.call(this, arguments);
	}
	
	
});
Ext.reg('chapterct', Garp.Chapterct);