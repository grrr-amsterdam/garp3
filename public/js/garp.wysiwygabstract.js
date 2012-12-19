/**
 * Wysiwyg Abstract Class
 */
Garp.WysiwygAbstract = Ext.extend(Ext.BoxComponent, {
	
	/**
	 * Reference to wysiwygct 
	 */
	ct: null,
	
	/**
	 * Reference to Garp.dataType
	 */
	model: 'Text',
	
	/**
	 * Retrieve contents
	 */
	getValue: function(){
		if (this.getData()) {
			return {
				columns: this.col ? this.col.split('-')[1] : null,
				data: this.getData(),
				model: this.model,
				type: ''
			};
		}
		return null;
	},
	
	/**
	 * innerHTML
	 */
	html: 
		'<div class="dd-handle icon-move"></div>' + 
		'<div class="dd-handle icon-delete"></div>' +
		'<div class="dd-handle icon-settings"></div>' + 
		'<div class="target top"></div>' +
		'<div class="target right"></div>' +
		'<div class="target bottom"></div>' + 
		'<div class="target left"></div>',
	
	/**
	 * Shortcut reference, wil get set on init
	 */	
	contentEditableEl: null,
	
	/**
	 * Default Col class
	 */
	col: null,
	
	/**
	 * Get innerHtml data
	 */
	getData: function(){
		return {
			description: this.contentEditable ? this.contentEditableEl.dom.innerHTML : ''
		};
	},

	/**
	 * Overridable
	 * @param {Object} component (this)
	 * @param {Object} evt
	 */
	showSettingsMenu: function(cmp, e){
		this.fireEvent('showsettings', cmp, e);
	},

	/**
	 * 
	 * @param {Object} ct
	 */
	afterRender: function(ct){
		Garp.WysiwygAbstract.superclass.afterRender.call(this, arguments);
		this.el.select('.dd-handle.icon-delete').on('click', function(){
			this.ownerCt.removeWysiwygBox(this);
		}, this);
		this.el.select('.dd-handle.icon-settings').on('click', function(cmp, e){
			this.showSettingsMenu(this, e);
		}, this);
	},
	
	/**
	 * Override beforeInit to add setup -> callback
	 */
	beforeInit: false,
	
	/**
	 * Aferinit gets called as callback after setup if beforeInit is overridden 
	 */
	afterInit: function(){
		if (!this.col) {
			this.col = 'grid-' + this.maxCols + '-' + this.maxCols;
		}
		this.ct.add(this);
		this.ct.afterAdd();
	},
	
	/**
	 * 
	 */
	initComponent: function(){
		Garp.WysiwygAbstract.superclass.initComponent.call(this, arguments);
		
		if(this.beforeInit){
			this.beforeInit(this.afterInit);
		} else {
			this.afterInit();
		}
		
	}
});