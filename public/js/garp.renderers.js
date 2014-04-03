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
			rec[f] ? name.push(rec[f]) : true;
		});
		return name.join(' ');
	},
	
	/**
	 * We do not want HTML to be viewed in a grid column. The cause significant performance hogs and Adobe Flash™ bugs...
	 */
	htmlRenderer: function(){
		return '';
	},
	
	/**
	 * Shorter Date & Time
	 * @param {Object} date
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
	 * @param {Object} date
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
	 * Date only
	 * @param {Object} date
	 */
	dateRenderer: function(date, meta, rec){
		var displayFormat = 'd F Y'
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
	
	timeRenderer: function(date, meta, rec){
		var displayFormat = 'H:i'
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
	 * @param {Object} date
	 */
	yearRenderer: function(date, meta, rec){
		var displayFormat = 'Y'
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
	 * @param {Object} date
	 */
	intelliDateTimeRenderer: function(date){
		var now = new Date();
		var yesterday = new Date().add(Date.DAY, -1);
		var date = Date.parseDate(date, 'Y-m-d H:i:s');
		if (!date) {
			return;
		}
		if(date.getYear() == now.getYear()){
			if(date.getMonth() == now.getMonth()){
				if(date.getDate() == yesterday.getDate()){
					return __('Yesterday at') + ' ' + date.format('H:i')
				}
				if(date.getDate() == now.getDate()){
					//if(date.getMinutes() == now.getMinutes()){
					//	return __('a few seconds ago');
					//} 
					return __('Today at') + ' ' + date.format('H:i')
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
	imageRenderer: function(val, meta, record){
		var localTpl = new Ext.Template('<div class="garp-image-renderct"><img src="' + IMAGES_CDN + 'scaled/cms_list/' + record.id + '" width="64" alt="{0}" /></div>', {
			compile: true,
			disableFormats: true
		});
		var remoteTpl = new Ext.Template('<div class="garp-image-renderct"><img src="{0}" width="64" alt="{0}" /></div>', {
			compile: true,
			disableFormats: true
		});
		
		if (typeof record == 'undefined') {
			var record = {
				phantom: true
			};
		}
		var v = record.phantom === true ? __('New Image') : val ? (/^https?:\/\//.test(val) ? remoteTpl.apply([val]) : localTpl.apply([val])) : __('No Image uploaded');
		return v;
	},
	
	/**
	 * 
	 * @param {String} val image Id
	 */
	imageRelationRenderer: function(val){
		return val ? '<div class="garp-image-renderct"><img src="' + IMAGES_CDN + 'scaled/cms_list/' + val + '" width="64" alt="" /></div>' : ''
	},
	
	/**
	 * Image rendererer primarily intended for Garp.formPanel, not realy appropriate as a column renderer. Use imageRenderer instead
	 * @param {Object} val
	 */
	imagePreviewRenderer: function(val,meta,record){
		var tpl = new Ext.Template('<div class="garp-image-renderct"><img src="' + IMAGES_CDN + 'scaled/cms_preview/' + record.id + '" alt="{0}" /></div>', {
			compile: true,
			disableFormats: true
		});
		if(typeof record == 'undefined'){
			var record = {
				phantom : true
			};
		}
		var v =  val ? tpl.apply([val]) : record.phantom === true ? __('New Image') : __('No Image uploaded');
		return v;
	},
	
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
				w = h = size * .75;
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
	}
});