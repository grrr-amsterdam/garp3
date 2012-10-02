/**
 * Extended DisplayField to accept a custom renderer.
 * @param {Object} val
 */
Ext.ux.form.RederedDisplayField = Ext.extend(Ext.form.DisplayField,  {
   
	renderer: function(val){
		return val;
	},
	
    setRawValue : function(v){
		v = this.renderer(v);
		
        if(this.htmlEncode){
            v = Ext.util.Format.htmlEncode(v);
        }
        return this.rendered ? (this.el.dom.innerHTML = (Ext.isEmpty(v) ? '' : v)) : (this.value = v);
    }

});

Ext.reg('rendereddisplayfield', Ext.ux.form.RederedDisplayField);