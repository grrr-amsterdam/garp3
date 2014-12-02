/**
 * Simplistic Date&Time Picker 
 * @param {Object} config
 */
Ext.ux.DateTimePicker = Ext.extend(Ext.DatePicker, {
	
	showToday: true, // must be
	
	timeValue: '12:00',
	
	initComponent: function(){
		Ext.ux.DateTimePicker.superclass.initComponent.call(this);
	},
	
	onRender:function(ct,pos){
		Ext.ux.DateTimePicker.superclass.onRender.call(this,ct,pos);
		this.addTimeField.call(this);
	},
	
	addTimeField: function(){
		if (!this.timeField) {
			this.timeField = new Ext.form.TimeField({
				lazyInit: false,
				renderTo: this.el.child('.x-date-bottom'),
				value: this.timeValue,
				getListParent: function() {
    			    var parent = this.el.up('.x-menu');
					return parent;
    			},
				listeners:{
					'select': {
						fn: function(){
							return false;
						}
					},
					'focus': {
						fn: function(){
							this.doDisabled(true);
							return false;
						},
						scope:this
					},
					'blur': {
						fn: function(){
							this.doDisabled(false);
							return false;
						},
						scope: this
					},
					'render': function(){
						this.list.on('click',function(e){
							e.stopPropagation();
							return false;
						});
					}
				},
				width: 60				
			});
			Ext.select('.x-date-bottom div, .x-date-bottom table', this.el).each(function(){
				this.setStyle({
					'margin-left': '18px',
					'float': 'left'
				});
			});
		}
	}
});