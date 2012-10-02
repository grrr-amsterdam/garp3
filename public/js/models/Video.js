Ext.ns('Garp.dataTypes');
Garp.dataTypes.Video.on('init', function(){

	// Thumbnail column:
	this.insertColumn(0, {
		header: '<span class="hidden">' + __('Thumbnail') + '</span>',
		width: 84,
		fixed: true,
		dataIndex: 'thumbnail',
		renderer: Garp.renderers.imageRenderer
	});
	
	// Remove 'required' fields that have nothing to do with the UI:
	//this.removeField('identifier');
	//this.removeField('type');
	
	
	// Move url field:
	var urlField = this.getField('url');
	this.removeField('url');
	this.insertField(1, urlField);
	// ..and put some infotexts near it:
	this.insertField(2, {
		xtype: 'box',
		cls: 'garp-notification-boxcomponent',
		html: __('YouTube and Vimeo Url\'s are supported'),
		fieldLabel: ' '
	});
	
	// Preview & other fields:
	this.addFields([{
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
		allowBlank: true,
		style: 'max-height: 18px;overflow:hidden;'
	}, {
		name: 'video_author',
		fieldLabel: __('Author'),
		allowBlank: true,
		xtype: 'displayfield'
	}]);
	
	this.getField('player').hidden = true;
	
	this.addListener('loaddata', function(rec, formPanel){
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
		// We want to calculate the width. If we're to early, we can get strange values for width. First let ExtJS doLayout's get run:
		if (formPanel.rendered) {
			updateUI.defer(500);
		} else {
			formPanel.on('show', updateUI, null, {
				single: true
			});
		}
	});
	
	/*
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
	 header: '<span class="hidden">' + __('Thumbnail') + '</span>',
	 width: 84,
	 fixed: true,
	 dataIndex: 'thumbnail',
	 renderer: Garp.renderers.imageRenderer
	 }, {
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
	 renderer: Garp.renderers.imageRelationRenderer
	 }, {
	 header: __('Description'),
	 dataIndex: 'description',
	 hidden: true,
	 renderer: Garp.renderers.htmlRenderer
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
	 xtype: 'textfield',
	 allowBlank: false
	 }, {
	 xtype: 'box',
	 cls: 'garp-notification-boxcomponent',
	 //style: 'margin-top: -1em;',
	 html: __('YouTube and Vimeo Url\'s are supported'),
	 fieldLabel: ' '
	 },{
	 name: 'name',
	 xtype: 'textfield',
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
	 }]*/
});
