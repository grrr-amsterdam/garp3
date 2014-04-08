Ext.ns('Garp');

/**
 * Simple extension upon image picker window. We need 'half' it's functionality
 */
Garp.ModelPickerWindow = Ext.extend(Garp.ImagePickerWindow, {
	
	/**
	 * @cfg model: name of the model to pick from:
	 */
	model: null,
	
	/**
	 * @cfg: hide the default wizard text above
	 */
	hideWizardText: true,
	
	/**
	 * @cfg: allow grid deselect; no item will be returned on 'Ok'
	 */
	allowBlank: false,
	
	// 'private' :
	activeItem: 0,
	
	/**
	 * Override default navigation 
	 * @param {Object} dir
	 */
	navHandler: function(dir){
		var page = this.getLayout().activeItem.id;
		page = parseInt(page.substr(5, page.length), 10);
		page += dir;
		if(page <= 0){
			page = 0;
		}
		
		switch(page){
			case 1:
				var selected = this.imgGrid.getSelectionModel().getSelected();
				this.fireEvent('select', {
					selected: selected || null
				});
				this.close();
			break;
			//case 0:
			default:
				if (!this.allowBlank) {
					var sm =this.imgGrid.getSelectionModel();
					sm.on('selectionchange', function(){
						this.nextBtn.setDisabled(sm.getCount() != 1);
					}, this);
				}
				this.prevBtn.disable();
				this.nextBtn.setText(__('Ok'));
				this.nextBtn.setDisabled(!this.allowBlank);
			break;
			
		}
		
		this.getLayout().setActiveItem('page-' + page);
	},
	
	initComponent: function(){
		var m = Garp.dataTypes[this.model];
		this.setTitle(__(m.text));
		this.setIconClass(m.iconCls);
		Garp.ModelPickerWindow.superclass.initComponent.call(this);
	}
	
});