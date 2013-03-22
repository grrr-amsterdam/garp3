/**
 * Ext.ux
 * RelationField / JoinedRelationField / BindedField
 * 
 * @TODO: move out of extux into Garp
 */

Ext.ns('Ext.ux');

Ext.ux.RelationField = Ext.extend(Ext.form.ComboBox,{
	/**
	 * @cfg model
	 */
	model: null,
	allowBlank: false,
	autoLoad: true,
	editable: false,
	triggerAction: 'all',
	typeAhead: false,
	mode: 'remote',
	valueField: 'id',
	displayField: 'name',
	disableCreate: false,
	emptyText: __('(empty)'),
	trigger1Class: 'relation-open-button',
	cls: 'relation-field',
	allQuery: '',
	lastQuery: '',
	baseParams: {
		start: 0,
		limit: Garp.pageSize
	},

	/**
	 * Gets called when the RelationPickerWindow is closed;
	 * @param {Object} selected
	 */	
	selectCallback: function(selected){
		var v = this.getValue();
		if (selected && selected.hasOwnProperty('selected')) {
			if (selected.selected === null) {
				this.setValue(null);
			} else {
				// set it to the value retrieved from the modelpicker:
				this.setValue(selected.selected.get(this.displayField) || selected.selected.get('id'));
				this.disable();
				// ...then reload to get the 'real' value from the server:
				// the reason we reload, is that the displayfield might not get passed from the picker (it just might not be there)
				
				this.store.on({
					load: function(){
						this.setValue(selected.selected.get('id'));
						this.enable();
						if (this.assertValue) {
							this.assertValue();
						}
						this.el.focus(true);
						this.collapse.defer(200, this);
					},
					single: true,
					scope: this
				});
				this.store.load();
			}
			
			this.originalValue = v;
			this.win.destroy();
			this.fireEvent('select', selected);
			return;
		} 
		this.win.destroy();
	},
	
	triggerFn: function(){
		this.win = new Garp.ModelPickerWindow({
			model: this.model,
			query: this.allQuery || null,
			allowBlank: this.allowBlank
		});
		this.win.on('select', this.selectCallback, this);
		this.win.on('close', this.selectCallback, this);
		this.win.show();
	},
	
	createRelationUrl: function(){
		if (this.getValue()) {
			return BASE + 'admin?' +
			Ext.urlEncode({
				model: this.model,
				id: this.getValue()
			});
		}
		return false;
	},
	
	onRelationOpenTriggerClick: function(){
		if (this.tip) {
			if (this.tip.isVisible()) {
				this.tip.hide();
			} else {
				this.tip.show();
			}
		} else {
			var url = this.createRelationUrl();
			if (url) {
				var win = window.open(url);
			}
		}
	},
	
	// override, to allow for null values (or "unrelate", so to say)
	getValue: function(){
		var value = Ext.ux.RelationField.superclass.getValue.call(this);
		if(value === '' || value == this.emptyText){
			value = null;
		}
		return value;
	},
	
	setValue: function(v){
		this.el.removeClass('x-form-invalid');
		if (v) {
			if (this.store.find('id', v) < 0) {
				this.disable();
				this.el.addClass('loading');
				this.store.load({
					add: true,
					single: true,
					params: {
						query: {
							'id': v
						}
					},
					callback: function(data, opts, success){
						this.enable();
						this.el.removeClass('loading');
						if (data && data.length) {
							this.assertValue();
							this.collapse();
						} else {
							// value not found! DB Integrity issue! 
							this.store.clearFilter();
							this.el.addClass('x-form-invalid');
						}
					},
					scope: this
				});
			}
		}
		if(v){
			this.getTrigger(0).removeClass('no-relation');
		} else {
			this.getTrigger(0).addClass('no-relation');
		}
		Ext.ux.RelationField.superclass.setValue.call(this, v);
	},
	
	
	initComponent: function(){
		this.store = new Ext.data.DirectStore({
			autoLoad: false,
			autoSave: false,
			pruneModifiedRecords: true,
			remoteSort: true,
			restful: true,
			autoDestroy: true,
			root: 'rows',
			idProperty: 'id',
			fields: Garp.dataTypes[this.model] ? Garp.dataTypes[this.model].getStoreFieldsFromColumnModel() : [],
			totalProperty: 'total',
			sortInfo: Garp.dataTypes[this.model] && Garp.dataTypes[this.model].sortInfo ? Garp.dataTypes[this.model].sortInfo : null,
			baseParams: this.baseParams,
			api: {
				create: Ext.emptyFn,
				read: Garp[this.model].fetch || Ext.emptyFn,
				update: Ext.emptyFn,
				destroy: Ext.emptyFn
			}
		});
		
		
		if (this.triggerFn) {
			this.onTriggerClick = this.triggerFn;
		}
		
		this.store.on({
			'load': {
				single: true,
				scope: this,
				fn: function(){
					var clear = {
						id: 0
					};
					clear[this.displayField] = this.emptyText;
					var rec = new this.store.recordType(clear, 0);
					this.store.add(rec);
					this.assertValue();
				}
			}
		});
		Ext.ux.RelationField.superclass.initComponent.call(this);
		this.triggerConfig = {
			tag: 'span',
			cls: 'x-form-twin-triggers',
			cn: [{
				tag: "img",
				src: Ext.BLANK_IMAGE_URL,
				alt: '',
				title: __('Open in new window'),
				cls: "x-form-trigger " + this.trigger1Class
			}, {
				tag: "img",
				src: Ext.BLANK_IMAGE_URL,
				alt: "",
				cls: "x-form-trigger " + this.trigger2Class
			}]
		};
		if (!Garp.dataTypes[this.model]) {
			this.disable();
		}
		
	},
	
	getTrigger : function(index){
        return this.triggers[index];
    },
    
    afterRender: function(){
		Ext.form.TwinTriggerField.superclass.afterRender.call(this);
		var triggers = this.triggers, i = 0, len = triggers.length;
		
		for (; i < len; ++i) {
			if (this['hideTrigger' + (i + 1)]) {
				triggers[i].hide();
			}
		}
		
		if (Garp.dataTypes[this.model].previewItems && Garp.dataTypes[this.model].previewItems.length) {
			this.tip = new Ext.ToolTip({
				target: this.el,
				html: '',
				anchor: 'top',
				anchorOffset: 5,
				closable: true,
				showDelay: 1000,
				hideDelay: 3500,
				listeners: {
					'show': function(tip){
						var idx = this.store.find('id', this.getValue());
						var items = Garp.dataTypes[this.model].previewItems;
						if (idx > -1) {
							var data = this.store.getAt(idx).data;
							var str = '<ul>';
							Ext.each(items, function(item){
								var value = data[item] || '';
								str += '<li>' + __(item) + ': <b>' + value + '</b></li>';
							});
							str += '</ul>';
							str += '<a target="_blank" href="' + BASE + 'admin/?model=' + this.model + '&id=' + data.id + '">' + __('Open in new window') + '</a>';
							tip.update(str);
						} else {
							tip.hide();
						}
					},
					scope: this
				},
				scope: this
			});
		}
	},

    initTrigger : function(){
        var ts = this.trigger.select('.x-form-trigger', true),
            triggerField = this;
            
        ts.each(function(t, all, index){
            var triggerIndex = 'Trigger'+(index+1);
            t.hide = function(){
                var w = triggerField.wrap.getWidth();
                this.dom.style.display = 'none';
                triggerField.el.setWidth(w-triggerField.trigger.getWidth());
                triggerField['hidden' + triggerIndex] = true;
            };
            t.show = function(){
                var w = triggerField.wrap.getWidth();
                this.dom.style.display = '';
                triggerField.el.setWidth(w-triggerField.trigger.getWidth());
                triggerField['hidden' + triggerIndex] = false;
            };
			if (index === 0) {
				this.mon(t, 'click', this.onRelationOpenTriggerClick, this, {
					preventDefault: true
				});
			} else {
				this.mon(t, 'click', this.onTriggerClick, this, {
					preventDefault: true
				});
			}
            t.addClassOnOver('x-form-trigger-over');
            t.addClassOnClick('x-form-trigger-click');
        }, this);
        this.triggers = ts.elements;
    },

    getTriggerWidth: function(){
        var tw = 0;
        Ext.each(this.triggers, function(t, index){
            var triggerIndex = 'Trigger' + (index + 1),
                w = t.getWidth();
            if(w === 0 && !this['hidden' + triggerIndex]){
                tw += this.defaultTriggerWidth;
            }else{
                tw += w;
            }
        }, this);
        return tw;
    },

    // private
    onDestroy : function() {
        Ext.destroy(this.triggers);
        Ext.form.TwinTriggerField.superclass.onDestroy.call(this);
    },
	
	/**
     * The function that should handle the trigger's click event.  This method does nothing by default
     * until overridden by an implementing function. See {@link Ext.form.TriggerField#onTriggerClick}
     * for additional information.
     * @method
     * @param {EventObject} e
     */
    onTrigger1Click : Ext.emptyFn,
    /**
     * The function that should handle the trigger's click event.  This method does nothing by default
     * until overridden by an implementing function. See {@link Ext.form.TriggerField#onTriggerClick}
     * for additional information.
     * @method
     * @param {EventObject} e
     */
    onTrigger2Click : Ext.emptyFn

});


