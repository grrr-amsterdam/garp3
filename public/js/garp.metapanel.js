/**
 * MetaPanel: meta information about the currently selected record in the formPanel
 */

Ext.ns('Garp');

Garp.MetaPanel = Ext.extend(Ext.Container, {
	
	region: 'east',
	width: 190,
	maxWidth: 190,
	header: false,
	border: false,
	cls: 'garp-metapanel',
	ref: 'metaPanel',
	monitorValid: false,
	
	/**
	 * Record to data, and fetch remote stuff
	 * @param {Object} rec
	 */
	processData: function(rec){
		this.rec = rec;
		this.update(rec.data);
		this.bindEditors();
		Ext.get('author-name').update(rec.get('author'));
		Ext.get('modifier-name').update(rec.get('modifier'));
	},
	
	/**
	 * Simple items, but they need Column Model based renderers
	 * @param {String} item or {Object} with property tpl for direct tpl access 
	 */
	buildTplItem: function(item){
		if (Ext.isObject(item)) {
			return item.tpl || '';
		} else {
			var out = '';
			out += '<h3>' + __(item) + '</h3>';
			out += '<p>{' + item + '}</p>'; // implement CM based renderer here 
			return out;
		}
	},
	
	/**
	 *  Setup template
	 */
	buildTpl: function(){
		var items = Garp.dataTypes[Garp.currentModel].metaPanelItems;
		var tpl = [];
		
		Ext.each(items, function(item){
			switch(item){
				case 'created':
					tpl.push('<h3>', __('Created'), '</h3>', 
					'<span id="author-image"></span>', '<a id="author-name"></a>', 
					'<a id="created-date">{[Garp.renderers.metaPanelDateRenderer(values.created)]}</a>');
				break;
				case 'modified':
					tpl.push('<h3>', __('Modified'), '</h3>',
					 '<span id="modifier-image"></span>', '<p id="modifier-name"></p>',
					 '<p id="modified-date">{[Garp.renderers.metaPanelDateRenderer(values.modified)]}</p>');
				break;
				case 'published':
					tpl.push('<h3>', __('Published'), '</h3>', 
						'<tpl if="typeof online_status !== &quot;undefined&quot;  && online_status === &quot;1&quot;">',
							'<a class="published-date">{[Garp.renderers.metaPanelDateRenderer(values.published)]}</a>',
							'<tpl if="values.published">', 
								' <a class="remove-published-date" title="', __('Delete'), '"> </a>', 
							'</tpl>', 
						'</tpl>',
						'<div id="online-status">', __('Draft'), ': ', 
						'<tpl if="typeof online_status !== &quot;undefined&quot; && online_status === &quot;1&quot;">', 
							'<input type="checkbox">',
						'</tpl>', 
						'<tpl if="typeof online_status !== &quot;undefined&quot;  && online_status === &quot;0&quot;">',
							'<input type="checkbox" checked>', 
						'</tpl></div>');
				break;
				default:
					tpl.push(this.buildTplItem(item));
				break;
			}			
		}, this);
		
		tpl.push('<div class="copyright">', 'Garp &copy {[Garp.renderers.yearRenderer(new Date())]} by ', '<a href="http://grrr.nl/" target="_blank">', 'Grrr', '</a><br>version 3.5.{[GARP_VERSION]}', '</div>');
		
		this.tpl = new Ext.XTemplate(tpl, {
			compiled: true
		});
	},
	

	/**
	 * Saves the new value on the server
	 * @param {Object} name
	 * @param {Object} val
	 */
	setVal: function(name, val){
		var rec = Garp.gridPanel.getSelectionModel().getSelected();
		if (rec.get('name') != val) { // only save when changed
			this.fireEvent('dirty');
			rec.beginEdit();
			rec.set(name, val);
			rec.endEdit();
			if (!rec.phantom) { // only save when record is already saved.
				this.disable();
				this.fireEvent('save-all');
			}
		}
	},

	/**
	 * Binds the editors to the UI elements
	 */
	bindEditors: function(){
		this.el.select('#author-name').un('click').on('click', function(e, el){
			this.authorIdEditor.startEdit(el, this.rec.get('author_id'));
			this.authorIdEditor.field.triggerFn();
			this.authorIdEditor.el.hide();
		}, this);
		this.el.select('#online-status input').un('click').on('click', function(e, el){
			this.setVal('online_status', Ext.get(el).getAttribute('checked') ? '0' : '1', true);
		}, this);
		this.el.select('#created-date').un('click').on('click', function(e, el){
			this.createdDateEditor.startEdit(el, this.rec.get('created'));
			this.createdDateEditor.field.df.onTriggerClick();
		}, this);
		this.el.select('.published-date').un('click').on('click', function(e, el){
			this.publishedDateEditor.startEdit(el, this.rec.get('published'));
			this.publishedDateEditor.field.df.onTriggerClick();
		}, this);
		this.el.select('.remove-published-date').un('click').on('click', function(e, el){
			this.setVal('published', null);
		}, this);
	},
	
	/**
	 * Creates editors for use later on
	 */
	buildEditors: function(){
		var cfg = {
			field: {
				xtype: 'xdatetime',
				width: 180,
				emptyText: __('No date specified'),
				timeConfig: {
					increment: 30
				},
				dateConfig: {
					emptyText: __('No date specified')
				},
				timeWidth: 60,
				dateFormat: 'j M Y'
			},
			offsets: [0, -6],
			alignment: 'tl?',
			completeOnEnter: true,
			cancelOnEsc: true,
			updateEl: false,
			ignoreNoChange: true
		};
		this.authorIdEditor = new Ext.Editor(Ext.apply({}, {
			field: {
				xtype: 'relationfield',
				model: 'User',
				displayField: 'fullname',
				listeners: {
					'select': function(v){
						if (v && v.selected) {
							this.setVal('author_id', v.selected.id);
						}
						this.authorIdEditor.completeEdit();
					},
					scope: this
				}
			}
		}, cfg));
		this.createdDateEditor = new Ext.Editor(Ext.apply({}, {
			listeners: {
				'beforecomplete': function(e, v){
					if (v) {
						this.setVal('created', v);
					}
				},
				scope: this
			}
		}, cfg));
		this.publishedDateEditor = new Ext.Editor(Ext.apply({}, {
			listeners: {
				'beforecomplete': function(e, v){
					if (v) {
						this.setVal('published', v);
					}
				},
				scope: this
			}
		}, cfg));
	},

	/**
	 * Init!
	 * @param {Object} parent container
	 */	
	initComponent: function(ct){
		this.buildTpl();
		this.buildEditors();
		Garp.MetaPanel.superclass.initComponent.call(this,ct);
		this.on({
			'loaddata': {
				fn: this.processData,
				scope: this
			}
		});
	}
	
});