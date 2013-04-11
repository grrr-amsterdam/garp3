Ext.ns('Garp');
Ext.enableListenerCollection = true;
Ext.QuickTips.init();
Ext.Direct.addProvider(Garp.API);

Garp.errorHandler = {
	msg: null,
	win: null,
	
	handler: function(msg, s){
		if (!msg) {
			msg = __('No readable error message specified');
		}
		var showClear = false;
		if (this.msg) {
			showClear = true;
			this.msg = msg + '<hr>' + this.msg;
		} else {
			this.msg = msg;
		}
		if (!this.win) {
			
			this.win = new Ext.Window({
				title: __('Error'),
				data: {
					msg: __('No error')
				},
				tpl: new Ext.Template(['<div class="garp-error-dialog" style="min-height: 80px; max-height: 250px; overflow: auto; ">','{msg}','</div>']),
				width: 500,
				buttonAlign: 'center',
				defaultButton: 'defaultButton',
				buttons: [{
					text: __('Ok'),
					id: 'defaultButton',
					handler: function(){
						this.win.hide();
					},
					scope: this
				}, {
					hidden: true,
					text: __('Login again'),
					handler: function(){
						this.win.close();
						window.location = BASE + 'g/auth/login';
					},
					scope: this
				}, {
					hidden: !showClear,
					ref: '../clearBtn',
					text: __('Clear messages'),
					handler: function(){
						this.msg = '';
						this.win.update({
							msg: this.msg
						});
						this.win.center();
					},
					scope: this
				}]
			});
		} else {
			this.win.clearBtn.show();
		}
		this.win.show();
		this.win.update({
			msg: this.msg
		});
		this.win.center();
		
	}
};
window.onerror = Garp.errorHandler.handler.createDelegate(Garp.errorHandler);

Ext.Direct.on({
	'exception': {
		fn: function(e, p){
			
			var transaction = '', action = '', method = '', message = '', tid = '';
			
			if (Ext.isObject(e)) {
				if (e.error) {
					message = e.error.message;
				} else {
					if(e.xhr && e.xhr.status === 403){
						message = __('You\'ve been logged out');
					} else {
						message = __('No connection');
					}
				}
				tid = e.tid;
				transaction = tid ? e.getTransaction() : null;
				
				if (Ext.isObject(transaction)) {
					action = transaction.action;
					method = transaction.method;
				}
				
				// now undirty & remove loadmasks again:
				// temporary!
				Garp.undirty();
				if (Garp.gridPanel && Garp.gridPanel.loadMask) {
					Garp.gridPanel.loadMask.hide();
					if (Garp.formPanel) {
						// reset state
						Garp.formPanel.state = 0;
						Garp.formPanel.updateUI();
						Garp.formPanel.fireEvent('dirty');
					}
				}
				
				
			}
			
			throw (
				'<b>' + (method ? __('Error trying to ') + __(method) : '' ) + ' ' + (Garp.dataTypes[action] ? '<i>'+__(Garp.dataTypes[action].text)+'</i>' : action) + '</b><br><br>' +
				__('Server response: ') + message || __('Nothing. Nada.') + '<br>' +
				(tid ? (__('Transaction id: ') + tid) : '') 
			);
			
			
		}
	}
});

/**
 * We can override Ext.ux.form.dateTime just now
 * Override it, so that the columnModel doesn't think it got changed. (isDirty fix)
 */
Ext.override(Ext.ux.form.DateTime, {
	getValue: function(){
		return this.dateValue ? this.dateValue.format(this.hiddenFormat) : '';
	}
});
/**
 * Idem
 */
Ext.override(Ext.form.Checkbox,{
	getValue: function(){
		if (this.rendered) {
			return this.el.dom.checked ? "1" : "0";
		}
		return this.checked ? "1" : "0";
	}
});
/**
 * Idem
 */
Ext.override(Ext.form.DateField, {
	format: 'd F Y',
	altFormats: 'Y-m-d|d F Y|j F Y|d m Y|d n Y|d-m-Y|d-n-Y',
	invalidText: __('"{0}" is not a valid date. Valid formats are a.o. "19 2 2013" and "19-02-2013"')
});
Ext.apply(Ext.form.TimeField.prototype, {
    altFormats: 'g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|ha|gA|h a|g a|g A|gi|hi|gia|hia|g|H|H:i:s'
});
/**
 * Idem
 */
Ext.apply(Ext.PagingToolbar.prototype, {
	beforePageText: '',
	displayMsg: ''
});

/**
 * Idem
 */
Ext.apply(Ext.ux.form.DateTime.prototype, {
	timeFormat: 'G:i',
	otherToNow: false,
	initDateValue:function() {
        this.dateValue = this.otherToNow ? new Date() : new Date(new Date().getFullYear(), 0, 1, 12, 0, 0);
    },
	timeConfig: {
		increment: 30
	},
	timeWidth: 70
});

