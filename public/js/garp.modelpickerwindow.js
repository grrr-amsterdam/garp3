Ext.ns('Garp');

Garp.ModelPickerWindow = Ext.extend(Garp.ImagePickerWindow, {
	
	/**
	 * @cfg model: name of the model to pick from:
	 */
	model: null,
	
	hideWizardText: true,
	
	/**
	 * @cfg: allow grid deselect; no item will be returned on 'Ok'
	 */
	allowBlank: false,
	
	/**
	 * private :
	 */
	activeItem: 0,
	
		
	initComponent: function(){
		var m = Garp.dataTypes[this.model];
		this.setTitle(m.text);
		this.setIconClass(m.iconCls);
		Garp.ModelPickerWindow.superclass.initComponent.call(this);
	},
	
	navHandler: function(dir){
		var page = this.getLayout().activeItem.id;
		page = parseInt(page.substr(5, page.length));
		page += dir;
		if(page <= 0){
			page = 0;
		}
		
		switch(page){
			case 0: default:
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
			case 1:
				var selected = this.imgGrid.getSelectionModel().getSelected();
				this.fireEvent('select', {
					selected: selected || null
				});
				this.close();
			break;
		}
		
		this.getLayout().setActiveItem('page-' + page);
	}
});