Ext.ns('Ext.ux.form');
/**
 * RichTextEditor extends on Ext.form.HtmlEditor
 * 
 * For IE basic RTE only
 * 
 * @author: Peter Schilleman, Engelswoord for Grrr.nl
 *  
 */

if (Ext.isIE) {

	// Lightweight variant:
	Ext.ux.form.RichTextEditor = Ext.extend(Ext.form.HtmlEditor,{
		enableAlignments: false,
		enableBlockQuote: true,
		enableColors: false,
		enableEmbed: true,
		enableFont: false,
		enableFontSize: false,
		enableFormat: true,
		enableHeading: true,
		enableLists: true,
		enableDefinitionList: true,
		enableMedia: true,
		enableLinks: true,
		enableUnderline: false,
		enableSourceEdit: true,
		defaultHeadingTag: 'h2',
		
		defaultValue: '<p>&#8203;</p>',
				
		/**
		 *
		 * @param {Object} val
		 */
		setValue: function(val){
			if (!val) {
				val = this.defaultValue;
			}
			Ext.ux.form.RichTextEditor.superclass.setValue.call(this, val);
		},
		/*
		 * Override functions:
		 */
		isDirty: function(){
			if (this.disabled || !this.rendered || !this.isVisible()) {
				return false;
			}
			return String(this.getValue()) !== String(this.originalValue);
		},	
		getDocMarkup: function(){
			return '<html><head><link rel="stylesheet" href="' + BASE + 'v' + ASSET_VERSION + '/css/garp/garp-richtexteditor.css" type="text/css"></head><body style="padding:0 !important;"></body></html>';
		},
				
		onRender: function(ct, position){
			Ext.ux.form.RichTextEditor.superclass.onRender.call(this, ct, position);
			
			this.statusbar = new Ext.Component({
				renderTo: this.wrap.dom,
				cls: 'x-toolbar garp-richtexteditor-statusbar',
				html: __('Internet Explorer mode: Not all options are available')
			});
		},
		
		/**
		 * custom 'blur' event
		 * @param {Object} e Ext event object
		 * @param {Object} t DOM target
		 */
		blur: function(e, t){
			if (!this.initialized) {
				return;
			}
			
			if (!this.hasFocus) {
				return;
			}
			
			// MIDAS still holds 'editing' state, so updateToolbar doesn't work. We'll unpress the toolbar buttons manually:
			var tbar = this.getToolbar();
			tbar.items.each(function(item){
				if (item.toggle) {
					item.toggle(false);
				}
			});
			
			// if the clicked element is within the RTE component or when there's a dialog on screen, we do nothing. 
			// Otherwise, we fire our blur event now:
			// Added: The clicked element must be within the formpanel. See [GARP] Ticket #314
			
			if (!t || (!Ext.WindowMgr.getActive()) && this.getEl().parent('.garp-formpanel') && !this.getEl().parent().contains(Ext.get(t).dom.id)) {
				this.hasFocus = false;
				this.fireEvent('blur', this);
				this.wrap.removeClass('x-focus');
				this.cleanupHtml();
				if (e && e.stopEvent) {
					e.stopEvent();
				}
			}
		},
		
		/**
		 * function initComponent
		 */
		initComponent: function(){
			this.addEvents('toolbarupdated');
			
			Ext.ux.form.RichTextEditor.superclass.initComponent.call(this);
			
			this.on('initialize', function(){
				this.execCmd('styleWithCSS', false);
				this.execCmd('insertBrOnReturn', false, false);
				this.execCmd('enableObjectResizing', false); // Doesn't work for IE
				this.updateStatusbar('');
				this.setupImageHandling();
				this.getDoc().body.style.backgroundColor = 'transparent';
			}, this);
			this.on('editmodechange', function(c, mode){
				if (mode) {
					this.addClass('garp-richtexteditor-source-edit');
				} else {
					this.removeClass('garp-richtexteditor-source-edit');
				}
			}, this);
			
		},
		
		/**
		 * unregister event handlers and such:
		 */
		destroy: function(){
			Ext.getBody().un('click', this.blur, this);
		},
		
		/**
		 * Protected method that will not generally be called directly. Pushes the value of the textarea
		 * into the iframe editor.
		 */
		pushValue: function(){
			if (this.initialized) {
				var v = this.el.dom.value;
				if (!this.activated && v.length < 1) {
					v = this.defaultValue;
				}
				// we don't want to push values into textarea if the user is editing the textarea instead of the iframe 
				if (this.sourceEditMode) {
					return;
				}
				
				/*
				 * @FIXME: Not sure why beforepush causes problems, but disabling the event test
				 *         fixes a major problem with syncing the component.   -- Peter 7-9-2010
				 */
				//if(this.fireEvent('beforepush', this, v) !== false){
				this.getEditorBody().innerHTML = v;
				
				/*
				 if(Ext.isGecko){
				 // Gecko hack, see: https://bugzilla.mozilla.org/show_bug.cgi?id=232791#c8
				 // fixed (see url)
				 // -- Peter 12-5-2011
				 this.setDesignMode(false);  //toggle off first
				 this.setDesignMode(true);
				 }*/
				this.fireEvent('push', this, v);
				//}
				Ext.EventManager.on(this.getDoc(), 'keydown', function(e){
					if (e.getKey() == e.TAB) {
						this.blur(e, false);
						return false;
					}
				}, this);
				
			}
		}
	});

// // // // // // // // // // // // // // // // // // // // // // // // // // //
// // // // // // // // // // // // // // // // // // // // // // // // // // //
// // // // // // // // // // // // // // // // // // // // // // // // // // //

} else {

	Ext.ux.form.RichTextEditor = Ext.extend(Ext.form.HtmlEditor, {
		/**
		 * custom config defaults:
		 */
		enableAlignments: false,
		enableBlockQuote: true,
		enableColors: false,
		enableEmbed: true,
		enableFont: false,
		enableFontSize: false,
		enableFormat: true,
		enableHeading: true,
		enableLists: true,
		enableDefinitionList: true,
		enableMedia: true,
		enableLinks: false, // Ext   //@TODO: refactor to resolve 'conflicting' name
		enableLink: true, // Garp  //@TODO: refactor to resolve 'conflicting' name
		enableUnderline: false,
		enableSourceEdit: true,
		defaultHeadingTag: 'h2',
		
		iframePad: 5,
		height: 500,
		showStatusbar: true,
		
		// used by 'blur' and editorEvent
		hasFocus: false,
		
		/**
		 * @cfg {String} defaultValue A default value to be put into the editor to resolve focus issues (defaults to &#160; (Non-breaking space) in Opera and IE6, &#8203; (Zero-width space) in all other browsers).
		 */
		//defaultValue: '<p style="display:none!important;height:0!important;"></p><p>&#8203;</p>', // (Ext.isOpera || Ext.isIE6) ? '<p>&#160;</p>' : '<p>&#8203;</p>',
		defaultValue: '<p>&#8203;</p>',
		//defaultValue: '',
		
		/*
		 * custom functions
		 */
		/**
		 *
		 * @param {Object} val
		 */
		setValue: function(val){
			if (!val) {
				val = this.defaultValue;
			}
			Ext.ux.form.RichTextEditor.superclass.setValue.call(this, val);
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
			var win = this.getWin();
			var doc = this.getDoc();
			
			sel = win.getSelection();
			if (sel.getRangeAt) {
				if (sel.rangeCount > 0) {
					range = sel.getRangeAt(0);
				}
			} else {
				// Old WebKit
				range = doc.createRange();
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
		
		
		/**
		 * function getSelection(void)
		 * @return current selected text as range
		 */
		getSelection: function(){
			var win = this.getWin();
			var ds = (typeof win.selection !== 'undefined' ? win.selection.createRange().text : (typeof win.getSelection === 'function') ? win.getSelection() : false);
			
			return ds;
		},
		
		/**
		 * function findNode(tagName): finds a node with a specified tag
		 *
		 * @experimental
		 *
		 * @return node or nothing
		 * @param {Object} tagName
		 */
		findNode: function(tagName){
			tagName = tagName.toUpperCase();
			var range = this.getRange();
			var out = null;
			Ext.each(['commonAncestorContainer', 'startContainer', 'endContainer'], function(container){
				var node = range[container];
				if (node.tagName && node.tagName === tagName) {
					out = node;
					return;
				}
				if (node.parentNode && node.parentNode.tagName === tagName) {
					out = node.parentNode;
					return;
				}
				if (node.previousSibling && node.previousSibling.tagName === tagName) {
					out = node.previousSibling;
					return;
				}
				if (node.children) {
					Ext.each(node.children, function(childNode){
						if (childNode.tagName && childNode.tagName === tagName) {
							out = childNode;
							return;
						}
					});
					if (out) {
						return out;
					}
				}
			});
			return out;
		},
		
		
		/**
		 * @description
		 * @param {Object} tagName
		 */
		createOrRemoveTag: function(tagName){
			var range = this.getRange();
			var sel = this.getSelection();
			var newNode;
			var search = this.findNode(tagName);
			if (!search) {
				newNode = this.getDoc().createElement(tagName);
				newNode.appendChild(range.extractContents());
				range.insertNode(newNode);
				range.selectNode(newNode);
			} else {
				range.selectNode(search);
				var text = range.toString();
				newNode = this.getDoc().createTextNode(text);
				range.deleteContents();
				range.insertNode(newNode);
				range.selectNodeContents(newNode);
			}
			sel.removeAllRanges();
			sel.addRange(range);
			this.focus.defer(20, this);
		},
		
		/**
		 * function addHeading
		 * @description add a heading tag, surrounding the current selection.
		 * @parameter type heading level
		 */
		addHeading: function(type){
			if (!type) {
				type = this.defaultHeadingTag;
			}
			type = type.toUpperCase();
			this.createOrRemoveTag(type);
		},
		
		/**
		 *
		 */
		addBlockQuote: function(){
			var sel = this.getSelection();
			var nodeList = this.filterTagsOnly(this.walk(sel.focusNode, true));
			var found = false;
			Ext.each(nodeList, function(node){
				if (node.tagName.toUpperCase() == 'BLOCKQUOTE') {
					found = true;
				}
			});
			if (found) {
				this.relayCmd('outdent');
			} else {
				this.relayCmd('formatblock', 'blockquote');
			}
		},
		
		/**
		 * function addLink
		 * @description display prompt and creates link on current selected text
		 * @return void
		 */
		addLink: function(){
			var url = null, // the href attribute for the link
 target = null, // targe might be _blank
 title = null, // title attribute
 currentNode = false; // possible existing anchor node, false to create a new 'A' tag 
			var selText = this.getSelection().toString(); // the selected Text
			// find if there's an 'a' selected, if so: get its attributes:
			var nodes = this.walk(this.getRange().endContainer, true);
			Ext.each(nodes, function(node){
				if (node.tagName == 'A') {
					url = decodeURIComponent(node.href);
					target = node.target !== '' ? true : null;
					title = node.title;
					if (!selText) {
						selText = node.textContent;
					}
					currentNode = node;
					return false; // stop searching
				}
			});
			
			// Dialog for the new link:		
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
						value: url || ''
					}, {
						xtype: 'textfield',
						fieldLabel: __('Title'),
						name: 'title',
						value: title
					}, {
						xtype: 'checkbox',
						allowBlank: true,
						fieldLabel: __('Open in new window'),
						name: 'target',
						checked: target !== null ? true : false
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
						if (url) {
							//target == '1' ? target = 'target="_blank"' : target = '';
							if (!selText) {
								selText = url;
							}
							if (currentNode) {
								currentNode.setAttribute('href', url);
								if (target) {
									currentNode.setAttribute('target', '_blank');
								} else {
									currentNode.removeAttribute('target');
								}
								currentNode.setAttribute('title', title);
							} else {
								var sel = this.getSelection();
								var range = this.getRange();
								var nwLink = this.getDoc().createElement('a');
								nwLink.setAttribute('href', url);
								if (target) {
									nwLink.setAttribute('target', '_blank');
								}
								nwLink.setAttribute('title', title);
								nwLink.appendChild(this.getDoc().createTextNode(selText));
								range.deleteContents();
								range.insertNode(nwLink);
								range.selectNodeContents(nwLink);
								sel.removeAllRanges();
								sel.addRange(range);
								//var nwLink = '<a href="' + url + '" ' + target + ' title="' + title + '">' + selText + '</a>&nbsp;';
								//this.insertAtCursor(nwLink);
							}
						}
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
			
			this.tb.items.map.createlink.toggle(false); // depress addLink button
		},
		
		/**
		 * Removes the selected element, while preserving undo (CMD-Z) functionality
		 * @param {Ext element} el
		 */
		removeSelection: function(el){
			this.getWin().focus();
			
			var sel = this.getSelection();
			var range = this.getRange();
			if (!range) {
				range = this.getDoc().createRange();
			}
			range.selectNode(el.dom);
			sel.removeAllRanges();
			sel.addRange(range);
			this.execCmd('delete');
		},
		
		/**
		 * shows the image/media Toolbar at the specified element
		 * @param {DOM Element} elm
		 */
		showMediaToolbar: function(elm){
			var el = Ext.get(elm);
			var editorEl = Ext.get(this.iframe);
			var containTop = Garp.viewport ? this.ownerCt.ownerCt.el.getTop() : 0;
			this.hideMediaToolbar();
			this.mediaToolbarLayer = new Ext.Layer({
				shadow: 'frame', // shadow at all sides
				shadowOffset: 4
			});
			var margin = 5;
			
			// Now calculate offsets. The iframe may be scrolled itself, but the containing formPanel may also be. 
			// The mediaToolbarlayer is not aware of any of that, as it is positioned absolutely on the page.
			if (elm.nodeName == 'IFRAME' || elm.nodeName == 'OBJECT') {
				this.mediaToolbarLayer.setWidth((32 * 3) + 4); // 3 items in this toolbar (no edit button)
			} else {
				this.mediaToolbarLayer.setWidth((32 * 4) + 4); // 4 items in this toolbar and some space. @TODO: refine this 'formulae' if necessary
			}
			this.mediaToolbarLayer.setHeight(28); // fixed height
			this.mediaToolbarLayer.setX(Math.max(0, el.getX()) + Math.max(0, editorEl.getBox(false, false).x) + margin);
			this.mediaToolbarLayer.setY(Math.max(containTop, Math.max(0, el.getY()) + Math.max(0, editorEl.getBox(false, false).y) + margin)); // image Top + (possibly scrolled) editor's Top
			this.mediaToolbarLayer.show();
			
			// Simple 'align buttons' handler
			function setAlign(side){
				var nwStyle = el.getStyle('float') == side ? '' : side;
				el.setStyle('float', nwStyle);
				this.mediaToolbar.left.toggle(nwStyle == 'left');
				this.mediaToolbar.right.toggle(nwStyle == 'right');
			}
			
			var tbarItems = [];
			tbarItems.push({
				iconCls: 'icon-richtext-edit-image',
				tooltip: __('Edit image'),
				scope: this,
				hidden: elm.nodeName == 'IFRAME' || elm.nodeName == 'OBJECT',
				handler: this.hideMediaToolbar.createSequence(this.editImage.createDelegate(this, [el]))
			}, {
				iconCls: 'icon-richtext-remove-image',
				tooltip: __('Remove image'),
				scope: this,
				handler: this.hideMediaToolbar.createSequence(this.removeSelection.createDelegate(this, [el]))
			}, '-', {
				iconCls: 'icon-richtext-align-left',
				tooltip: 'Align left',
				pressed: el.getStyle('float') == 'left',
				enableToggle: true,
				ref: 'left',
				//disabled: elm.nodeName == 'IFRAME' || elm.nodeName == 'OBJECT',
				handler: setAlign.createDelegate(this, ['left'])
			}, {
				iconCls: 'icon-richtext-align-right',
				tooltip: 'Align right',
				pressed: el.getStyle('float') == 'right',
				enableToggle: true,
				ref: 'right',
				//disabled: elm.nodeName == 'IFRAME' || elm.nodeName == 'OBJECT',
				handler: setAlign.createDelegate(this, ['right'])
			});
			
			// Now setup the toolbar
			this.mediaToolbar = new Ext.Toolbar({
				renderTo: this.mediaToolbarLayer,
				items: [tbarItems]
			});
			
			this.mediaToolbar.show();
			
		},
		
		/**
		 * Hides the imageToolbar
		 */
		hideMediaToolbar: function(){
			if (this.mediaToolbarLayer) {
				this.mediaToolbarLayer.remove();
			}
			if (this.mediaToolbar) {
				this.mediaToolbar.destroy();
			}
		},
		
		/**
		 * prevents the dragging of images when they have captions with them,
		 * sets clickhandler
		 */
		setupImageHandling: function(){
			var scope = this;
			
			// make images inside 
			var imgs = Ext.DomQuery.select('dl.figure img, dl.figure dd', this.getDoc().body);
			Ext.each(imgs, function(img){
				img.draggable = false;
			});
			
			// Dragging:
			this.getWin().addEventListener('mousedown', function(e){
				if (e.target.nodeName == 'IMG') {
					var repNode = e.target.parentNode.parentNode; // dl.figure ?
					if (Ext.get(repNode).hasClass('figure')) {
						var sel = scope.getSelection();
						/*
						 if (sel.rangeCount > 0) { // check to see whether the image is within a selection
						 var range = sel.getRangeAt(0);
						 range.setStartBefore(repNode);
						 range.setEndAfter(repNode);
						 sel.removeRange(range);
						 sel.addRange(range);
						 } else {*/
						sel.removeAllRanges();
						var range = document.createRange();
						range.selectNodeContents(repNode);
						range.setStartBefore(repNode);
						range.setEndAfter(repNode);
						sel.addRange(range);
						//}
					}
				}
				return true;
			}, false);
			
			
			this.getWin().addEventListener('mouseover', function(e){
				if (e && e.target) {
					var t = e.target;
					if (t.className == 'figure' || t.nodeName == 'IFRAME' || t.nodeName == 'OBJECT') { // image
						if (scope.getSelection().focusNode !== scope.getDoc().getElementsByTagName('body')[0]) { // do we have focus (selection != body) ? If not, we can't edit images. See Ticket #165
							scope.showMediaToolbar(t);
						}
					} else if (t.parentNode && t.parentNode.parentNode && t.parentNode.parentNode.className == 'figure') { // image with caption
						if (scope.getSelection().focusNode !== scope.getDoc().getElementsByTagName('body')[0]) { // do we have focus?
							scope.showMediaToolbar(t.parentNode.parentNode);
						}
					} else {
						scope.hideMediaToolbar();
					}
					return true;
				}
			}, false);
		},
		
		/**
		 * Put's the selected image in the editor
		 * Replaces old "this.execCmd('insertImage', selected.src);"
		 * @param {Object} selected
		 */
		putImage: function(selected){
			var tpl = new Ext.XTemplate(['<tpl if="caption">', '<tpl if="align">', '<dl class="figure" style="float: {align};">', '</tpl>', '<tpl if="!align">', '<dl class="figure" style="float: none;">', '</tpl>', '<dt>', '<img src="{path}" draggable="false"> ', '</dt>', '<dd draggable="false">{caption}</dd>', '</dl>', '</tpl>', '<tpl if="!caption">', '<tpl if="align">', '<img class="figure" src="{path}" style="float: {align};">', '</tpl>', '<tpl if="!align">', '<img class="figure" src="{path}" style="float: none;">', '</tpl>', '</tpl>']);
			
			// Create selection to be replace by the new image:
			var sel = this.getSelection();
			var start = sel.anchorNode;
			var startO = sel.anchorOffset;
			var end = sel.focusNode;
			var endO = sel.focusOffset;
			// Put image
			this.insertAtCursor(tpl.apply({
				path: selected.src,
				width: selected.template.get('w'),
				height: selected.template.get('h'),
				align: selected.align,
				caption: selected.caption || false
			}));
			
			// TODO: move to own special function "moveCaretToEndOfDOM" or something:
			var scope = this;
			setTimeout(function(){
				var f = Ext.select('.figure', null, scope.getDoc()).last().dom; // newly added figure
				var bElms = scope.getDoc().getElementsByTagName('body')[0].children; // body elements
				if (bElms[bElms.length - 1].isSameNode(f)) {
					//console.log('we have the last element: insert an \'empty\' paragraph, to allow user to get cursor beyond this image');
					//insertAtCursor seems to break FF
					
					var p = document.createElement('p');
					var t = document.createTextNode(' \u200B '); // Safari doesn't want to select empty stuff
					p.appendChild(t);
					scope.getDoc().getElementsByTagName('body')[0].appendChild(p);
				}
				
				setTimeout(function(){
					var sel = scope.getSelection();
					var l = scope.getDoc().body.children;
					l = l[l.length - 1];
					
					sel.removeAllRanges();//remove any selections already made
					var range = scope.getDoc().createRange();
					range.setStart(l, 0);
					range.setStartBefore(l);//Select the entire contents of the last element
					range.setEnd(l, 0);
					range.setEndAfter(l);
					range.collapse(false);//collapse the range to the end point
					sel.addRange(range);//make the range you have just created the visible selection	
					sel.selectAllChildren(l); // select it all
					sel.collapse(l, 1); // collapse selection to end				
				}, 100);
				
			}, 100);
		},
		
		/**
		 * function addImage
		 */
		addImage: function(){
			var win = new Garp.ImagePickerWindow({});
			win.on('select', this.putImage, this);
			win.show();
		},
		
		/**
		 * Replaces current image with a new one, or with different attributes (align, caption, size etc)
		 * @param {Object} current
		 */
		editImage: function(current){
			var path = current.child('img') ? current.child('img').getAttribute('src') : current.getAttribute('src');
			path = path.split('/');
			if (path[path.length - 1] === '') {
				path.splice(path.length - 1, 1); // remove last ,if it's a trailing slash
			}
			var fileId = path[path.length - 1];
			var tplName = path[path.length - 2];
			var align = current.getStyle('float');
			var caption = current.child('dd') ? current.child('dd').dom.innerHTML : null;
			var win = new Garp.ImagePickerWindow({
				imgGridQuery: {
					id: fileId
				},
				cropTemplateName: tplName,
				captionValue: caption,
				alignValue: align == 'none' ? '' : align
			});
			
			win.on('select', function(selected){
				win.close();
				this.removeSelection(current);
				this.putImage(selected);
			}, this);
			win.show();
		},
		
		/**
		 * Add a video
		 */
		addVideo: function(){
			var win = new Garp.ModelPickerWindow({
				model: 'Video',
				listeners: {
					'select': function(selected){
						var video = selected.selected;
						// We include two empty paragraphs, to make sure including is at a blocklevel  
						this.insertAtCursor('<p></p>' +
						Garp.videoTpl.apply({
							player: video.get('player'),
							width: VIDEO_WIDTH,
							height: VIDEO_HEIGHT
						}) +
						'<p></p>');
					},
					scope: this
				}
			});
			win.show();
		},
		
		/**
		 * function pastePlainText
		 */
		pastePlainText: function(){
			var textarea = new Ext.form.TextArea();
			var win = new Ext.Window({
				width: 480,
				height: 320,
				modal: true,
				layout: 'fit',
				title: __('Paste as plain text'),
				iconCls: 'icon-richtext-paste-plain-text',
				defaultButton: textarea,
				items: [textarea],
				buttons: [{
					text: __('Cancel'),
					ref: '../cancel',
					scope: this,
					handler: function(){
						win.close();
					}
				}, {
					text: __('Ok'),
					ref: '../ok',
					scope: this,
					handler: function(){
						var val = textarea.getValue();
						this.insertAtCursor(val);
						win.close();
					}
				}]
			});
			win.show();
			win.keymap = new Ext.KeyMap(win.getEl(), [{
				key: Ext.EventObject.ENTER,
				ctrl: true,
				scope: this,
				fn: function(e){
					win.ok.handler.call(this);
				}
			}, {
				key: Ext.EventObject.ESC,
				scope: this,
				fn: function(e){
					win.cancel.handler.call(this);
					e.stopEvent();
				}
			}]);
			win.keymap.stopEvent = true;
		},
		
		addEmbed: function(){
			var textarea = new Ext.form.TextArea();
			var win = new Ext.Window({
				width: 480,
				height: 320,
				modal: true,
				layout: 'fit',
				title: __('Embed HTML'),
				iconCls: 'icon-richtext-add-embed',
				defaultButton: textarea,
				items: [textarea],
				buttons: [{
					text: __('Ok'),
					ref: '../ok',
					scope: this,
					handler: function(){
						var val = textarea.getValue();
						this.insertAtCursor('<p></p>'); // makes sure this goes not in a paragraph
						this.execCmd('InsertHTML', val);
						this.insertAtCursor('<p></p>');
						win.close();
					}
				}, {
					text: __('Cancel'),
					ref: '../cancel',
					scope: this,
					handler: function(){
						win.close();
					}
				}]
			});
			win.show();
			win.keymap = new Ext.KeyMap(win.getEl(), [{
				key: Ext.EventObject.ENTER,
				ctrl: true,
				scope: this,
				fn: function(e){
					win.ok.handler.call(this);
				}
			}, {
				key: Ext.EventObject.ESC,
				scope: this,
				fn: function(e){
					win.cancel.handler.call(this);
					e.stopEvent();
				}
			}]);
			win.keymap.stopEvent = true;
		},
		
		/**
		 * function walk. Walks the DOM
		 * @param {Object} node (the node to walk from)
		 * @param {Object} dir (true, move up, false move down)
		 * @param {Object} list (private)
		 */
		walk: function(node, dir, list){
			if (!list) {
				list = [];
			}
			if (!dir === false) {
				dir = true;
			}
			if (dir) {
				list.push(node);
				if (node && node.parentNode && node.nodeName !== 'HTML') { // move up 
					node = node.parentNode;
					this.walk(node, dir, list);
				}
			} else {
				list.push(node);
				if (node && node.childNodes && node.childNodes[0]) { // move down 
					node = node.childNodes[0];
					this.walk(node, dir, list);
				}
			}
			return list;
		},
		
		/**
		 * filters out things that are not tags
		 * @param {Object} arr
		 */
		filterTagsOnly: function(arr){
			var out = [];
			for (var c = 0; c < arr.length; c++) {
				var item = arr[c];
				if (item.tagName) {
					out.push(item);
				}
			}
			return out;
		},
		
		/**
		 * @return the current tag Name, or, if current selection is a textnode, it gives its direct parent's tag Name
		 */
		getCurrentTagName: function(){
			var node = this.getSelection().focusNode;
			if (!node) {
				return;
			}
			node = node.tagName ? node.tagName : (node.parentNode.tagName ? node.parentNode.tagName : '');
			return node.toLowerCase();
		},
		
		/**
		 * @return the current tag's classList or, if current selection is a textnode, it gives its direct parent's classList
		 */
		getCurrentClassList: function(){
			var node = this.getSelection().focusNode;
			if (!node) {
				return;
			}
			var list = node.className ? node.className : (node.parentNode.className ? node.parentNode.className : '');
			return list.toLowerCase();
		},
		
		
		
		/**
		 * function buildStatusbar
		 * builds a 'statusbar' like element (bbar)
		 */
		buildStatusbar: function(){
			this.statusbar = new Ext.Component({
				renderTo: this.wrap.dom,
				cls: 'x-toolbar garp-richtexteditor-statusbar',
				html: '&nbsp;'
			});
			var highlightElm = null;
			this.statusbar.el.on('mouseover', function(e, t){
				var index = Number(t.className.substr(1, t.className.length));
				if (!isNaN(index)) {
					var sel = this.getSelection();
					var htmlPath = this.filterTagsOnly(this.walk(sel.focusNode, true).reverse());
					highlightElm = htmlPath[index];
					if (highlightElm) {
						Ext.get(highlightElm).addClass('garp-richtexteditor-highlight');
					}
				}
			}, this);
			this.statusbar.el.on('mouseout', function(e, t){
				if (highlightElm) {
					Ext.get(highlightElm).removeClass('garp-richtexteditor-highlight');
				}
			}, this);
		},
		
		/**
		 * function updateStatusbar: displays a message, or the dom tree
		 * @param {Object} str
		 */
		updateStatusbar: function(str){
			if (!this.statusbar) {
				return;
			}
			
			if (str !== '') {
				var sel = this.getSelection();
				if (!sel) {
					return;
				}
				var htmlPath = this.walk(sel.focusNode, true).reverse();
				str = '';
				for (var c = 1, len = htmlPath.length; c < len; c++) { // skip first ( HTML)
					var elm = htmlPath[c];
					if (elm.tagName) {
						str += '<span class="_' + c + '">' + elm.tagName + '</span> &gt; ';
					} else {
						str += '<span class="_' + c + '">"' + Ext.util.Format.ellipsis(elm.nodeValue, 20, true) + '"</span>';
					}
				}
			} else {
				str = '&nbsp;';
			}
			this.statusbar.update(str);
		},
		
		/*
		 * Override functions:
		 */
		isDirty: function(){
			if (this.disabled || !this.rendered || !this.isVisible()) {
				return false;
			}
			return String(this.getValue()) !== String(this.originalValue);
		},
		
		
		/**
		 * Override getDocMarkup for stylesheet inclusion
		 * Protected method that will not generally be called directly. It
		 * is called when the editor initializes the iframe with HTML contents. Override this method if you
		 * want to change the initialization markup of the iframe (e.g. to add stylesheets).
		 *
		 * Note: IE8-Standards has unwanted scroller behavior, so the default meta tag forces IE7 compatibility
		 */
		getDocMarkup: function(){
			return '<html><head><link rel="stylesheet" href="' + BASE + 'v' + ASSET_VERSION + '/css/garp/garp-richtexteditor.css" type="text/css"></head><body></body></html>';
		},
		
		// private
		/**
		 * ... but extended
		 */
		onEditorEvent: function(e){
			this.execCmd('styleWithCSS', false); // seems to get "forgotten" not sure why. @TODO @FIXME
			this.hasFocus = true;
			this.wrap.addClass('x-focus');
			var sel = this.getSelection();
			if (!sel) {
				return;
			}
			var body = this.getDoc().body;
			var htmlPath = this.filterTagsOnly(this.walk(body, false));
			
			// check to see if there's at least one paragraph:
			var count = false;
			for (var i in htmlPath) {
				var t = htmlPath[i];
				if (t.tagName && (t.tagName == 'p ' || t.tagName == 'P')) {
					count = true;
					break;
				}
			}
			
			// otherwise: insert a paragraph:
			if ((sel.focusNode && sel.focusNode.nodeName && sel.focusNode.nodeName == 'DIV') || !count) {
				this.relayCmd('formatblock', 'p');
			}
			
			this.updateToolbar();
		},
		
		addDefinitionList: function(){
			if(Ext.isChrome || Ext.isWebkit){
				throw "Unfortunately, your browser does not support this feature yet.";
			}
			
			var t = this.getCurrentTagName().toLowerCase();
			switch (t) {
				case 'dl':
					this.insertAtCursor('</dl><p>');
					break;
				case 'dt':
					this.insertAtCursor('</dt></dl><p>');
					break;
				case 'dd':
					this.insertAtCursor('</dd><dt>');
					break;
				default:
					var sel = this.getSelection();
					var id = Ext.id();
					var txt = sel.toString() || '&hellip;';
					var range = this.getRange();
					range.deleteContents();
					this.insertAtCursor('<dl><dt id="' + id + '">' + txt);
					var elm = this.getDoc().getElementById(id);
					range.selectNodeContents(elm);
					sel.removeAllRanges();
					sel.addRange(range);
					break;
			}
			this.deferFocus();
			this.updateToolbar.defer(100, this);
		},
		
		
		/**
		 * function updateToolbar()
		 * Protected method that will not generally be called directly. It triggers
		 * a toolbar update by reading the markup state of the current selection in the editor.
		 *
		 * Overriden for heading and link
		 */
		updateToolbar: function(){
		
			if (this.readOnly) {
				return;
			}
			
			if (!this.activated && this.onFirstFocus) {
				this.onFirstFocus();
				return;
			}
			
			var btns = this.tb.items.map, doc = this.getDoc();
			
			if (this.enableFont && !Ext.isSafari2) {
				var name = (doc.queryCommandValue('FontName') || this.defaultFont).toLowerCase();
				if (name != this.fontSelect.dom.value) {
					this.fontSelect.dom.value = name;
				}
			}
			if (this.enableFormat) {
				btns.bold.toggle(doc.queryCommandState('bold'));
				btns.italic.toggle(doc.queryCommandState('italic'));
				if (this.enableUnderline) {
					btns.underline.toggle(doc.queryCommandState('underline'));
				}
			}
			if (this.enableAlignments) {
				btns.justifyleft.toggle(doc.queryCommandState('justifyleft'));
				btns.justifycenter.toggle(doc.queryCommandState('justifycenter'));
				btns.justifyright.toggle(doc.queryCommandState('justifyright'));
			}
			if (!Ext.isSafari2 && this.enableLists) {
				btns.insertorderedlist.toggle(doc.queryCommandState('insertorderedlist'));
				btns.insertunorderedlist.toggle(doc.queryCommandState('insertunorderedlist'));
			}
			
			var format = doc.queryCommandValue('formatblock');
			var h = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
			if (format && h.indexOf(format.toLowerCase()) > -1) {
				btns.addheading.toggle(true);
			} else {
				btns.addheading.toggle(false);
			}
			
			var t = this.getCurrentTagName();
			var l = this.getCurrentClassList();
			btns.createlink.toggle(t == 'a');
			if (l.indexOf('figure') == -1) {
				btns.definitionlist.toggle(t == 'dl' || t == 'dt' || t == 'dd');
			}
			Ext.menu.MenuMgr.hideAll();
			
			if (this.statusbar) {
				this.updateStatusbar();
			}
			
			this.syncValue();
			this.fireEvent('toolbarupdated', this);
		},
		
		/**
		 * function onRender
		 * @param {Object} ct
		 * @param {Object} position
		 */
		onRender: function(ct, position){
			Ext.ux.form.RichTextEditor.superclass.onRender.call(this, ct, position);
			if (this.showStatusbar) {
				this.buildStatusbar();
			}
			// Sometimes, current state overrules clicks on tb buttons. We defer the newly desired state, as to fix the UI: 
			Ext.each(['bold', 'italic', 'underline', 'insertorderedlist', 'insertunorderedlist', 'addheading', 'justifyleft', 'justifycenter', 'justifyright'], function(btn){
				if (this.tb.items.map[btn]) {
					this.tb.items.map[btn].on('click', function(e){
						if (this.pressed) {
							this.toggle.defer(100, this, [true]);
						}
					});
				}
			}, this);
		},
		
		/**
		 * afterRender
		 */
		afterRender: function(){
			Ext.ux.form.RichTextEditor.superclass.afterRender.call(this);
			var tbar = this.getToolbar();
			
			if (!this.enableUnderline) {
				var u = tbar.find('itemId', 'underline')[0];
				tbar.remove(u);
			}
			if (this.enableLists && this.enableUnderline) {
				tbar.insert(3, '-');
			}
			
			tbar.insert(3, {
				tooltip: __('<b>Add Blockquote</b><br>Convert selected text into a blockquote.'),
				iconCls: 'icon-richtext-add-blockquote',
				hidden: !this.enableBlockQuote,
				itemId: 'addblockquote',
				handler: this.addBlockQuote.createDelegate(this),
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(4, {
				tooltip: __('<b>Add Heading</b><br>Convert selected text into a heading.'),
				iconCls: 'icon-richtext-add-heading',
				enableToggle: true,
				hidden: !this.enableHeading,
				itemId: 'addheading',
				handler: this.addHeading.createDelegate(this, [this.defaultHeadingTag]),
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(8, {
				tooltip: __('<b>Add Glossary</b><br>Creates a list of terms.'),
				iconCls: 'icon-richtext-add-dl',
				enableToggle: true,
				itemId: 'definitionlist',
				hidden: !this.enableDefinitionList,
				handler: this.addDefinitionList, // this.createLink
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(9, {
				tooltip: __('<b>Add Link</b><br>Convert selected text into a hyperlink.'),
				iconCls: 'icon-richtext-add-link',
				enableToggle: true,
				itemId: 'createlink',
				hidden: !this.enableLink,
				handler: this.addLink, // this.createLink
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(10, {
				tooltip: __('<b>Image</b><br>Insert image.'),
				iconCls: 'icon-richtext-add-image',
				itemId: 'addImage',
				hidden: !this.enableMedia,
				handler: this.addImage,
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(11, {
				tooltip: __('<b>Video</b><br>Insert a video.'),
				iconCls: 'icon-richtext-add-video',
				itemId: 'addVideo',
				hidden: !this.enableMedia,
				handler: this.addVideo,
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(12, '-');
			
			tbar.insert(13, {
				tooltip: __('<b>Paste as plain text</b><br>Removes styling.'),
				iconCls: 'icon-richtext-paste-plain-text',
				enableToggle: false,
				itemId: 'pastePlainText',
				handler: this.pastePlainText,
				tabIndex: -1,
				scope: this
			});
			
			tbar.insert(14, {
				tooltip: __('<b>Add Embed</b>'),
				iconCls: 'icon-richtext-add-embed',
				enableToggle: false,
				itemId: 'addEmbed',
				hidden: !this.enableEmbed,
				handler: this.addEmbed,
				tabIndex: -1,
				scope: this
			});
			
		},
		
		
		/**
		 * function onResize
		 *
		 * override because of possible statusbar
		 */
		onResize: function(w, h){
			Ext.form.HtmlEditor.superclass.onResize.apply(this, arguments);
			if (this.el && this.iframe) {
				if (Ext.isNumber(w)) {
					var aw = w - this.wrap.getFrameWidth('lr');
					this.el.setWidth(aw);
					this.tb.setWidth(aw);
					this.iframe.style.width = Math.max(aw, 0) + 'px';
				}
				if (Ext.isNumber(h)) {
					var ah = h - this.wrap.getFrameWidth('tb') - this.tb.el.getHeight() - (this.statusbar ? this.statusbar.el.getHeight() : 0);
					this.el.setHeight(ah);
					this.iframe.style.height = Math.max(ah, 0) + 'px';
					var bd = this.getEditorBody();
					if (bd) {
						bd.style.height = Math.max((ah - (this.iframePad * 2)), 0) + 'px';
					}
				}
			}
		},
		
		/**
		 * Unwraps a tag within a domFragment
		 * @param {Object} tagName
		 * @param {Object} fragment
		 */
		unwrap: function(tagName, fragment){
			while (Ext.DomQuery.select(tagName, fragment).length > 0) {
				var elm = Ext.DomQuery.selectNode(tagName, fragment);
				elm.normalize();
				while (elm.childNodes.length > 0) {
					var child = elm.childNodes[elm.childNodes.length - 1];
					var clone = child.cloneNode(true);
					elm.parentNode.insertBefore(clone, elm);
					elm.removeChild(child);
				}
				elm.parentNode.removeChild(elm);
			}
		},
		
		/**
		 * replaces Tags with something else or removes them if replaceWith is false
		 * @param {Object} tagName
		 * @param {Object} replaceWith
		 * @param {Object} fragment
		 */
		replaceTag: function(tagName, replaceWith, fragment){
			var search = Ext.DomQuery.select(tagName, fragment);
			Ext.DomHelper.useDom = false;
			Ext.each(search, function(s){
				var elm = Ext.get(s);
				if (replaceWith) {
					var i = elm.dom.innerHTML;
					Ext.DomHelper.insertBefore(s, {
						tag: replaceWith,
						html: i
					});
				}
				elm.remove();
			});
		},
		
		/**
		 * wrap a fragment
		 * @param {Object} tagName
		 * @param {Object} fragment
		 */
		wrapFragment: function(tagName, fragment){
			var wrapper = fragment.ownerDocument.createElement(tagName);
			var inner = fragment.cloneNode(true);
			wrapper.appendChild(inner);
			fragment.parentNode.insertBefore(wrapper, fragment);
			fragment.parentNode.removeChild(fragment);
		},
		
		/**
		 * makes sure inline elements are wrapped in a <p>
		 * @param {Object} fragment
		 */
		fixInlineElements: function(fragment){
			// inline elements need to get wrapped in a 'p' 
			var inlineElms = ['#text', 'B', 'BIG', 'I', 'SMALL', 'TT', 'ABBR', 'ACRONYM', 'CITE', 'CODE', 'DFN', 'KBD', 'STRONG', 'SAMP', 'VAR', 'A', 'BDO', 'IMG', 'MAP', 'OBJECT', 'Q', 'SCRIPT', 'SPAN', 'SUB', 'SUP', 'BUTTON', 'INPUT', 'LABEL', 'SELECT', 'TEXTAREA'];
			
			var firstChild = false;
			var lastChilds = false;
			Ext.each(fragment.childNodes, function(child){
				if (inlineElms.indexOf(child.nodeName) > -1) {
					if (!firstChild) {
						firstChild = child;
					}
					lastChild = child;
				}
			}, this);
			if (firstChild) {
				var wrapper = fragment.ownerDocument.createElement('p');
				if (firstChild != lastChild) { // create a range if the wrapper should span more than one node
					var range = fragment.ownerDocument.createRange();
					range.setStart(firstChild, 0);
					range.setEnd(lastChild, lastChild.length);
					wrapper.appendChild(range.extractContents());
				} else { // otherwise, wrap just a clone of the original node
					wrapper.appendChild(firstChild.cloneNode(true));
				}
				firstChild.parentNode.replaceChild(wrapper, firstChild);
			}
			fragment.normalize();
		},
		
		fixBrs: function(fragment){
			var elms = Ext.each(Ext.DomQuery.select('br', fragment), function(elm){
				var el = Ext.get(elm);
				//console.log(' ');
				//console.info(elm);
				
				if (el.parent('p') && el.parent('p').dom.childNodes[0] == elm) {
					//console.log('first element in p is br');
					el.remove();
					return;
				}
				if (el.dom.nextSibling && el.dom.nextSibling.nodeName == 'BR') {
					//console.log('next element is a br');
					el.remove();
					return;
				}
			});
		},
		
		/**
		 * cleans the body from unwanted br's and stuff
		 */
		cleanupHtml: function(){
		
			this.suspendEvents();
			this.setDesignMode(false); //toggle off first
			var doc = this.getDoc();
			var body = doc.body;
			
			// change 'empty char. entities' into spaces:
			var entities = ['&nbsp;', '&#8203;'];
			var html = this.getValue();
			if (!html) {
				return true;
			}
			Ext.each(entities, function(entity){
				html = html.replace(entity, ' ');
			});
			this.setValue(html);
			
			// remove spans
			this.unwrap('span', body);
			this.unwrap('div', body);
			
			// replace strong into b & em into i:
			this.replaceTag('strong', 'b', body);
			this.replaceTag('em', 'i', body);
			
			// make sure every text node is within a paragraph
			body.normalize();
			this.fixInlineElements(body);
			this.fixBrs(body);
			
			// remove empty p's:
			Ext.get(Ext.DomQuery.select('p:empty', body)).remove();
			Ext.each(Ext.DomQuery.select('p', body), function(elm){
				if (entities.indexOf(elm.innerHTML) > -1) {
					Ext.get(elm).remove();
				}
			});
			
			// remove ID attributes
			var elms = Ext.DomQuery.jsSelect('[id]', body);
			Ext.each(elms, function(elm){
				elm.removeAttribute('id');
			}, this);
			
			this.setDesignMode(true);
			this.resumeEvents();
			
			return true;
		},
		
		/**
		 * cleans the body from unwanted br's and stuff
		 * @deprecated
		 */
		cleanupHtmlOld: function(){
			this.suspendEvents();
			var doc = this.getDoc();
			var body = doc.body;
			
			// unwrap span's, because WE DON'T WANT span's
			while (Ext.DomQuery.select('span', body).length > 0) {
				var elm = Ext.DomQuery.selectNode('span', body);
				elm.normalize();
				while (elm.childNodes.length > 0) {
					var child = elm.childNodes[0];
					var clone = child.cloneNode(true);
					elm.parentNode.insertBefore(clone, elm);
					elm.removeChild(child);
				}
				elm.parentNode.removeChild(elm);
			}
			body.normalize();
			// remove possible lonely BR
			if (body.children.length == 1 && body.children[0].tagName == 'BR') {
				body.removeChild(body.children[0]);
			}
			
			// change 'empty char. entities' into spaces:
			var entities = ['&nbsp;', '&#8203;'];
			var html = this.getValue();
			if (!html) {
				return true;
			}
			Ext.each(entities, function(entity){
				html = html.replace(entity, ' ');
			});
			
			this.setValue(html);
			
			// make sure every textNode is inside a paragraph.
			// this only checks for *direct children* of body.
			Ext.each(body.childNodes, function(elm){
				if (elm.nodeName == '#text' && elm.nodeValue) {
					var p = doc.createElement('p');
					var t = doc.createTextNode(elm.nodeValue);
					p.appendChild(t);
					body.insertBefore(p, elm);
					body.removeChild(elm);
				}
			});
			
			var elms = Ext.each(Ext.DomQuery.select('br', body), function(elm){
				var el = Ext.get(elm);
				//console.log(' ');
				//console.info(elm);
				if (!el.parent('p')) {
					//console.log('parent not p');
					//el.remove();
					return;
				}
				if (el.parent('p').dom.childNodes.length == 1) {
					//console.log('parent only has br');
					el.remove();
					return;
				}
				if (el.parent('p').dom.childNodes[0] == elm) {
					//console.log('first element in p is br');
					el.remove();
					return;
				}
				if (el.dom.nextSibling && el.dom.nextSibling.nodeName == 'BR') {
					//console.log('next element is a br');
					el.remove();
					return;
				}
			});
			
			// remove empty p's:
			Ext.get(Ext.DomQuery.select('p:empty', body)).remove();
			Ext.each(Ext.DomQuery.select('p', body), function(elm){
				if (entities.indexOf(elm.innerHTML) > -1) {
					Ext.get(elm).remove();
				}
			});
			
			// change strong's into b's:
			var strongs = Ext.DomQuery.select('strong', body);
			Ext.DomHelper.useDom = false;
			Ext.each(strongs, function(s){
				var elm = Ext.get(s);
				var i = elm.dom.innerHTML;
				Ext.DomHelper.insertBefore(s, {
					tag: 'b',
					html: i
				});
				elm.remove();
			});
			
			// change em's into i's:
			strongs = Ext.DomQuery.select('em', body);
			Ext.DomHelper.useDom = false;
			Ext.each(strongs, function(s){
				var elm = Ext.get(s);
				var i = elm.dom.innerHTML;
				Ext.DomHelper.insertBefore(s, {
					tag: 'i',
					html: i
				});
				elm.remove();
			});
			
			this.resumeEvents();
			
			return true;
		},
		
		/**
		 * custom 'blur' event
		 * @param {Object} e Ext event object
		 * @param {Object} t DOM target
		 */
		blur: function(e, t){
			if (!this.initialized) {
				return;
			}
			this.hideMediaToolbar();
			
			if (!this.hasFocus) {
				return;
			}
			
			// MIDAS still holds 'editing' state, so updateToolbar doesn't work. We'll unpress the toolbar buttons manually:
			var tbar = this.getToolbar();
			tbar.items.each(function(item){
				if (item.toggle) {
					item.toggle(false);
				}
			});
			
			// if the clicked element is within the RTE component or when there's a dialog on screen, we do nothing. 
			// Otherwise, we fire our blur event now:
			// Added: The clicked element must be within the formpanel. See [GARP] Ticket #314
			
			if (!t || (!Ext.WindowMgr.getActive()) && this.getEl().parent('.garp-formpanel') && !this.getEl().parent().contains(Ext.get(t).dom.id)) {
				this.hasFocus = false;
				this.fireEvent('blur', this);
				this.wrap.removeClass('x-focus');
				this.cleanupHtml();
				if (e && e.stopEvent) {
					e.stopEvent();
				}
			}
		},
		
		/**
		 * function initComponent
		 */
		initComponent: function(){
			this.addEvents('toolbarupdated');
			
			Ext.ux.form.RichTextEditor.superclass.initComponent.call(this);
			
			this.on('initialize', function(){
				this.execCmd('styleWithCSS', false);
				this.execCmd('insertBrOnReturn', false, false);
				this.execCmd('enableObjectResizing', false); // Doesn't work for IE
				this.updateStatusbar('');
				this.setupImageHandling();
				this.getDoc().body.style.backgroundColor = 'transparent';
			}, this);
			this.on('editmodechange', function(c, mode){
				if (mode) {
					this.addClass('garp-richtexteditor-source-edit');
				} else {
					this.removeClass('garp-richtexteditor-source-edit');
				}
			}, this);
			this.on('blur', function(){
				this.cleanupHtml();
				this.hideMediaToolbar();
				this.updateStatusbar('');
			}, this);
			Ext.getBody().on('click', this.blur, this);
			
			this.on('push', function(){
				this.updateStatusbar('');
				//this.cleanupHtml();
			}, this);
			
		},
		
		/**
		 * unregister event handlers and such:
		 */
		destroy: function(){
			Ext.getBody().un('click', this.blur, this);
		},
		
		/**
		 * Protected method that will not generally be called directly. Pushes the value of the textarea
		 * into the iframe editor.
		 */
		pushValue: function(){
			if (this.initialized) {
				var v = this.el.dom.value;
				if (!this.activated && v.length < 1) {
					v = this.defaultValue;
				}
				// we don't want to push values into textarea if the user is editing the textarea instead of the iframe 
				if (this.sourceEditMode) {
					return;
				}
				
				/*
				 * @FIXME: Not sure why beforepush causes problems, but disabling the event test
				 *         fixes a major problem with syncing the component.   -- Peter 7-9-2010
				 */
				//if(this.fireEvent('beforepush', this, v) !== false){
				this.getEditorBody().innerHTML = v;
				
				/*
				 if(Ext.isGecko){
				 // Gecko hack, see: https://bugzilla.mozilla.org/show_bug.cgi?id=232791#c8
				 // fixed (see url)
				 // -- Peter 12-5-2011
				 this.setDesignMode(false);  //toggle off first
				 this.setDesignMode(true);
				 }*/
				this.fireEvent('push', this, v);
				//}
				Ext.EventManager.on(this.getDoc(), 'keydown', function(e){
					if (e.getKey() == e.TAB) {
						this.blur(e, false);
						return false;
					}
				}, this);
				var k = new Ext.KeyMap(this.getDoc().body, {
					ctrl: true,
					key: Ext.EventObject.ENTER,
					fn: function(e){
						var parentForm = this.findParentByType('garpformpanel');
						if(!parentForm){
							parentForm = this.findParentByType('formpanel');
						}
						if (parentForm) {
							this.blur(e, false);
							this.syncValue();
							this.cleanupHtml();
							parentForm.fireEvent('save-all');
						}
					},
					scope: this
				});
				k.enable();
			}
		},
		
		fixKeys: function(){ // load time branching for fastest keydown performance
			//if (true || Ext.isWebkit) {
			return function(e){
				if (e.ctrlKey) {
					var c = e.getCharCode(), cmd;
					if (c > 0) {
						c = String.fromCharCode(c);
						switch (c.toLowerCase()) {
							case 'b':
								cmd = 'bold';
								break;
							case 'i':
								cmd = 'italic';
								break;
							case 'u':
								cmd = 'underline';
								break;
						}
						if (cmd) {
							this.win.focus();
							this.execCmd(cmd);
							this.deferFocus();
							e.preventDefault();
						}
					}
				}
			};
			//}
		
			// to override standard Ext.form.HtmlEditor behavior: 
			//return function(e){
			//};
		
			/*
			 if(Ext.isIE){
			 return function(e){
			 var k = e.getKey(),
			 doc = this.getDoc(),
			 r;
			 if(k == e.TAB){
			 e.stopEvent();
			 r = doc.selection.createRange();
			 if(r){
			 r.collapse(true);
			 r.pasteHTML('&nbsp;&nbsp;&nbsp;&nbsp;');
			 this.deferFocus();
			 }
			 }else if(k == e.ENTER){
			 r = doc.selection.createRange();
			 if(r){
			 var target = r.parentElement();
			 if(!target || target.tagName.toLowerCase() != 'li'){
			 e.stopEvent();
			 r.pasteHTML('<br />');
			 r.collapse(false);
			 r.select();
			 }
			 }
			 }
			 };
			 }else if(Ext.isOpera){
			 return function(e){
			 var k = e.getKey();
			 if(k == e.TAB){
			 e.stopEvent();
			 this.win.focus();
			 this.execCmd('InsertHTML','&nbsp;&nbsp;&nbsp;&nbsp;');
			 this.deferFocus();
			 }
			 };
			 }else if(Ext.isWebKit){
			 return function(e){
			 var k = e.getKey();
			 if(k == e.TAB){
			 e.stopEvent();
			 this.execCmd('InsertText','\t');
			 this.deferFocus();
			 }else if(k == e.ENTER){
			 e.stopEvent();
			 this.execCmd('InsertHtml','<br /><br />');
			 this.deferFocus();
			 }
			 };
			 }*/
		}()
	});
	
	/**
	 * Plugin for ext.ux.form.richTextEditor
	 */
	Garp.CodeEditor = function(){
	
		this.updateBtns = function(){
			var btns = this.getToolbar().items.map;
			btns.pre.toggle(this.getDoc().queryCommandValue('formatblock') == 'pre');
			
			var tag = this.getCurrentTagName();
			btns.code.toggle(tag === 'code');
			btns['var'].toggle(tag === 'var');
		};
		
		this.afterrender = function(){
		
			this.addVar = function(){
				if (this.getCurrentTagName() == 'var') {
					this.relayCmd('removeformat');
				} else {
					var selText = this.getSelection().toString(); // the selected Text
					if (!selText) {
						selText = '{var}';
					}
					this.insertAtCursor('<var>' + selText + '</var>');
				}
			};
			
			this.addCode = function(){
				if (this.getCurrentTagName() == 'code') {
					this.relayCmd('removeformat');
				} else {
					var selText = this.getSelection().toString(); // the selected Text
					if (!selText) {
						selText = '{code}';
					}
					this.insertAtCursor('<code>' + selText + '</code>');
				}
			};
			
			this.addPre = function(){
				if (this.getDoc().queryCommandValue('formatblock') == 'pre') {
					this.relayCmd('formatblock', 'p');
				} else {
					this.relayCmd('formatblock', 'pre');
				}
			};
			
			var tb = this.getToolbar();
			
			tb.add('-');
			tb.insert(5, {
				iconCls: 'icon-richtext-add-heading-menu',
				tabIndex: -1,
				menu: new Ext.menu.Menu({
					defaults: {
						handler: function(item){
							this.addHeading(item.text);
						},
						scope: this
					},
					items: [{
						text: 'H1'
					}, {
						text: 'H2'
					}, {
						text: 'H3'
					}, {
						text: 'H4'
					}, {
						text: 'H5'
					}, {
						text: 'H6'
					}]
				})
			});
			
			tb.add({
				tooltip: __('Add &lt;var&gt;'),
				tabIndex: -1,
				handler: this.addVar,
				scope: this,
				itemId: 'var',
				enableToggle: true,
				iconCls: 'icon-richtext-add-var'
			});
			
			tb.add({
				tooltip: __('Add &lt;code&gt;'),
				tabIndex: -1,
				handler: this.addCode,
				scope: this,
				itemId: 'code',
				enableToggle: true,
				iconCls: 'icon-richtext-add-code'
			});
			
			tb.add({
				tooltip: __('Add &lt;pre&gt;'),
				tabIndex: -1,
				handler: this.addPre,
				scope: this,
				itemId: 'pre',
				iconCls: 'icon-richtext-add-pre',
				enableToggle: true
			});
			
			
			var old = tb.get('addheading');
			old.hide();
		};
		
		this.init = function(field){
			field.on('afterrender', this.afterrender);
			field.on('toolbarupdated', this.updateBtns);
		};
	};
}

Ext.reg('richtexteditor', Ext.ux.form.RichTextEditor);