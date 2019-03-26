/****************************************************
 *
 * CKEditor Extension
 *
 * Written by Larix Kortbeek for Grrr
 *
 *****************************************************/
Ext.form.CKEditor = function(config) {
    this.config = config;


    config.CKEditor = window.WYSIWYG_CKEDITOR_CONFIG || {
        // Allow only these tags (=true for all of them)
        allowedContent: true,
        customConfig: '',
        format_tags: 'p;h2;h3',

        // Available buttons
        toolbar: [
            ['Bold', 'Italic', '-', 'RemoveFormat'],
            ['Link', 'Unlink'],
            ['NumberedList', 'BulletedList', 'Blockquote', 'Format'],
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo', '-', 'Source', '-', 'CharCount']
        ],

        // Disable CKEditor's own image plugin
        removePlugins: 'image'
    };

    // Load the site's own styling
    if (window.WYSIWYG_CSS_URL) {
        config.CKEditor.contentsCss = window.WYSIWYG_CSS_URL;
    }

    config.CKEditor.height = "400px";
    config.CKEditor.maxLength = config.maxLength || 0;

    var extraPlugins = 'charcount,garpctrlenter';

    // Load the garp content plugins for richwyswig editor types
    if (config.rich) {
        // Always load the images picker
        extraPlugins += ",garpimages";
        var richButtons = ["Garpimage"];

        // Only load the video picker when a VIDEO_WIDTH template is defined
        if (typeof VIDEO_WIDTH !== 'undefined') {
            extraPlugins += ",garpvideos";
            richButtons.push("Garpvideo");
        }

        // Let the editor know
        config.CKEditor.toolbar.push(richButtons);
    }

    config.CKEditor.extraPlugins = extraPlugins;

    // Attach to Ext
    Ext.form.CKEditor.superclass.constructor.call(this, config);
};

Ext.extend(Ext.form.CKEditor, Ext.form.TextArea, {

    adjustEditorSize: function() {
        if (!this.editor || this.waitingForSetData) {
            return;
        }
        var w = this.itemCt.getWidth() - 125 - 30; // 125px padding and 30px margin
        this.editor.resize(w, parseInt(this.config.CKEditor.height, 10));
    },

    onRender: function(ct, position) {
        Ext.form.CKEditor.superclass.onRender.call(this, ct, position);
        var ckLoaded = function() {
            this.editor = CKEDITOR.replace(this.id, this.config.CKEditor);
            // Closure for quick access in the event listener
            var scope = this;
            this.editor.on('dataReady', function() {
                this.resetDirty();
                scope.waitingForSetData = false;
                scope.on('resize', scope.adjustEditorSize);
                scope.adjustEditorSize(this);
            });
            this.setValue(this.orgValue);
        };
        if (typeof CKEDITOR === 'undefined') {
            Ext.Loader.load([ASSET_URL + 'js/garp/ckeditor/ckeditor.js'], ckLoaded, this);
            return;
        }
        ckLoaded.call(this);
    },

    isValid: function(value) {
        var charCount = this.getCharCount();
        if (!this.editor) {
            return true;
        }

        if (!this.allowBlank && !charCount) {
            if (this.wasBlank) {
                return false;
            }
            this.wasBlank = true;
            this.editor.element.addClass('invalid');
            this.markInvalid(this.blankText);
            return false;
        }
        this.wasBlank = false;

        if (this.maxLength && charCount >= this.maxLength) {
            if (this.wasTooLong) {
                return false;
            }
            this.wasTooLong = true;
            this.editor.element.addClass('invalid');
            this.markInvalid(this.maxLengthText);
            return false;
        }
        this.wasTooLong = false;

        this.clearInvalid();
        return true;
    },

    // Get char count, stripped of HTML tags
    getCharCount: function() {
        var contentString = "";
        try {
            contentString = this.editor.document.getBody().getText();
        } catch(e) {
            contentString = this.getValue().replace(/(<([^>]+)>)/ig,"");
        }
        // Trim newlines and count
        return contentString.replace(/^\s+|\s+$/g, '').length;
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