/**
 * Joined RelationFields are pre-filled relations sent from the server. If we want to change it, 
 * we need to update the bindedField referenced field. The server then joins again.
 * Untill then, we use the referenced model's displayFieldRederer
 */
Ext.ux.JoinedRelationField = Ext.extend(Ext.ux.RelationField, {
	
	bindedField: null,
	
	selectCallback: function(selected){
		var val, disp;
		if(selected && typeof selected.selected !== 'undefined'){
			if (selected.selected) {
				val = selected.selected.get('id');
				disp = Garp.dataTypes[this.model].displayFieldRenderer(selected.selected);
			} else{
				val = null;
				disp = null;
			}
			this.form.findField(this.bindedField).setValue(val);
			this.setValue(disp);
			this.fireEvent('select', selected);
		}
	},
	
	// joinedRelation saves data in it's bindedField. The field itself must not get send!
	isDirty: function(){
		return false;
	},
	
	// joinedRelation saves data in it's bindedField. The field itself must not get send!
	getValue: function(){
		return null;
	},
	
	createRelationUrl: function(){
		var id = this.form.findField(this.bindedField).getValue();
		if (id) {
			return BASE + 'admin?' +
			Ext.urlEncode({
				model: this.model,
				id: id
			});
		}
		return false;
	},
	
	setValue: function(v){
		Ext.ux.RelationField.superclass.setValue.call(this, v);
		return this;
	}
});

/**
 * Glue component; this field holds the ID value for a joinedRelationField 
 */
Ext.ux.BindedField = Ext.extend(Ext.form.TextField, {
	bindedField: null,
	initComponent: function(v){
		this.hidden = true;
		this.hideFieldLabel = true;
		Ext.ux.BindedField.superclass.initComponent.call(this, v);
	}
});

// xtypes
Ext.reg('relationfield', Ext.ux.RelationField);
Ext.reg('joinedrelationfield', Ext.ux.JoinedRelationField);
Ext.reg('bindedfield', Ext.ux.BindedField);
