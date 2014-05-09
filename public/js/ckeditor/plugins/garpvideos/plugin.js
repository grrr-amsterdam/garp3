CKEDITOR.plugins.add('garpvideos', {
    init: function(editor) {
        editor.addCommand('garpvideoDialog', {
            exec: function(editor) {
                var command = this;

                // Open Extjs picker
                var win = new Garp.ModelPickerWindow({
                    model: "Video"
                });
                win.on('select', function(data) {
                    // Insert element
                    var html = Garp.videoTpl.apply({
                        player: data.selected.get('player'),
                        width: VIDEO_WIDTH,
                        height: VIDEO_HEIGHT
                    });
                    var element = CKEDITOR.dom.element.createFromHtml(html);
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
        editor.ui.addButton('Garpvideo', {
            label: 'Insert Video',
            command: 'garpvideoDialog',
            icon: this.path + 'video-film.png',
            toolbar: 'insert'
        });
    }
});