Ext.form.VTypes.mailtoOrUrlText = __('Not a valid Url');

/**
 * Override To disable keynav when grid is disabled
 * @param {Object} e
 * @param {Object} name
 */
Ext.override(Ext.grid.RowSelectionModel, {
	onKeyPress : function(e, name){
		if(this.grid.disabled){
			return;
		}
        var up = name == 'up',
            method = up ? 'selectPrevious' : 'selectNext',
            add = up ? -1 : 1,
            last;
        if(!e.shiftKey || this.singleSelect){
            this[method](false);
        }else if(this.last !== false && this.lastActive !== false){
            last = this.last;
            this.selectRange(this.last,  this.lastActive + add);
            this.grid.getView().focusRow(this.lastActive);
            if(last !== false){
                this.last = last;
            }
        }else{
           this.selectFirstRow();
        }
    }
});


/**
 * use isValid() instead of validate() - validate causes the field to visually change state
 */
Ext.override(Ext.form.BasicForm, {
	isValid: function(){
		var valid = true;
		this.items.each(function(f){
			if (!f.isValid(true)) { // instead of validate()
				valid = false;
				return false;
			}
		});
		return valid;
	}
});

/**
 * Defaults for NumberField
 */
Ext.override(Ext.form.NumberField, {
	decimalPrecision: 16
});
Ext.override(Ext.form.NumberField, {
setValue : function(v){
v = typeof v == 'number' ? v : parseFloat(String(v).replace(this.decimalSeparator, "."));
v = isNaN(v) ? null : String(v).replace(".", this.decimalSeparator);
return Ext.form.NumberField.superclass.setValue.call(this, v);
},

// private

parseValue : function(value){
value = parseFloat(String(value).replace(this.decimalSeparator, "."));
return isNaN(value) ? null : value;
},

// private

fixPrecision : function(value){
var nan = isNaN(value);
if(!this.allowDecimals || this.decimalPrecision == -1 || nan || !value){
return nan ? null : value;
}
return parseFloat(parseFloat(value).toFixed(this.decimalPrecision));
}
});


/**
 * Enable key events in combo's and set a default emptyText
 */
Ext.apply(Ext.form.ComboBox.prototype, {
	enableKeyEvents: true,
	emptyText: __('(empty)'),
	afterRender: function(){
		Ext.form.ComboBox.superclass.afterRender.call(this);
		if (this.mode != 'local' || this.xtype == 'timefield' || this.xtype == 'datefield') {
			return;
		}
		var displayField = this.store.fields.items[this.store.fields.items.length - 1].name;
		var valueField = this.store.fields.items[this.store.fields.items.length - 2 > -1 ? this.store.fields.items.length - 2 : 0].name;
		this.el.on('keypress', function(e){
			var charc = String.fromCharCode(e.getCharCode());
			var selectedIndices = this.view.getSelectedIndexes();
			var currentSelectedIdx = (selectedIndices.length > 0) ? selectedIndices[0] : null;
			var startIdx = (currentSelectedIdx === null) ? 0 : ++currentSelectedIdx;
			var idx = this.store.find(displayField, charc, startIdx, false);
			if (idx > -1) {
				this.select(idx);
			} else if (idx == -1 && startIdx > 0) {
				// search looped, start at 0 again:
				idx = this.store.find(displayField, charc, 0, false);
				if (idx > -1) {
					this.select(idx);
				}
			}
			if (idx > -1) {
				var rec = this.store.getAt(idx);
				this.setValue(rec.get(valueField));
			}
		}, this);
	}
});


/** 
 * Override Ext.grid.GridView doRender, so that it passes a reference to the Grid view to a column Renderer
 * 
 * 
 * @param {Object} columns
 * @param {Object} records
 * @param {Object} store
 * @param {Object} startRow
 * @param {Object} colCount
 * @param {Object} stripe
 */
