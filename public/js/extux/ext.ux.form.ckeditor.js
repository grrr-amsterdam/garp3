/****************************************************
 *
 * CKEditor Extension
 *
 * Written by Larix Kortbeek for Grrr
 *
 *****************************************************/
Ext.form.CKEditor = function(config) {
    this.config = config;

    config.CKEditor = {
        // Allow only these tags (=true for all of them)
        allowedContent: true,

        // Available buttons
        toolbar: [
            ['Bold', 'Italic', '-', 'RemoveFormat'],
            ['Link', 'Unlink'],
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo', '-', 'Source']
        ]
    };

    // Load the site's own styling
    if (window.WYSIWYG_CSS_URL) {
        config.CKEditor.contentsCss = window.WYSIWYG_CSS_URL;
    }

    // Load the garp content plugins for richwyswig editor types
    if (config.rich) {
        // Always load the images picker
        var extraPlugins = "garpimages";
        var richButtons = ["Garpimage"];

        // Only load the video picker when a VIDEO_WIDTH template is defined
        if (VIDEO_WIDTH) {
            extraPlugins += ",garpvideos";
            richButtons.push("Garpvideo");
        }

        // Let the editor know
        config.CKEditor.extraPlugins = extraPlugins;
        config.CKEditor.toolbar.push(richButtons);
    }

    // Attach to Ext
    Ext.form.CKEditor.superclass.constructor.call(this, config);
};

Ext.extend(Ext.form.CKEditor, Ext.form.TextArea, {
    onRender: function(ct, position) {
        Ext.form.CKEditor.superclass.onRender.call(this, ct, position);

        this.editor = CKEDITOR.replace(this.id, this.config.CKEditor);

        // Closure for quick access in the event listener
        var that = this;
        this.editor.on('dataReady', function() {
            this.resetDirty();
            that.waitingForSetData = false;
        });
        this.setValue(this.orgValue);
    },

    setValue: function(value) {
        // Save the value as the elements original value
        this.orgValue = value;

        // Wait for an editor (the setValue function will be called on render)
        if (!this.editor) {
            return;
        }
        // Convert undefineds and nulls to empty string
        value = value || "";

        // Working around CKEditor's crazy-assync setData
        // (When setting data twice in short succession only the first data gets set)
        var that = this;

        function retrySetValue(event) {
            that.setValue(that.orgValue);
            that.waitingForSetData = false;
            event.removeListener();
        }
        if (this.waitingForSetData) {
            this.editor.on('dataReady', retrySetValue);
        }
        this.waitingForSetData = true;

        // Set CKEditor's content
        this.editor.setData(value);
    },

    getValue: function() {
        var val = this.orgValue;

        // If an editor is available and content has changed
        if (this.editor && this.editor.checkDirty()) {
            val = this.editor.getData();
        }

        // Convert falsy values to the empty string
        return val || "";
    },

    getRawValue: function() {
        return this.getValue();
    }
});

// Define "Rich CKEditor" (which allows for some more options such as image and video embeds)
Ext.form.RichCKEditor = function(config) {
    config.rich = true;
    Ext.form.RichCKEditor.superclass.constructor.call(this, config);
};
Ext.extend(Ext.form.RichCKEditor, Ext.form.CKEditor);

// Enable the CKEditor as default richtexteditor
Ext.reg('wysiwygeditor', Ext.form.CKEditor);
Ext.reg('richwysiwygeditor', Ext.form.RichCKEditor);
