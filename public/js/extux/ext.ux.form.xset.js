/* eslint-disable */
Ext.ns('Ext.ux.form');

Ext.ux.form.XSet = Ext.extend(Ext.form.CheckboxGroup, {
  xtype: 'xset',
  columns: 1,
  items: [],

  initComponent: function() {
    var items = [];
    var scope = this.ct;
    Ext.iterate(this.options, function(key, value) {
      items.push({
        allowBlank: true,
        boxLabel: typeof value === 'string' ? value.replace(/_/g, ' ') : key,
        submitValue: false,
        name: key,
        checked: true,
        renderTo: scope,
        labelSeparator: ''

      });
    });
    this.items = items;
    Ext.ux.form.XSet.superclass.initComponent.call(this);
  },

  isDirty: function(){
    if(typeof this.originalValue !== 'undefined') {
      return this.getValue() !== this.originalValue;
    }
    return false;
  },

  setValue: function(value) {
    var val = {};
    Ext.each(this.items.items, function(i){
      if (i) {
        val[i.name] = false;
      }
    });

    if (value) {
      Ext.each(Array.isArray(value) ? value : value.split(','), function(v){
        val[v] = true;
      });
    }
    Ext.ux.form.XSet.superclass.setValue.call(this, val);
  },

  getValue: function(value) {
    var val = [];
    Ext.each(this.items.items, function(i){
      if (i.checked) {
        val.push(i.name);
      }
    });
    return val.join(',');
  },
});

Ext.reg('xset', Ext.ux.form.XSet);
