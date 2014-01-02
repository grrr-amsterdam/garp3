Ext.ns('Garp');

Garp.MetaPanel = Ext.extend(Ext.Container, {

	region: 'east',
	width: 190,
	collapsible: true,
	collapseMode: 'mini',
	animCollapse: false,
	header: false,
	layout: 'form',
	border: false,
	cls: 'garp-metapanel',
	ref: 'metaPanel',
	monitorValid: false,
	
	rec : null,
	
	data: {
		authorName: '',
		authorImg: '',
		createdDate: '',
		modifierName: '',
		modifierImg: '',
		modifiedDate: '',
		publishedDate: '',
		onlineStatus: ''
	},
	
	/**
	 * Holds data to update data asynchronous
	 * @param {Object} data
	 */
	updateData: function(data){
		if (data) {
			Ext.apply(this.data, data);
			this.update(this.data);
		} else {
			this.update();
		}
		this.setupEditors();
	},
	
	
	setVal:function(name, val){
		if (!Garp.formPanel.isLocked()) {
			var rec = Garp.gridPanel.getSelectionModel().getSelected();
			rec.beginEdit();
			rec.set(name, val);
			rec.endEdit();
			this.fireEvent('loaddata', rec);
		}
	},
	
	tpl: new Ext.XTemplate(
		'<div class="hr"></div>',
		'<h2>',__('Created'),'</h2>',
		'{authorImg}',
		'<a class="author-name">{authorName}</a>',
		'<a class="created-date">{[this.formatDate(values.createdDate)]}</a>',
		
		'<h2>',__('Modified'),'</h2>',
		'{modifierImg}',
		'<p class="modifier-name">{modifierName}</p>',
		'<p class="modified-date">{[this.formatDate(values.modifiedDate)]}</p>',
		
		'<tpl if="onlineStatus == 0 || onlineStatus == 1">',
		'<h2>',__('Published'),'</h2>',
		'<a class="published-date">{[this.formatDate(values.publishedDate)]}</a>',
		'<tpl if="values.publishedDate">',
			' <a class="remove-published-date" title="',__('Delete'),'"> </a>',
		'</tpl>',
		'<a class="online-status">',
			__('Online'), ': ',
			'<tpl if="onlineStatus == 0">',
				__('no'),
			'</tpl>',
			'<tpl if="onlineStatus == 1">',
				__('yes'),
			'</tpl>',
		'</a>',
		'</tpl>',
		'<div class="copyright">', 'Garp &copy {[Garp.renderers.yearRenderer(new Date())]} by ', 
				'<a href="http://grrr.nl/" target="_blank">', 'Grrr', '</a><br>version 3.{[GARP_VERSION]}', 
		'</div>',
		{
			compiled: true,
			formatDate: function(v){
				if(v && v.substr(0,4) == '0000'){
					return '<i>' + __('Invalid date') + '</i>';
				}
				return v ? Garp.renderers.intelliDateTimeRenderer(v) : '<i>' + __('No date specified') + '</i>';
			}
		}
	),
	

	/**
	 * Finds record information accross User and currentModel. Then display that using this updateData
	 * @param {Object} rec
	 * @param {Object} formpanel
	 */
	processData: function(rec, fp){
		
		this.rec = rec;
		
		this.updateData({
			createdDate: rec.get('created'),
			modifiedDate: rec.get('modified'),
			publishedDate: rec.get('published'),
			onlineStatus: rec.get('online_status')
		});
		
		if (rec.get('author_id') && Garp['User'].fetch) {
			Garp['User'].fetch({
				'query': {
					'id': rec.get('author_id')
				}
			}, function(data){
				if (Ext.isArray(data.rows)) {
					var rec = data.rows[0];
					if (!rec) {
						return;
					}
					var img = !Ext.isEmpty(rec.image_id) ? Garp.renderers.imageRelationRenderer(rec.image_id) : '';
					var name = Garp.renderers.fullNameConverter(true, rec);
					this.updateData({
						authorName: name,
						authorImg: img
					});
				}
			}, this);
		} else {
			this.updateData({
				authorName: '',
				authorImg: ''
			});
		}
		if (rec.get('modifier_id') && Garp['User'].fetch) {
			Garp['User'].fetch({
				'query': {
					'id': rec.get('modifier_id')
				}
			}, function(data){
				if (Ext.isArray(data.rows)) {
					var rec = data.rows[0];
					if (!rec) {
						return;
					}
					var img = !Ext.isEmpty(rec.image_id) ? Garp.renderers.imageRelationRenderer(rec.image_id) : '';
					var name = Garp.renderers.fullNameConverter(true, rec);
					this.updateData({
						modifierName: name,
						modifierImg: img
					});
				}
			}, this);
		} else {
			this.updateData({
				modifierName: '',
				modifierImg: ''
			});
		}
	},


	buildEditors: function(){
		var cfg = {
			field: {
				xtype: 'xdatetime',
				width: 180,
				timeWidth: 60,
				dateFormat: 'j M Y',
			},
			offsets: [0,-6],
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
				//typeAhead: true,
				//editable: true,
				displayField: 'fullname',
				listeners: {
					'select': function(v){
						if (v && v.selected) {
							this.setVal('author_id',v.selected.id);
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
					this.setVal('created', v);
				},
				scope: this
			}
		}, cfg));
		this.publishedDateEditor = new Ext.Editor(Ext.apply({}, {
			listeners: {
				'beforecomplete': function(e, v){
					this.setVal('published', v);
				},
				scope: this
			},
		}, cfg));
		this.onlineStatusEditor = new Ext.Editor(Ext.apply({}, {
			listeners: {
				'beforecomplete': function(e, v){
					this.setVal('online_status', v);
				},
				scope: this
			},
			offsets: [36, -6],
			hideEl: false,
			field: {
				width: 50,
				xtype: 'combo',
				triggerAction: 'all',
				editable: false,
				forceSelection: true,
				store: [['1', __('yes')], ['0', __('no')]]
			}
		}, cfg));
	},

	/**
	 * Sets up Ext Editors to edit this metaPanel
	 */
	setupEditors: function(){
		this.el.select('.author-name').on('click', function(e, el){
			this.authorIdEditor.startEdit(el, this.rec.get('author_id'));
			this.authorIdEditor.field.triggerFn();
			this.authorIdEditor.el.hide();
		}, this);
		this.el.select('.online-status').on('click', function(e, el){
			this.onlineStatusEditor.startEdit(el, this.rec.get('online_status'));
		}, this);
		this.el.select('.created-date').on('click', function(e, el){
			this.createdDateEditor.startEdit(el, this.rec.get('created'));
		}, this);
		this.el.select('.published-date').on('click', function(e, el){
			this.publishedDateEditor.startEdit(el, this.rec.get('published'));
		}, this);
		this.el.select('.remove-published-date').on('click', function(e, el){
			this.setVal('published', null);
		}, this);
	},

	/**
	 * init 
	 * @param {Object} parent container
	 */
	initComponent: function(ct){
		
		this.buildEditors();
		
		Garp.MetaPanel.superclass.initComponent.call(this, ct);
		this.on({
			'loaddata': {
				fn: this.processData,
				scope: this,
				buffer: 250
			}
		});
		this.on('syncdata',this.updateData.createDelegate(this, [this.data]));
		/*
		
		 If we want to give intelliDateTimeRenderer 'a few seconds ago' :
		  
		this.on('render', function(){
			this.syncdataTimer = new Ext.util.TaskRunner();
			this.syncdataTimer.start({
				run: this.fireEvent.createDelegate(this, ['syncdata']),
				interval: 1000 * 20 // 20 seconds
			});
		}, this);
		*/
	},
	
	
});