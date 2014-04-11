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
	var  mailtoOrUrlRe = /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|((((^https?)|(^ftp)):\/\/)?([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i;
	//   mailtoOrUrlRe = /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|((((^https?)|(^ftp)):\/\/)([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i;
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
				if (!stricter.test(n) && n) {
					f.setValue('http://' + n);
				}
			}
		});

	}
};

/**
 * Override  updateRecord. We don't want non-dirty values to get synced. It causes empty string instead of null values...
 */
Ext.apply(Ext.form.BasicForm.prototype, {
	/**
     * Persists the values in this form into the passed {@link Ext.data.Record} object in a beginEdit/endEdit block.
     * @param {Record} record The record to edit
     * @return {BasicForm} this
     */
    updateRecord : function(record){
        record.beginEdit();
        var fs = record.fields,
            field,
            value;
        fs.each(function(f){
            field = this.findField(f.name);
			if(field){
				// ADDED BECAUSE OF NULL VALUES:
				if(typeof field.isDirty !== 'undefined' && !field.isDirty()){
					return;
				}
                value = field.getValue();
                if (Ext.type(value) !== false && value.getGroupValue) {
                    value = value.getGroupValue();
                } else if ( field.eachItem ) {
                    value = [];
                    field.eachItem(function(item){
                        value.push(item.getValue());
                    });
                }
                record.set(f.name, value);
            }
        }, this);
        record.endEdit();
        return this;
    }
});

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
if (!Ext.isIE) {
	Ext.apply(Ext.menu.Menu.prototype, {
		listeners: {
			'show': function(){
				if (!this.parentMenu) {
					var el = this.getEl();
					var o = {
						duration: 0.2
					};
					el.fadeIn(o);
					if (el.shadow && el.shadow.el) {
						el.shadow.el.fadeIn(o);
					}
				}
				return true;
			}
		}
	});
}
Ext.apply(Ext.grid.GridPanel.prototype, {
	listeners:{
		/**
		 * Clear selections, when clicking outside of selection:
 		 */
		'containerclick': function(){
			this.getSelectionModel().clearSelections();
		},

		/**
		 * Hide columns with no title/text from columnMenu's:
		 */
		'viewready': function(){
			if (this.getView() && this.getView().hmenu) {
				var columnMenu = this.getView().hmenu.get('columns');
				columnMenu.menu.on('beforeshow', function(){
					var items = this.items;
					items.each(function(menuItem){
						if (!menuItem.text) {
							columnMenu.menu.remove(menuItem.itemId);
						}
					});
				});
			}
		}
	}
});

/**
 * Image Template for embeding images
 */
Garp.imageTpl = new Ext.XTemplate(['<tpl if="caption">', '<tpl if="align">', '<dl class="figure" style="float: {align};">', '</tpl>', '<tpl if="!align">', '<dl class="figure" style="float: none;">', '</tpl>', '<dt>', '<img src="{path}" draggable="false"> ', '</dt>', '<dd draggable="false">{caption}</dd>', '</dl>', '</tpl>', '<tpl if="!caption">', '<tpl if="align">', '<img class="figure" src="{path}" style="float: {align};">', '</tpl>', '<tpl if="!align">', '<img class="figure" src="{path}" style="float: none;">', '</tpl>', '</tpl>']);

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
					if (!n) {
						return;
					}
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
			if ((f.xtype == 'tweetfield') && f.items) {
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
	// necessary for 'one' field models. Otherwise formDirty will not fire until field blur, which is odd
	enableKeyEvents: true,

	// countBox helper functions:
	getCountBox: function(){
		var cb;
		if (this.refOwner) {
			cb = this.refOwner[this.countBox];
		} else if (this.ownerCt.ownerCt) {
			cb = this.ownerCt.ownerCt.ownerCt.ownerCt[this.countBox];
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
		return true;
	},

	initComponent: function(){

		Ext.form.Field.superclass.initComponent.call(this);

		// Prevent conflicts with superbox select. We'll quit here:
		if(this.xtype && this.xtype == 'superboxselect'){
			return true;
		}
		this.on('blur', function(){
			if (this.getValue()) {
				if (this.getValue() === String(this.getValue())) {
					this.setValue(String(this.getValue()).trim());
				}
			}
			return true;
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

			if (!this.allowBlank && this.label) {
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
        this.addEvents(
            'focus',
            'blur',
            'specialkey',
            'change',
            'invalid',
            'valid'
        );
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
	},
	setValue: function(date){
		if (date && ((date.getFullYear && date.getFullYear() > -1) || this.parseDate(date))) {
			return Ext.form.DateField.superclass.setValue.call(this, this.formatDate(this.parseDate(date)));
		}
		return Ext.form.DateField.superclass.setValue.call(this, null);
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
