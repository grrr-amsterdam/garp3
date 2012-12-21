/** EXTENDED MODEL **/
Garp.dataTypes.Text.on('init', function(){
	
	this.iconCls = 'icon-text';
	
	this.extraTypes = [['',__('Normal')],['aside',__('Aside')],['attention',__('Attention')]];
	
	// Wysiwyg Editor
	this.Wysiwyg = Ext.extend(Garp.WysiwygAbstract, {
		
		allowedTags: ['a','b','i','br','p','ul','ol','li'],
		
		filterHtml: function(){
			var scope = this;
			function walk(nodes){
				Ext.each(nodes, function(el){
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
		},
		
		data: null,
		
		getData: function(){
			if (this.contentEditableEl) {
				return {
					description: this.contentEditableEl.dom.innerHTML
				};
			} else {
				return '';
			}
		},
		
		initComponent: function(){

			this.html += '<div class="contenteditable">' +
				 			__('Enter text') +
						'</div>'; 
		

			this.on('user-resize', function(w, nw, nwCol){
				this.col = nwCol;
			}, this);
			
			this.on('afterrender', function(){
				this.addClass('wysiwyg-box');
				if (this.col) {
					this.addClass(this.col);
				}
				this.el.select('.dd-handle, .target').each(function(el){
					el.dom.setAttribute(id, Ext.id());
				});

				this.contentEditableEl = this.el.child('.contenteditable');
				this.contentEditableEl.dom.setAttribute('contenteditable', true);
				this.contentEditableEl.on('focus', this.filterHtml, this);
				this.contentEditableEl.on('click', this.filterHtml, this);
				this.contentEditableEl.on('blur', this.filterHtml, this);
				
				if (this.data) {
					this.contentEditableEl.update(this.data.description);
				}
			}, this);
			
			Garp.dataTypes.Text.Wysiwyg.superclass.initComponent.call(this, arguments);

		}
	});
	
});