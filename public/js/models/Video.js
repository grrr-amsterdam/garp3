Ext.ns('Garp.dataTypes');
Garp.dataTypes.Video = new Garp.DataType({
	iconCls: 'icon-video',
	text: 'Video',
	
	defaultData: {
		id: null,
		name: null,
		url: null,
		description: null,
		player: null,
		duration: null,
		tags: null,
		author: null,
		image: null,
		thumbnail: null,
		created: null,
		modified: null
	},
	
	columnModel: [{
		header: __('Name'),
		dataIndex: 'name',
		sortable: true
	}, {
		header: __('Url'),
		dataIndex: 'url',
		sortable: true,
		hidden: true
	}, {
		header: __('Image'),
		dataIndex: 'image',
		hidden: true,
		renderer: Garp.renderers.imageRenderer
	}, {
		header: __('Description'),
		dataIndex: 'description',
		hidden: true,
		renderer: Garp.renderers.htmlRenderer
	},{
		header: __('Thumbnail'),
		dataIndex: 'thumbnail',
		renderer: Garp.renderers.imageRenderer
	}, {
		header: 'Created',
		dataIndex: 'created',
		hidden: true,
		sortable: true,
		renderer: Garp.renderers.dateTimeRenderer
	}, {
		header: 'Modified',
		dataIndex: 'modified',
		hidden: true,
		sortable: true,
		renderer: Garp.renderers.dateTimeRenderer
	}],
	
	formConfig: [{
		layout: 'form',
		defaults: {
			defaultType: 'textfield'
		},
		listeners: {
			'loaddata': function(rec, formPanel){
				function updateUI(){
					if (rec.phantom) {
						formPanel.preview.update({
							width: 0,
							height: 0,
							player: ''
						});
					} else {
						var w = formPanel.preview.getWidth();
						var h = Math.floor((9 * w) / 16);
						if (rec.get('player')) {
							formPanel.preview.update({
								width: w,
								height: h,
								player: rec.get('player')
							});
						}
					}
				}
				if (formPanel.rendered) {
					updateUI.defer(500);
				} else {
					formPanel.on('show', updateUI, null, {
						single: true
					});
				}
			}
		},
		items: [{
			xtype: 'fieldset',
			items: [{
				name: 'id',
				hideFieldLabel: true,
				disabled: true,
				xtype: 'numberfield',
				hidden: true
			}, {
				name: 'url',
				fieldLabel: __('Url'),
				allowBlank: false
			}, {
				xtype: 'box',
				cls: 'garp-notification-boxcomponent',
				style: 'margin-top: -1em',
				html: __('YouTube and Vimeo Url\'s are supported'),
				fieldLabel: ' '
			},{
				name: 'name',
				fieldLabel: __('Name')
			}, {
				name: 'description',
				fieldLabel: __('Description'),
				xtype: 'richtexteditor',
				enableMedia: false,
				enableHeading: false,
				enableBlockQuote: false,
				enableLists: false,
				enableSourceEdit: false,
				height: 160
			}, {
				xtype: 'box',
				ref: '../../../preview',
				fieldLabel: __('Preview'),
				data: {
					player: '',
					width: 0,
					height: 0
				},
				tpl: Garp.videoTpl
			}, {
				xtype: 'box',
				cls: 'separator'
			}, {
				name: 'tags',
				fieldLabel: __('Tags'),
				xtype: 'displayfield',
				style: 'max-height: 18px;overflow:hidden;'
			}, {
				name: 'author',
				fieldLabel: __('Author'),
				xtype: 'displayfield'
			}]
		}]
	}]
});