Ext.override(Ext.grid.GridView, {
	doRender: function(columns, records, store, startRow, colCount, stripe){
		var templates = this.templates, cellTemplate = templates.cell, rowTemplate = templates.row, last = colCount - 1, tstyle = 'width:' + this.getTotalWidth() + ';',  // buffers
		rowBuffer = [], colBuffer = [], rowParams = {
			tstyle: tstyle
		}, meta = {}, len = records.length, alt, column, record, i, j, rowIndex;
		
		//build up each row's HTML
		for (j = 0; j < len; j++) {
			record = records[j];
			colBuffer = [];
			
			rowIndex = j + startRow;
			
			//build up each column's HTML
			for (i = 0; i < colCount; i++) {
				column = columns[i];
				
				meta.id = column.id;
				meta.css = i === 0 ? 'x-grid3-cell-first ' : (i == last ? 'x-grid3-cell-last ' : '');
				meta.attr = meta.cellAttr = '';
				meta.style = column.style;
				meta.value = column.renderer.call(column.scope, record.data[column.name], meta, record, rowIndex, i, store, this);
				
				if (Ext.isEmpty(meta.value)) {
					meta.value = '&#160;';
				}
				
				if (this.markDirty && record.dirty && typeof record.modified[column.name] != 'undefined') {
					meta.css += ' x-grid3-dirty-cell';
				}
				
				colBuffer[colBuffer.length] = cellTemplate.apply(meta);
			}
			
			alt = [];
			//set up row striping and row dirtiness CSS classes
			if (stripe && ((rowIndex + 1) % 2 === 0)) {
				alt[0] = 'x-grid3-row-alt';
			}
			
			if (record.dirty) {
				alt[1] = ' x-grid3-dirty-row';
			}
			
			rowParams.cols = colCount;
			
			if (this.getRowClass) {
				alt[2] = this.getRowClass(record, rowIndex, rowParams, store);
			}
			
			rowParams.alt = alt.join(' ');
			rowParams.cells = colBuffer.join('');
			
			rowBuffer[rowBuffer.length] = rowTemplate.apply(rowParams);
		}
		
		return rowBuffer.join('');
	}
});

Ext.apply(Ext.form.TextField.prototype, {
	minLengthText: __('You have {2} character(s) too few. The minimal length is {0}.'),
	maxLengthText: __('You have {2} character(s) too many. The maximum length is {0}.'),
	getErrors: function(value){
		var errors = Ext.form.TextField.superclass.getErrors.apply(this, arguments);
		
		value = Ext.isDefined(value) ? value : this.processValue(this.getRawValue());
		
		if (Ext.isFunction(this.validator)) {
			var msg = this.validator(value);
			if (msg !== true) {
				errors.push(msg);
			}
		}
		
		if (value.length < 1 || value === this.emptyText) {
			if (this.allowBlank) {
				//if value is blank and allowBlank is true, there cannot be any additional errors
				return errors;
			} else {
				errors.push(this.blankText);
			}
		}
		
		if (!this.allowBlank && (value.length < 1 || value === this.emptyText)) { // if it's blank
			errors.push(this.blankText);
		}
		
		if (value.length < this.minLength) {
			errors.push(String.format(this.minLengthText, this.minLength, value.length, this.minLength - value.length)); // PP added too few
		}
		
		if (value.length > this.maxLength) {
			errors.push(String.format(this.maxLengthText, this.maxLength, value.length, value.length - this.maxLength)); // PP added too many
		}
		
		if (this.vtype) {
			var vt = Ext.form.VTypes;
			if (!vt[this.vtype](value, this)) {
				errors.push(this.vtypeText || vt[this.vtype + 'Text']);
			}
		}
		
		if (this.regex && !this.regex.test(value)) {
			errors.push(this.regexText);
		}
		
		return errors;
		
	}
});

Ext.apply(Ext.menu.Menu.prototype, {
	scrollIncrement: 35,
	onScrollWheel: function(e){
		if(e.getWheelDelta() > 0){
			this.onScroll(null, this.scroller.top);
		} else if (e.getWheelDelta() < 0){
			this.onScroll(null, this.scroller.bottom);
		}
	}
});
Ext.menu.Menu.prototype.createScrollers = Ext.menu.Menu.prototype.createScrollers.createSequence(function(){
	var scope = this;
	var task = null;
	 
	function startScroll(elm){
		if(task){
			stopScroll();
		}
		task = setInterval(function(){
			scope.onScroll(null, elm);
		}, 100);
	}
	
	function stopScroll(){
		clearInterval(task);
		task = null;
	}
	
	Ext.EventManager.addListener(this.el, 'mousewheel', this.onScrollWheel, this);
	this.on('destroy', function(){
		Ext.EventManager.removeListener(this.el, 'mousewheel', this.onScrollWheel, this);
	});
	
	this.scroller.top.on('mouseenter', startScroll.createDelegate(this, [this.scroller.top]));
	this.scroller.top.on('mouseleave', stopScroll);
	this.scroller.bottom.on('mouseenter', startScroll.createDelegate(this, [this.scroller.bottom]));
	this.scroller.bottom.on('mouseleave', stopScroll);
	
});

/** Fixes some el == null issues at D 'n D **/
Ext.lib.Dom.getXY = Ext.lib.Dom.getXY.createInterceptor(function(el){
	return el || false;
});
Ext.lib.Region.getRegion = Ext.lib.Region.getRegion.createInterceptor(function(el){
	return el || false;
});