Ext.ns('Garp.renderers');

/**
 * General Column Renderers & Field converters
 * 
 */
Ext.apply(Garp.renderers,{
	
	/**
	 * Converter for fullname fields. Combines first, prefix, last into one field. Usefull e.g. as displayField for relationFields
	 * @param {Object} v field value (not used)
	 * @param {Object} rec Ext.Record
	 */
	fullNameConverter: function(v, rec){
		var name = [];
		Ext.each(['first_name', 'last_name_prefix', 'last_name'], function(f){
			if(rec[f]){
				name.push(rec[f]);
			}
		});
		if(!name){
			name = rec.name;
		}
		return name.join(' ');
	},
	
	/**
	 * Google address resolver. Forms only
	 * @param {Object} arr
	 * @return {String} formated address
	 */
	geocodeAddressRenderer: function(arr){
		arr = arr.address_components;
		var country_long,
		country,
		city,
		street,
		housenumber,
		out = '';

		Ext.each(arr, function(i){
			if(i.types.indexOf('country') > -1){
				country_long = i.long_name;
				country = i.short_name;
			}
			if(i.types.indexOf('locality') > -1){
				city = i.short_name;
			}
			if(i.types.indexOf('route') > -1){
				street = i.short_name;
			}
			if(i.types.indexOf('street_number') > -1){
				housenumber = i.short_name;
			}
			
		});
		if (housenumber) {
			out = street + ' ' + housenumber;
		} else if (street) {
			out = street;
		} 
		if(city){
			out += ', ' + city;
		}
		if(country != 'NL'){
			out += ', ' + country_long;
		}
		return out;		
	},
	
	/**
	 * We do not want HTML to be viewed in a grid column. This causes significant performance hogs and Adobe Flash™ bugs otherwise...
	 */
	htmlRenderer: function(){
		return '';
	},
	
	/**
	 * Shorter Date & Time
	 * @param {Date/String} date
	 */
	shortDateTimeRenderer: function(date, meta, rec){
		var displayFormat = 'd M Y H:i';
		if (Ext.isDate(date)) {
			return date.format(displayFormat);
		}
		if (date && typeof Date.parseDate(date,'Y-m-d H:i:s') != 'undefined') {
			return Date.parseDate(date, 'Y-m-d H:i:s').format(displayFormat);
		}
		return '-';
	},
	
	
	/**
	 * Date & Time
	 * @param {Date/String} date
	 */
	dateTimeRenderer: function(date, meta, rec){
		var displayFormat = 'd F Y H:i';
		if (Ext.isDate(date)) {
			return date.format(displayFormat);
		}
		if (date && typeof Date.parseDate(date,'Y-m-d H:i:s') != 'undefined') {
			return Date.parseDate(date, 'Y-m-d H:i:s').format(displayFormat);
		}
		return '-';
	},
	
	/**
	 * Used in metaPanel
	 * @param {Object} v
	 */
	metaPanelDateRenderer: function(v){
		if (v && v.substr(0, 4) == '0000') {
			return '<i>' + __('Invalid date') + '</i>';
		}
		return v ? Garp.renderers.intelliDateTimeRenderer(v) : '<i>' + __('No date specified') + '</i>';
	},
	
	/**
	 * Date only
	 * @param {Object} date
	 */
	dateRenderer: function(date, meta, rec){
		var displayFormat = 'd F Y';
		if (Ext.isDate(date)) {
			var v =  date.format(displayFormat);
			return v;
		}
		if (date && typeof Date.parseDate(date, 'Y-m-d') != 'undefined') {
			return Date.parseDate(date, 'Y-m-d').format(displayFormat);
		} else if (date && typeof Date.parseDate(date, 'd F Y') != 'undefined') {
			return Date.parseDate(date, 'd F Y').format(displayFormat);
		}
		return '-';
	},
	
	/**
	 * 
	 * @param {Date/String} date
	 * @param {Object} meta
	 * @param {Object} rec
	 */
	timeRenderer: function(date, meta, rec){
		var displayFormat = 'H:i';
		if (Ext.isDate(date)) {
			var v =  date.format(displayFormat);
			return v;
		}
		if (date && typeof Date.parseDate(date, 'H:i:s') != 'undefined') {
			return Date.parseDate(date, 'H:i:s').format(displayFormat);
		} else if (date && typeof Date.parseDate(date, 'H:i') != 'undefined') {
			return Date.parseDate(date, 'H:i').format(displayFormat);
		}
		return '-';
	},
	
	/**
	 * Year
	 * @param {Date/String} date
	 */
	yearRenderer: function(date, meta, rec){
		var displayFormat = 'Y';
		if (Ext.isDate(date)) {
			return date.format(displayFormat);
		}
		if (date && typeof Date.parseDate(date,'Y') != 'undefined') {
			return Date.parseDate(date, 'Y').format(displayFormat);
		}
		return '-';
	},
	
	
	/**
	 * For use in Forms. 
	 * 
	 * Displays today @ time, yesterday @ time or just the date (WITHOUT time)
	 * 
	 * @TODO Decide if this also needs to go into grids. Make adjustments then.
	 * @param {Date} date
	 */
	intelliDateTimeRenderer: function(date){
		var now = new Date();
		var yesterday = new Date().add(Date.DAY, -1);
		date = Date.parseDate(date, 'Y-m-d H:i:s');
		if (!date) {
			return;
		}
		if(date.getYear() == now.getYear()){
			if(date.getMonth() == now.getMonth()){
				if(date.getDate() == yesterday.getDate()){
					return __('Yesterday at') + ' ' + date.format('H:i');
				}
				if(date.getDate() == now.getDate()){
					//if(date.getMinutes() == now.getMinutes()){
					//	return __('a few seconds ago');
					//} 
					return __('Today at') + ' ' + date.format('H:i');
				}
				return date.format('j M');
			}	
		}
		return date.format('j M Y');
	},
	
	
	/**
	 * Image
	 * @param {Object} val
	 */
	imageRenderer: function(val, meta, record, options){
		if(!record){
			record = {
				id: 0
			};
		}
		if(!Ext.isObject(options)){
			options = {
				size: 64
			};
		}
		var localTpl = new Ext.Template('<div class="garp-image-renderct"><img src="' + IMAGES_CDN + 'scaled/cms_list/' + record.id + '" width="' + options.size+ '" alt="{0}" /></div>', {
			compile: false,
			disableFormats: true
		});
		var remoteTpl = new Ext.Template('<div class="garp-image-renderct"><img src="{0}" width="' + options.size+ '" alt="{0}" /></div>', {
			compile: false,
			disableFormats: true
		});
		
		if (typeof record == 'undefined') {
			return __('New Image');
		}
		
		var v = val ? (/^https?:\/\//.test(val) ? remoteTpl.apply([val]) : localTpl.apply([val])) : __('No Image uploaded');
		return v;
	},
	
	/**
	 * 
	 * @param {String} val image Id
	 */
	imageRelationRenderer: function(val){
		var imgHtml = val ? '<img src="' + IMAGES_CDN + 'scaled/cms_list/' + val + '" width="64" alt="" />' : '&nbsp;';
		return '<div class="garp-image-renderct">'+imgHtml+'</div>';
	},
	
	/**
	 * Image rendererer primarily intended for Garp.formPanel, not realy appropriate as a column renderer. Use imageRenderer instead
	 * @param {String} val
	 */
	imagePreviewRenderer: function(val,meta,record){
		var tpl = new Ext.Template('<div class="garp-image-renderct"><img src="' + IMAGES_CDN + 'scaled/cms_preview/' + record.id + '" alt="{0}" /></div>', {
			compile: true,
			disableFormats: true
		});
		if(typeof record == 'undefined'){
			record = {
				phantom : true
			};
		}
		var v =  val ? tpl.apply([val]) : record.phantom === true ? __('New Image') : __('No Image uploaded');
		return v;
	},
	
	/**
	 * 
	 * @param {String} val
	 */
	uploadedImagePreviewRenderer: function(val){
		var tpl = new Ext.Template('<div class="garp-image-renderct"><img src="' + IMAGES_CDN + val + '" /></div>', {
			compile: true,
			disableFormats: true
		});
		return tpl.apply(val);
	},

	
	/**
	 * cropPreviewRenderer
	 */
	cropPreviewRenderer: function(val, meta, record){
		if (record.get('w') && record.get('h')) {
			var size = 32;
			var w = record.get('w') / record.get('h') * size;
			var h = record.get('h') / record.get('w') * size;
			if (w > size) {
				h = h * (size / w);
				w = size;
			}
			if (h > size) {
				w = w * (size / h);
				h = size;
			}
			if (h == w) {
				w = h = size * 0.75;
			}
			var mt = (size - h) / 2;
			var ml = (size - w) / 2;
			h = Math.ceil(h);
			w = Math.ceil(w);
			mt = Math.floor(mt);
			ml = Math.floor(ml);
			
			return '<div style="background: #aaa; width: ' + size + 'px; height: ' + size + 'px; border: 1px #888 solid;"><div style="width: ' + w + 'px; height: ' + h + 'px; background-color: #eee;margin: ' + mt + 'px ' + ml + 'px;"></div></div>';
		} else {
			return '';
		}
	},
	
	/**
	 * 
	 * @param {Number} row
	 * @param {Number} cell
	 * @param {Object} view
	 */
	checkVisible: function(row, cell, view){
		if(view.getRow(row)){
			return Ext.get(view.getCell(row, cell)).isVisible(true);
		}
		return false;
	},
	
	/**
	 * remoteDisplayFieldRenderer
	 * grabs the external model and uses its displayField
	 * 
	 * Usage from extended model:
	 * @example this.addColumn({
	 *  // [...]
	 *  sortable: false
	 * 	dataIndex: 'Cinema'
	 * 	renderer: Garp.renderers.remoteDisplayFieldRenderer.createDelegate(null, ['Cinema'], true) // no scope, Cinema model, append arguments
	 * });
	 * 
	 * @param {Object} val
	 * @param {Object} meta
	 * @param {Object} rec
	 * @param {Number} rI
	 * @param {Number} cI
	 * @param {Object} store
	 * @param {Object} view
	 * @param {String} modelName
	 */
	remoteDisplayFieldRenderer: function(val, meta, rec, rI, cI, store, view, modelName){
		if (!modelName || !val) {
			return;
		}
		view.on('refresh', function(){
				if (Garp.renderers.checkVisible(rI, cI, view)) {
					Garp[modelName].fetch({
						query: {
							id: val
						}
					}, function(res){
						// make res an ultra ligtweight 'pseudo record':
						if (res.rows && res.rows[0]) {
							res.rows[0].get = function(v){
								return this[v] || '';
							};
							var text = Garp.dataTypes[modelName].displayFieldRenderer(res.rows[0]);
							if (Ext.get(view.getCell(rI, cI))) {
								Ext.get(view.getCell(rI, cI)).update('<div unselectable="on" class="x-grid3-cell-inner x-grid3-col-0">' + text + '</div>');
							}
						}
					});
				}
			
		}, {
			buffer: 200,
			scope: this
		});
		
		return '<div class="remoteDisplayFieldSpinner"></div>';
	},
	
	checkboxRenderer: function(v){
		return v == '1'  ? __('yes') : __('no');
	}
});
