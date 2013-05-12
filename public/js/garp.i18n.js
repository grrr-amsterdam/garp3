/**
 * 
 * i18nSource: a form field to contain values for referenced language fields;
 * it acts as a conduit for other fields in the form (they are grouped in i18nFieldsets).
 * e.g. on 'setValue' all referenced fields are 'setValue'd with their respective language value:
 *  
 *  field.name.setValue(obj) -->> field._name_nl.setValue(str); 
 *                                field._name_en.setValue(str);
 *  
 */
Garp.i18nSource = Ext.extend(Ext.form.Field, {

	ref: '../',
	style: 'display:none; height: 0; margin: 0; padding: 0;',
	hideLabel: true,
	
	originalValue: null,
	
	initComponent: function(ct){
		Garp.i18nSource.superclass.initComponent.call(this, ct);
		Garp.i18nSource.superclass.hide.call(this);
	},
	
	/**
	 * Get referenced field
	 * @param {String} lang
	 */
	getRefField: function(lang){
		return this.refOwner.find('name', ('_' + this.name + '_' + lang))[0];
	},
	
	setValue: function(v){
		Ext.each(LANGUAGES, function(lang){
			if (this.getRefField(lang)) {
				this.getRefField(lang).setValue(v[lang]);
			}
		}, this);
		Garp.i18nSource.superclass.setValue.call(this, v);
	},
	
	setRawValue: function(v){
		return this.setValue(v);
	},
	
	getValue: function(v){
		var out = {};
		Ext.each(LANGUAGES, function(lang){
			field = this.getRefField(lang);
			if (field && field.isDirty()) {
				out[lang] = field.getValue();
			}
		}, this);
		return out;
	},
	
	getRawValue: function(v){
		return this.getValue(v);
	},

	isDirty: function(v){
		var out = false;
		Ext.each(LANGUAGES, function(lang){
			if (this.getRefField(lang) && this.getRefField(lang).isDirty()) {
				out = true;
				return false;
			}
		}, this);
		return out;
	},
	
	/**
	 * Perform function on all referenced fields
	 * @param {String} function to perform
	 * @param {Object} [optional] param
	 * @param {Bool} skipSelf, do not perform the function on 'this'
	 */
	_setAll: function(func, param, skipSelf){
		Ext.each(LANGUAGES, function(lang){
			var f = this.getRefField(lang);
			if (f && f[func]) {
				f[func](param);
			}
		}, this);
		if (!skipSelf === true) {
			return Garp.i18nSource.superclass[func].call(this, param);
		}
	},
	
	setVisible: function(state){
		return this._setAll('setVisible', state, true);
	},
	setDisabled: function(state){
		return this._setAll('setDisabled', state);
	},
	show: function(state){
		return this._setAll('show', state, true);
	},
	hide: function(state){
		return this._setAll('hide', state, true);
	},
	enable: function(state){
		return this._setAll('enable', state);
	},
	disable: function(state){
		return this._setAll('disable', state);
	}	
	
});
Ext.reg('i18nsource', Garp.i18nSource);


/**
 * Simple Fieldset to hold i18n fields:
 */
Garp.i18nFieldSet = Ext.extend(Ext.form.FieldSet, {
	cls: 'i18n-fieldset',
	collapsed: true,
	initComponent: function(ct){
		Garp.i18nFieldSet.superclass.initComponent.call(this, ct);
	}
});
Ext.reg('i18nfieldset', Garp.i18nFieldSet);