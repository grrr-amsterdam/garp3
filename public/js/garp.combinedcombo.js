/**
 * Experimental
 */
Garp.CombinedComboRow = Ext.extend(Ext.form.FieldSet,{
	
	name: '',
	model: '',
	value: null,
	removeRow: false,
	style: 'margin:0;padding:0;',
	layout: 'hbox',
	
	removeRow: function(){
		this.ownerCt.remove(this);
		this.destroy();
	},
	
	addRow: function(){
		this.ownerCt.add({
			xtype: 'combinedcomborow',
			model: this.model,
			name: this.name,
			fieldLabel: this.fieldLabel,
			showRemove: false
		});
		this.ownerCt.doLayout();
		this.get(1).setText('-');
		this.get(1).handler = this.removeRow;
	},
	
	initComponent: function(ct){
		Garp.CombinedComboRow.superclass.initComponent.call(this, ct);
		this.add([{
			fieldLabel: '',
			hideFieldLabel: true,
			xtype: 'relationfield',
			triggerFn: null,
			typeAhead: true,
			editable: true,
			allowBlank: true,
			forceSelection: false,
			hideTrigger: true,
			model: this.model,
			value: this.value,
			name: this.name,
			flex: 1,
			listeners: {
				'beforequery': function(evt){
					var q = {};
					var f = 'name'; //@TODO: make this dynamic!
					q[f + ' like'] = '%' + evt.query + '%';
					Ext.apply(evt, {
						forceAll: true,
						query: {
							or: q
						}
					});
				},
				scope: this
			}
		},{
			xtype: 'button',
			tabIndex: '-1',
			width: 40,
			margins: '0 0 0 10',
			text: this.showRemove ? '-' : '+',
			tooltip: this.showRemove ? __('Remove') : __('Add'),
			handler: this.showRemove ? this.removeRow : this.addRow,
			scope: this
		}]);
		
		this.doLayout();
		
		this.on('afterrender', function(){
			var map = new Ext.KeyMap(this.getEl(), [{
				key: [10, 13],
				fn: function(){
					this.blur();
					this.get(0).getEl().removeClass('x-form-focus');
					this.addRow();
					var last = this.ownerCt.items.length-1;
					this.ownerCt.items.items[last].get(0).focus(false);
				},
				scope: this
			}]);
		}, this);
	}
});
Ext.reg('combinedcomborow', Garp.CombinedComboRow);

Garp.CombinedCombo = Ext.extend(Ext.form.FieldSet,{
	name: '',
	rule: '',
	model: '',
	
	border: false,
	style: 'margin:0;padding:0;',
	
	getParentForm: function(){
		return this.findParentBy(function(i){
			return i.getForm;
		});
	},
	
	removeAllFields: function(){
		var children = this.findByType('combinedcomborow');
		Ext.each(children, function(i){
			i.destroy();
		});
		this.doLayout();
	},
	
	_selectionChange: function(){
		this.removeAllFields();
		
		var ownerId = this.getParentForm().getForm().findField('id').getValue();
		var q = {};
		q[this.rule + '.id'] = ownerId; 
		Garp[this.model].fetch({
			rule: this.rule,
			query: q
		}, function(res){
			console.warn(res);
			if (res && res.rows && res.rows.length) {
				Ext.each(res.rows, function(r, i){
					var f = this.add({
						xtype: 'combinedcomborow',
						model: this.model,
						hidden: true,
						name: this.name,
						value: r.id,
						showRemove: i != (res.rows.length-1)
					});
					f.get(0).store.on({
						'load': {
							fn: function(){
								f.show();
							},
							single: true
						}
					});
				}, this);
			} else {
				this.add({
					xtype:'combinedcomborow',
					model: this.model,
					name: this.name,
					showRemove: false
				});
			}
			this.doLayout();
		}, this);
	},
	
	
	initComponent: function(ct){
	
		Garp.CombinedCombo.superclass.initComponent.call(this, ct);
		Garp.eventManager.on({
			'selectionchange': {
				scope: this,
				fn: this._selectionChange,
				buffer: 400
			},
			'save-all': {
				fn: function(){
					console.log('DO SOMETHING, FOOL!');
				}
			}
		});
	}
	
});
Ext.reg('combinedcombo', Garp.CombinedCombo;
