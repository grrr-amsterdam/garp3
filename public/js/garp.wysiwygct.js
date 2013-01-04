Garp.WysiwygField = Ext.extend(Ext.form.TextField, {

	region: 'center',

	hideLabel: true,

	bodyStyle: 'overflow-y: auto',

	reset: function(){
		this.chapterct.removeAll(true);
		delete this.originalValue;
		if(!this.chapterct.items.length){
			this.chapterct.addWysiwygCt();
		}
	},

	hideMode: 'display',

	extraTypes: null,
	
	setValue: function(items){
		this.reset();
		var maxCols = this.maxCols;

		if (items && items.length) {
			this.chapterct.removeAll(true);
			Ext.each(items, function(item){
				this.chapterct.addWysiwygCt({
					type: item.type
				});
				var currentWysiwygCt = this.chapterct.items.last();
				Ext.each(item.content, function(node){
					if(!node.model){
						if (console && console.dir) {
							console.dir(node);
						}
						throw 'Model type not found. DB corrupted or silly developer at work...';
					}
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
		}
	},
	
	getValue: function(){
		
		var output = [];
		this.chapterct.items.each(function(wysiwygct){
			var content = [];
			if (!wysiwygct.body.dom) {
				return;
			}
			Ext.each(wysiwygct.body.dom.childNodes, function(elm){
				var node = Ext.getCmp(elm.getAttribute('id'));
				if (node.getValue()) {
					var o = node.getValue();
					if(node.type){
						o.type = node.getType();
					}
					content.push(o);
				}
			});
			if (content.length) {
				output.push({
					content: content,
					type: wysiwygct.getExtraType()
				});
			}
		}, this);
		return output;
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
		this.chapterct = new Garp.Chapterct({
			renderTo: this.wrap,
			maxCols: this.maxCols,
			extraTypes: this.extraTypes
		});
		this.on('resize', function(){
			this.chapterct.setWidth(this.getWidth());
		}, this);
		
		this.el.hide();
		Garp.WysiwygField.superclass.afterRender.call(this);

	},
	
	initComponent: function(cfg){
		Garp.WysiwygField.superclass.initComponent.call(this, arguments);
	}
	
});
Ext.reg('wysiwygfield', Garp.WysiwygField);


/* * * */

Garp.Wysiwygct = Ext.extend(Ext.Panel,{

	cls: 'wysiwyg-ct',
	bodyCssClass: 'wysiwyg-body',
	
	bodyBorder: false,
	autoScroll: true,
	autoHeight: true,
	padding: '30',
	maxCols: null,
	extraTypes: null,
	
	getWysiwygDataTypes: function(){
		var dataTypes = [];
		for(var i in Garp.dataTypes){
			if(Garp.dataTypes[i].Wysiwyg){
				dataTypes.push(Garp.dataTypes[i]);
			}
		}
		return dataTypes;
	},
	
	setExtraType: function(){
		if (this.el) {
			var type = this.getTopToolbar().extraTypesMenu.getValue();
			var wrap = this.el.select('.wysiwyg-body');
			Ext.each(this.extraTypes, function(t){
				wrap.removeClass(t[0]);
			}, this);
			wrap.addClass(type);
			this.ownerCt.extraType = type;
		} 
	},
	
	getExtraType: function(){
		return this.getTopToolbar().extraTypesMenu.getValue();
	},
	
	
		/**
		 * function getRange
		 *
		 * @experimental
		 *
		 * @return the current mouse selected range
		 */
		getRange: function(){
			var range, sel, container;
			
			sel = window.getSelection();
			if (sel.getRangeAt) {
				if (sel.rangeCount > 0) {
					range = sel.getRangeAt(0);
				}
			} else {
				// Old WebKit
				range = document.createRange();
				range.setStart(sel.anchorNode, sel.anchorOffset);
				range.setEnd(sel.focusNode, sel.focusOffset);
				
				// Handle the case when the selection was selected backwards (from the end to the start in the document)
				if (range.collapsed !== sel.isCollapsed) {
					range.setStart(sel.focusNode, sel.focusOffset);
					range.setEnd(sel.anchorNode, sel.anchorOffset);
				}
			}
			return range;
		},
	
	setupTbar: function(){
		
		function addMenuFactory(){
			var menu = [{
				text: __('Add chapter'),
				iconCls: 'icon-wysiwyg-add-chapter',
				handler: function(){
					this.ownerCt.addWysiwygCt({
						type:''
					}, this);
				},
				scope: this
			},'-'];
			Ext.each(this.getWysiwygDataTypes(), function(model){
				menu.push({
					text: __(model.text),
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
			cls: 'garp-formpanel-toolbar',
			padding: '0 10 0 10',
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
			}, ' ', {
				editable: false,
				forceSelection: true,
				triggerAction: 'all',
				xtype: 'combo',
				ref: 'extraTypesMenu',
				store: this.extraTypes,
				value: '',
				listeners: {
					select: function(menu, v){
						this.ownerCt.ownerCt.setExtraType();
						this.blur();
						return true;
					}
				}
			}, ' ', ' ', {
				iconCls: 'icon-wysiwyg-bold',
				ref: 'boldBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Bold', false, null);
				}
			}, {
				iconCls: 'icon-wysiwyg-italic',
				ref: 'italicBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Italic', false, null);
				}
			}, {
				iconCls: 'icon-wysiwyg-orderedlist',
				ref: 'insertorderedlistBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Insertorderedlist', false, null);
				}
			},{
				iconCls: 'icon-wysiwyg-unorderedlist',
				ref: 'insertunorderedlistBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					document.execCommand('Insertunorderedlist', false, null);
				}
			},{
				iconCls: 'icon-wysiwyg-createlink',
				ref: 'createlinkBtn',
				clickEvent: 'mousedown',
				enableToggle: true,
				handler: function(b, e){
					e.preventDefault();
					
					var scope = this;
					
					function isAlreadyLink(){
						var isLink = false;
						if (scope.getCurrentTagName() == 'A') { // now select node contents:
							scope.getRange().selectNodeContents(scope.getRange().endContainer);
							return true;
						}
						Ext.each(scope.getRange().endContainer.childNodes, function(node){
							if (node.nodeName && node.nodeName == 'A') {
								isLink = true;
								return false;
							}
						});
						return isLink;
					}
					
					
					if (isAlreadyLink()) {
						document.execCommand('Unlink', false, null);
					} else {
						var sel = this.getSelection();
						var txt = sel.toString();
						if (txt) {
							var range = scope.getRange();
							
							var dialog = new Ext.Window({
								title: __('Add link'),
								iconCls: 'icon-richtext-add-link',
								width: 445,
								modal: true,
								height: 240,
								border: true,
								layout: 'fit',
								defaultButton: '_url', // defaultButton can focus anything ;-)
								items: [{
									xtype: 'fieldset',
									bodyCssClass: 'garp-dialog-fieldset',
									labelWidth: 160,
									items: [{
										xtype: 'textfield',
										fieldLabel: __('Url'),
										name: 'url',
										id: '_url',
										vtype: 'mailtoOrUrl',
										allowBlank: false,
										plugins: [Garp.mailtoOrUrlPlugin],
										value: ''
									}, {
										xtype: 'textfield',
										fieldLabel: __('Title'),
										name: 'title',
										value: ''
									}, {
										xtype: 'checkbox',
										allowBlank: true,
										fieldLabel: __('Open in new window'),
										name: 'target',
										checked: ''
									}]
								}],
								buttonAlign: 'right',
								buttons: [{
									text: __('Cancel'),
									handler: function(){
										dialog.close();
									}
								}, {
									text: __('Ok'),
									ref: '../ok',
									handler: function(){
										var url = dialog.find('name', 'url')[0].getValue(), title = dialog.find('name', 'title')[0].getValue(), target = dialog.find('name', 'target')[0].getValue() == '1';
										
										var nwLink = document.createElement('a');
										
										nwLink.setAttribute('href', url);
										if (target) {
											nwLink.setAttribute('target', '_blank');
										}
										nwLink.setAttribute('title', title);
										nwLink.appendChild(document.createTextNode(txt));
										
										range.deleteContents();
										range.insertNode(nwLink);
										range.selectNodeContents(nwLink);
										sel.removeAllRanges();
										sel.addRange(range);
										
										
										dialog.close();
									},
									scope: this
								}]
							});
							dialog.show();
							dialog.items.get(0).items.get(0).clearInvalid();
							
							var map = new Ext.KeyMap([dialog.find('name', 'url')[0].getEl(), dialog.find('name', 'title')[0].getEl()], {
								key: [10, 13],
								fn: function(){
									dialog.ok.handler.call(this);
								},
								scope: this
							});
/*							
							Ext.Msg.prompt(__('Garp'), __('URL to link to'), function(btn, url){
								if (btn == 'ok' && url) {
									var nwLink = document.createElement('a');
									
									nwLink.setAttribute('href', url);
									//nwLink.setAttribute('target', '_blank');
									//nwLink.setAttribute('title', title);
									nwLink.appendChild(document.createTextNode(txt));
									
									range.deleteContents();
									range.insertNode(nwLink);
									range.selectNodeContents(nwLink);
									sel.removeAllRanges();
									sel.addRange(range);
								}
							});*/
						}
					}
					
				}
			},'->',{
				text: __('Delete'),
				iconCls: 'icon-wysiwyg-remove-chapter',
				handler: function(){
					if (this.ownerCt.items.length > 1) {
						this.ownerCt.remove(this);
					} else {
						this.ownerCt.addWysiwygCt();
						this.ownerCt.remove(this);
					}
				}
			}]
		});
	},
	
	getSelection: function(){
		var ds = (typeof document.selection !== 'undefined' ? document.selection.createRange().text : (typeof document.getSelection === 'function') ? document.getSelection() : false);
		return ds;
	},
	
	getCurrentTagName: function(){
		var node = this.getSelection().focusNode;
		if (!node) {
			return;
		}
		return node.tagName ? node.tagName : (node.parentNode.tagName ? node.parentNode.tagName : '');
	},
	
	
	setupTbarWatcher: function(){
		var states = ['bold','italic','insertorderedlist','insertunorderedlist'];
		var scope = this;
		this.tbWatcherInterval = setInterval(function(){
			var tbar = scope.getTopToolbar();
			if(!tbar || Ext.select('.wysiwyg-box').elements.length === 0) {
				return;
			}
			
			tbar.createlinkBtn.toggle(scope.getCurrentTagName() == 'A', false);

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
				
				b4Drag: function(){
					if(!this.el){
						return;
					}
				},
				
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
		});
		
		this.getEl().addClass('disabled-targets');
		if(this.extraType){
			this.getTopToolbar().extraTypesMenu.setValue(this.extraType).fireEvent('select');
		}
	},
	
	/**
	 * I.N.I.T.
	 * @param {Object} ct
	 */
	initComponent: function(){
		
		this.setupTbar();
		this.setupTbarWatcher();
		this.setupKeyboardHandling();
		
		Garp.Wysiwygct.superclass.initComponent.call(this, arguments);
		
		this.on('afterlayout', this.setupDD, this, {
			single: true
		});
		
		this.on('afterrender', function(){
			this.body.wrap({
				tag: 'div',
				cls: 'wysiwyg-wrap'
			});
		}, this);
		
		this.on('add', function(scope, comp){
			comp.on('showsettings', function(cmp, e){
				
				// @TODO: decide if this needs to go to wysiwyg box ?
				if (Garp.dataTypes[cmp.model].wysiwygConfig) {
					var items = Garp.dataTypes[cmp.model].wysiwygConfig.classMenu;
					var menuItems = [];
					Ext.each(items, function(cl){
						var item = {
							text: cl[1],
							val: cl[0]
						};
						if (cmp.el.hasClass(cl[0])) {
							item.checked = true;
						}
						menuItems.push(item);
					});
					
					var menu = new Ext.menu.Menu({
						defaults: {
							group: 'type',
							handler: function(v){
								Ext.each(items, function(cl){
									this.el.removeClass(cl[0]);
								}, this);
								this.el.addClass(v.val);
								this.type = v.val;
							},
							scope: this
						},
						items: menuItems
					});
					menu.showAt(e.getXY());
				}
			});
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
	
	extraTypes: null,
	
	addWysiwygCt: function(cfg, callerWysiwyg){
		var idx = 0;
		
		if (callerWysiwyg) {
			this.items.each(function(i,c){ // find index of caller to find inserting position
				if(i == callerWysiwyg){
					idx = c;
					return false;
				}
			});
		} 
		
		this.insert(idx + 1, new Garp.Wysiwygct({
			ct: this,
			extraTypes: this.extraTypes,
			extraType: cfg && cfg.type ? cfg.type : '',
			maxCols: this.maxCols
		}));
			
		this.doLayout();
	},
	
	/*
	bbar: new Ext.Toolbar({
		width: '100%',
		style: 'border: 0',
		items: ['->',{
			text: __('Add'),
			iconCls: 'icon-wysiwyg-add-chapter',
			handler: function(){
				this.ownerCt.ownerCt.addWysiwygCt();
			}
		}]
	}),*/
		
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