/** EXTENDED MODEL **/
Garp.dataTypes.Text.on('init', function(){
	
	
	// Wysiwyg Editor
	this.Wysiwyg = Ext.extend(Garp.WysiwygAbstract, {
		
		allowedTags: ['a','b','i','br','p','ul','ol','li'],
		
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
				
				if (this.data) {
					this.contentEditableEl.update(this.data.description);
				}
			}, this);
			
			Garp.dataTypes.Text.Wysiwyg.superclass.initComponent.call(this, arguments);

		}
	});
	
});