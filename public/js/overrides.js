/**
 * overrides.js
 * This file contains overrides and *small* extensions on the Ext Framework itself and the Garp namespace.
 */
Ext.ns('Garp');

/**
 * @DEBUG utility function. ONLY Needed if Ticket #70 (CMS JS error) occurs again.
 * 
Ext.override(Ext.Element, {
	getValue : function(asNumber){
		if (!this.dom) {
			\\TEMP = this;
			throw __('Error! Element "' + this.id + '" doesn\'t exist anymore.');
			
		} else {
			var val = this.dom.value;
			return asNumber ? parseInt(val, 10) : val;
		}
    }
});
*/

(function(){
	var mailtoOrUrlRe = /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|((((^https?)|(^ftp)):\/\/)?([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i;
	Ext.apply(Ext.form.VTypes, {
		mailtoOrUrl: function(val, field){
			return mailtoOrUrlRe.test(val);
		},
		mailtoOrUrlText: 'Not a valid Url'
	});
})();

Garp.mailtoOrUrlPlugin = {
	init: function(field){
		var stricter = /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|(((^https?)|(^ftp)):\/\/([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i;
		field.on({
			'change': function(f, n, o){
				if (!stricter.test(n)) {
					f.setValue('http://' + n);
				}
			}
		});	
		
	}
};

/**
 * Define default fieldset config:
 */
Ext.apply(Ext.form.FieldSet.prototype,{
	border: false,
	buttonAlign: 'left',
	collapsible: true,
	
	defaults: {
		anchor: '-30',
		msgTarget: 'under'
		
	},
	hideCollapseTool: true,
	labelWidth: 120,
	labelSeparator: ' ',
	titleCollapse: true
});

/**
 * Defaults for combo: 
 */
Ext.apply(Ext.form.ComboBox.prototype, {
	triggerAction: 'all',
	typeAhead: false
});

Ext.apply(Ext.grid.GridPanel.prototype,{
	loadMask: true
});

/**
 * Undirty is not native. Add it via Form's prototype. Simple extension on BasicForm
 */
Ext.apply(Ext.form.BasicForm.prototype,{

	// apply a change event to a form:
	// see: http://code.extjs.com:8080/ext/ticket/159
	add: function(){
		var me = this;
		me.items.addAll(Array.prototype.slice.call(arguments, 0));
		Ext.each(arguments, function(f){
			f.form = me;
			f.on('change', me.onFieldChange, me);
		});
		return me;
	},
	
	onFieldChange: function(){
		this.fireEvent('change', this);
	},
	// end see
	
	unDirty: function(){
		Ext.each(this.items.items, function(){
			if (this.xtype === 'richtexteditor' || this.xtype === 'htmleditor') {
				// Do not let the rte push textarea contents to iframe. It causes the caret to move to the end of the content... 
				this.on({
					'beforepush': {
						fn: function(){
							return false;
						},
						'single': true
					}
				});
			}
			this.originalValue = this.getValue();
		});
	},
	
	updateRecord: function(record){
		record.beginEdit();
		var fs = record.fields, field, value;
		fs.each(function(f){
			field = this.findField(f.name);
			if (field) {
				value = field.getValue();
				if (value !== null) {
					if (typeof value != undefined && value.getGroupValue) {
						value = value.getGroupValue();
					} else if (field.eachItem) {
						value = [];
						field.eachItem(function(item){
							value.push(item.getValue());
						});
					}
					record.set(f.name, value);
				}
			}
		}, this);
		record.endEdit();
		return this;
	}
});

/**
 * Override data Store for Zend Compatibility: 
 * 
 */
Ext.override(Ext.data.Store, {
	
	/**
	 * Sort as a string ("name ASC") instead of object { sort : 'name', dir : 'ASC' } 
	 */
	load: function(options){
		options = Ext.apply({}, options);
		this.storeOptions(options);
		if (this.sortInfo && this.remoteSort) {
			var pn = this.paramNames;
			options.params = Ext.apply({}, options.params);
			
			// sort as a string:
			options.params[pn.sort] = this.sortInfo.field + ' ' + this.sortInfo.direction;
			
			delete options.params[pn.dir];
		}
		try {
			return this.execute('read', null, options); // <-- null represents rs.  No rs for load actions.
		} 
		catch (e) {
			this.handleException(e);
			return false;
		}
	}
});

/**
 * Fancier menu show
 * Give all menus (but not submenus) a fade In
 */
Ext.apply(Ext.menu.Menu.prototype, {
	listeners:{
		'show': function(){
			if (!this.parentMenu) {
				var el = this.getEl();
				var o = {duration:.2};
				el.fadeIn(o);
				if (el.shadow && el.shadow.el) {
					el.shadow.el.fadeIn(o);
				}
			}
			return true;
		}
	}
});

/**
 * Clear selections, when clicking outside of selection
 */
Ext.apply(Ext.grid.GridPanel.prototype, {
	listeners:{
		'containerclick': function(){
			this.getSelectionModel().clearSelections(); 
		}
	}
});



/**
 * Video Template for embeding videos
 */
Garp.videoTpl = new Ext.XTemplate('<iframe width="{width}" height="{height}" src="{player}" frameborder="0"></iframe>');

/**
 * Ext.Panel setTitle override for TabPanels (ellipsis added)
 * @param {String} title
 * @param {String} iconCls
 */
Ext.override(Ext.Panel, {
	setTitle : function(title, iconCls){
		if (this.tabEl) {
			title = Ext.util.Format.ellipsis(title, 25, true);
		}
        this.title = title;
				
        if(this.header && this.headerAsText){
            this.header.child('span').update(title);
        }
        if(iconCls){
            this.setIconClass(iconCls);
        }
        this.fireEvent('titlechange', this, title);
        return this;
    }
});

/**
 * Defaults changes & clearInvalid update:
 */
Ext.apply(Ext.form.CompositeField.prototype, {	
	combineErrors: false,
	clearInvalid: function(){
		this.items.each(function(item){
			item.clearInvalid();
		}, this);
	}
});

/**
 * Override BasicForm getFieldValues, because compositeFields don't work well native (can't get fieldValues from items inside comp.field)
 * @param {Object} dirtyOnly
 */
/*
Ext.form.BasicForm.prototype.getFieldValues = function(dirtyOnly){
	var o = {}, n, key, val;
	function walk(f){
		n = f.getName();
		if (!n) 
			return;
		key = o[n];
		val = f.getValue();
		
		if (Ext.isDefined(key)) {
			if (Ext.isArray(key)) {
				o[n].push(val);
			} else {
				o[n] = [key, val];
			}
		} else {
			o[n] = val;
		}
	}
	this.items.each(function(f){
		if (!f.xtype == 'compositefield' && !f.disabled && (dirtyOnly !== true || f.isDirty())) {
			walk(f);
		} else if (f.xtype == 'compositefield' && f.items) {
			f.items.each(function(cf){
				walk(cf);
			})
		}
	});
	return o;
};*/

Ext.apply(Ext.BasicForm.prototype, {
	getFieldValues : function(dirtyOnly){
        var o = {},
            n,
            key,
            val;
        this.items.each(function(f) {
			
			function walk(f){
				
				if (!f.disabled && (dirtyOnly !== true || f.isDirty())) {
					n = f.getName();
					if(!n) return;
					key = o[n];
					val = f.getValue();
					
					if (Ext.isDefined(key)) {
						if (Ext.isArray(key)) {
							o[n].push(val);
						} else {
							o[n] = [key, val];
						}
					} else {
						o[n] = val;
					}
				}
			}
			if ((f.xtype == 'compositefield' || f.xtype == 'tweetfield') && f.items) {
				f.items.each(function(cf){
					walk(cf);
				});
			} else {
				walk(f);
			}
        });
        return o;
    }
});

/**
 * Extension to Ext.Grid Column
 */
Ext.override(Ext.grid.Column,{
	virtual: false
});

/**
 * 
 */
Ext.override(Ext.form.Field,{
	
	// countBox helper functions:	
	getCountBox: function(){
		var cb;
		if (this.refOwner) {
			cb = this.refOwner[this.countBox];
		} else if (this.ownerCt.ownerCt) {
			this.ownerCt.ownerCt.ownerCt.ownerCt[this.countBox];
		}
		return cb;
	},
	updateCountBox: function(){
		var l = this.maxLength - (this.getValue() ? this.getValue().length : 0);
		if(l < 0){
			this.getCountBox().getEl().addClass('negative');
		} else {
			this.getCountBox().getEl().removeClass('negative');
		}
		this.getCountBox().update(l + ' ' + __('left'));
	},
	hideCountBox: function(){
		this.getCountBox().update('');
	},
	
	initComponent: function(){

		Ext.form.Field.superclass.initComponent.call(this);
		
		
		/* Override: */
		
		this.on('blur', function(){
			if (this.getValue()) {
				if (this.getValue() === String(this.getValue())) {
					this.setValue(String(this.getValue()).trim());
				}
			}
		}, this);
		
		this.on('afterrender', function(){
			
			if (this.countBox && this.maxLength) {
				this.getEl().on({
					'keypress': {
						fn: this.updateCountBox,
						buffer: 50,
						scope: this
					}
				});
				this.on('focus',  this.updateCountBox, this);
				this.on('blur',  this.hideCountBox, this);
				this.on('change',  this.updateCountBox, this);
			}
		
			if (this.allowBlank == false && this.label) {
				this.label.addClass('required-field');
			}
			if (this.xtype == 'numberfield' && this.label){
				this.label.addClass('number-field');
				if (!this.width) {
					this.setWidth(50);
					delete this.anchor;
				}
			}
		}, this);
		/* End Override */
		
        this.addEvents(
            /**
             * @event focus
             * Fires when this field receives input focus.
             * @param {Ext.form.Field} this
             */
            'focus',
            /**
             * @event blur
             * Fires when this field loses input focus.
             * @param {Ext.form.Field} this
             */
            'blur',
            /**
             * @event specialkey
             * Fires when any key related to navigation (arrows, tab, enter, esc, etc.) is pressed.
             * To handle other keys see {@link Ext.Panel#keys} or {@link Ext.KeyMap}.
             * You can check {@link Ext.EventObject#getKey} to determine which key was pressed.
             * For example: <pre><code>
var form = new Ext.form.FormPanel({
    ...
    items: [{
            fieldLabel: 'Field 1',
            name: 'field1',
            allowBlank: false
        },{
            fieldLabel: 'Field 2',
            name: 'field2',
            listeners: {
                specialkey: function(field, e){
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.{@link Ext.EventObject#getKey getKey()} == e.ENTER) {
                        var form = field.ownerCt.getForm();
                        form.submit();
                    }
                }
            }
        }
    ],
    ...
});
             * </code></pre>
             * @param {Ext.form.Field} this
             * @param {Ext.EventObject} e The event object
             */
            'specialkey',
            /**
             * @event change
             * Fires just before the field blurs if the field value has changed.
             * @param {Ext.form.Field} this
             * @param {Mixed} newValue The new value
             * @param {Mixed} oldValue The original value
             */
            'change',
            /**
             * @event invalid
             * Fires after the field has been marked as invalid.
             * @param {Ext.form.Field} this
             * @param {String} msg The validation message
             */
            'invalid',
            /**
             * @event valid
             * Fires after the field has been validated with no errors.
             * @param {Ext.form.Field} this
             */
            'valid'
        );
	}
});

/**
 * MySQL doesn't like null for TimeFields and DateFields. It's picky on time format too:
 */
Ext.override(Ext.form.TimeField, {
	format: 'H:i', // we default to 24Hr format. "12:00 AM" is not supported
	getValue: function(){
		var v = Ext.form.TimeField.superclass.getValue.call(this);
        return this.formatDate(this.parseDate(v)) || null;
	}
});
Ext.override(Ext.form.DateField, {
	getValue: function(){
		return this.parseDate(Ext.form.DateField.superclass.getValue.call(this)) || null;
	}
});

/**
 * Same as field ^^
 */
Ext.override(Ext.Button, {
	initComponent: function(){
	
		if (this.menu) {
			this.menu = Ext.menu.MenuMgr.get(this.menu);
			this.menu.ownerCt = this;
		}
		
		Ext.Button.superclass.initComponent.call(this);
		
		// fieldLabel class support:
		if (this.allowBlank !== true) {
			this.on('afterrender', function(){
				if (this.label) {
					this.label.addClass('required-field');
				}
			}, this);
			
		}
		
		this.addEvents('click', 'toggle', 'mouseover', 'mouseout', 'menushow', 'menuhide', 'menutriggerover', 'menutriggerout');
		
		if (this.menu) {
			this.menu.ownerCt = undefined;
		}
		if (Ext.isString(this.toggleGroup)) {
			this.enableToggle = true;
		}
	}
});

/**
 * provide renderer function to displayFields:
 */
Ext.override(Ext.form.DisplayField, {
	setValue: function(v){
		if (this.renderer) {
			v = this.renderer(v);
		}
		this.setRawValue(v);
		return this;
	}
});

/**
 * custom (simplification of) paging toolbar
 */
Ext.override(Ext.PagingToolbar, {
	initComponent : function(){
		var T = Ext.Toolbar;
		this.first = new T.Button({});
		this.last = new T.Button({});
		this.refresh = new T.Button({});
		
        var pagingItems = [ this.prev = new T.Button({
            tooltip: this.prevText,
            overflowText: this.prevText,
            iconCls: 'x-tbar-page-prev',
            disabled: true,
            handler: this.movePrevious,
            scope: this
        }), this.beforePageText,
        this.inputItem = new Ext.form.NumberField({
            cls: 'x-tbar-page-number',
            allowDecimals: false,
            allowNegative: false,
            enableKeyEvents: true,
            selectOnFocus: true,
            submitValue: false,
            listeners: {
                scope: this,
                keydown: this.onPagingKeyDown,
                blur: this.onPagingBlur
            }
        }), this.afterTextItem = new T.TextItem({
            text: String.format(this.afterPageText, 1)
        }), this.next = new T.Button({
            tooltip: this.nextText,
            overflowText: this.nextText,
            iconCls: 'x-tbar-page-next',
            disabled: true,
            handler: this.moveNext,
            scope: this
        })];


        var userItems = this.items || this.buttons || [];
        if (this.prependButtons) {
            this.items = userItems.concat(pagingItems);
        }else{
            this.items = pagingItems.concat(userItems);
        }
        delete this.buttons;
        if(this.displayInfo){
            this.items.push('->');
            this.items.push(this.displayItem = new T.TextItem({}));
        }
        Ext.PagingToolbar.superclass.initComponent.call(this);
        this.addEvents(
            /**
             * @event change
             * Fires after the active page has been changed.
             * @param {Ext.PagingToolbar} this
             * @param {Object} pageData An object that has these properties:<ul>
             * <li><code>total</code> : Number <div class="sub-desc">The total number of records in the dataset as
             * returned by the server</div></li>
             * <li><code>activePage</code> : Number <div class="sub-desc">The current page number</div></li>
             * <li><code>pages</code> : Number <div class="sub-desc">The total number of pages (calculated from
             * the total number of records in the dataset as returned by the server and the current {@link #pageSize})</div></li>
             * </ul>
             */
            'change',
            /**
             * @event beforechange
             * Fires just before the active page is changed.
             * Return false to prevent the active page from being changed.
             * @param {Ext.PagingToolbar} this
             * @param {Object} params An object hash of the parameters which the PagingToolbar will send when
             * loading the required page. This will contain:<ul>
             * <li><code>start</code> : Number <div class="sub-desc">The starting row number for the next page of records to
             * be retrieved from the server</div></li>
             * <li><code>limit</code> : Number <div class="sub-desc">The number of records to be retrieved from the server</div></li>
             * </ul>
             * <p>(note: the names of the <b>start</b> and <b>limit</b> properties are determined
             * by the store's {@link Ext.data.Store#paramNames paramNames} property.)</p>
             * <p>Parameters may be added as required in the event handler.</p>
             */
            'beforechange'
        );
        this.on('afterlayout', this.onFirstLayout, this, {single: true});
        this.cursor = 0;
        this.bindStore(this.store, true);
    }});
