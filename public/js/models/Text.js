/** EXTENDED MODEL **/
Garp.dataTypes.Text.on('init', function(){
	
	this.iconCls = 'icon-text';
	
	// Wysiwyg Editor
	this.Wysiwyg = Ext.extend(Garp.WysiwygAbstract, {
		
		allowedTags: ['a','b','i','br','p','div','ul','ol','li'],
		
		_data: {
			description: null,
			name: null
		},
		
		/**
		 * Returns tagNames for all childs of el. N.B. Doesn't recursively do so.
		 * @param {Object} el
		 */
		getTagNames: function(el){
			var out = [];
			if (el.childNodes) {
				Array.prototype.slice.call(el.childNodes).forEach(function(el){
					if (el && el.tagName) {
						out.push(el.tagName);
					}
				});
				return out;
			}
		},
		
		/**
		 * Chrome renders '<div>' whereas Firefox renders '<p>'. Make it behave the same:
		 */
		fixParagraphs: function(){
			var el = this.contentEditableEl.dom;
			var p;
			if (!el.childNodes) {
				return;
			}
			// deep search for div's and replace those for p's
			Ext.DomQuery.jsSelect('DIV', el).reverse().forEach(function(elm){
				var inner = elm.childNodes;
				p = document.createElement('P');
				Array.prototype.slice.call(inner).forEach(function(child){
					p.appendChild(child.clone ? child.clone() : child);
				});
				elm.parentNode.replaceChild(p, elm);
			});
			// next see if we have a p as a surrounding tag. We need one:
			if (this.getTagNames(el).indexOf('P') == -1) {
				var range = document.createRange();
				p = document.createElement('P');
				if (range.selectNodeContents) {
					range.selectNodeContents(el);
				}
				range.surroundContents(p);
				range.collapse(true);
				range.detach();
			}
		},
		
		/**
		 * Cleanup this messy dom:
		 */
		filterHtml: function(){
			var scope = this;
			function walk(nodes){
				Ext.each(nodes, function(el){
					if(!el){
						return;
					}
					el.normalize();
					if (el.tagName) {
						var tag = el.tagName.toLowerCase();
						if (scope.allowedTags.indexOf(tag) == -1) {
							if (el.childNodes.length > 0) {
								while (el.childNodes.length > 0 && el.parentNode) {
									var child = el.childNodes[el.childNodes.length - 1];
									var clone = child.cloneNode(true);
									el.parentNode.insertBefore(clone, el);
									el.removeChild(child);
									el.parentNode.removeChild(el);
									walk(scope.contentEditableEl.dom.childNodes);
								}
							} else if (el.parentNode) {
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
			this.fixParagraphs();
		},
		
		getData: function(){
			if (this.contentEditableEl) {
				return {
					description: this.contentEditableEl.dom.innerHTML,
					name: this._data.name || false
				};
			} else {
				return '';
			}
		},
		
		setTitle: function(text){
			this._data.name = text;
			this.titleEl.update(text);
			this.titleEl.setDisplayed( text ? true : false);
		},
		
		showTitleDialog: function(){
			if (!this.titleEditor) {
				this.titleEditor = new Ext.Editor({
					alignment: 'tl',
					autoSize: true,
					field: {
						selectOnFocus: true,
						xtype: 'textfield',
						width: '100%',
						anchor: '99%'
					}
				});
			}
			this.titleEl.setDisplayed(true);
			this.titleEditor.startEdit(this.titleEl, this._data.name);
			this.titleEditor.on('complete', function(f, v){
				this.setTitle(v);
			}, this);
		},
		
		getMenuOptions: function(){
			return [{
				group: '',
				text: __('Add / remove title'),
				handler: this.showTitleDialog
			},{
				group :'',
				text: __('Add / remove animation classes'),
				handler: this.showAnimClassesDialog
			}];
		},
		
		initComponent: function(){

			this.html += '<div class="vertical-content"><h4 class="contenttitle"></h4>'+'<div class="contenteditable">' +
				 			__('Enter text') +
						'</div></div>'; 
		
			this.on('afterrender', function(){
				this.addClass('wysiwyg-box');
				if (this.col) {
					this.addClass(this.col);
				}
				this.el.select('.dd-handle, .target').each(function(el){
					el.dom.setAttribute(id, Ext.id());
				});

				this.contentEditableEl = this.el.select('.contenteditable').first();
				this.contentEditableEl.dom.setAttribute('contenteditable', true);
				this.contentEditableEl.removeAllListeners();
				this.contentEditableEl.on('focus', this.filterHtml, this);
				this.contentEditableEl.on('click', this.filterHtml, this);
				this.contentEditableEl.on('blur', this.filterHtml, this);
				
				this.titleEl = this.el.select('.contenttitle').first();
				this.titleEl.removeAllListeners();
				this.titleEl.on('click', this.showTitleDialog, this);
				
				
				if(this.type){
					this.el.addClass(this.type);
				}
				
				if (this._data && this._data.description) {
					this.contentEditableEl.update(this._data.description);
					this.titleEl.update(this._data.name || '');
				}
				this.titleEl.setDisplayed( (this._data && this._data.name) || false);
			}, this);
			
			Garp.dataTypes.Text.Wysiwyg.superclass.initComponent.call(this, arguments);

		}
	});
	
});