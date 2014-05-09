CKEDITOR.plugins.add('garpimages', {
	init: function(editor) {
		editor.addCommand('garpimageDialog', {
			exec: function(editor) {
				var command = this;

				// Open Extjs image picker
				var win = new Garp.ImagePickerWindow({});
				win.on('select', function(data) {
					// Insert image element
					var tpl = Garp.imageTpl;

					var imgHtml = tpl.apply({
						path: data.src,
						width: data.template.get('w'),
						height: data.template.get('h'),
						align: data.align,
						caption: data.caption || false
					});
					console.log(imgHtml);
					var element = CKEDITOR.dom.element.createFromHtml(imgHtml);
					editor.insertElement(element);

					// Return to CKEditor
					editor.fire('afterCommandExec', {
						name: command.name,
						command: command
					});
				}, this);
				win.show();

			},
			async: true
		});
		editor.ui.addButton('Garpimage', {
			label: 'Insert Image',
			command: 'garpimageDialog',
            icon: this.path + 'picture.png',
			toolbar: 'insert'
		});
	}
});
