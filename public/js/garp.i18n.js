/**
 * 
 */

Garp.i18nSource = Ext.extend(Ext.form.Field, {

	ref: '../',
	
	originalValue: null,
	
	initComponent: function(ct){
		Garp.i18nSource.superclass.initComponent.call(this, ct);
	},
	
	getRefField: function(lang){
		return this.refOwner.find('name', ('_' + this.name + '_' + lang))[0];
	},
	
	setValue: function(v){
		Ext.each(LANGUAGES, function(lang){
			this.getRefField(lang).setValue(v[lang]);
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
			if (field.isDirty()) {
				out[lang] = field.getValue();
			}
		}, this);
		return out;
	},
	
	isDirty: function(v){
		var out = false;
		Ext.each(LANGUAGES, function(lang){
			if (this.getRefField(lang).isDirty()) {
				out = true;
				return false;
			}
		}, this);
		return out; //(this.getValue() !== this.originalValue);
	},
	
	getRawValue: function(v){
		return this.getValue(v);
	}
	
});
Ext.reg('i18nsource', Garp.i18nSource);

Garp.i18nFieldSet = Ext.extend(Ext.form.FieldSet, {
	style: 'border-top: 1px #ddd dotted; padding: 0; margin: 20px 0 0 0; ',
	initComponent: function(ct){
		Garp.i18nFieldSet.superclass.initComponent.call(this, ct);
	}
});
Ext.reg('i18nfieldset', Garp.i18nFieldSet);