/*******************************************************************************
Create Date : 11/10/2013
----------------------------------------------------------------------
Plugin name : charcount/wordcount
Version : 2.0
Author : Frommelt Yoann
		 Pete Allison
Description : Character/word counter for CKEditor v 3
********************************************************************************/
CKEDITOR.plugins.add('charcount',{
	init:function(editor){
		var labelCounter = ''
		// Only implement if config.maxLength or config.maxWords has been defined
		if (editor.config.maxLength != null || editor.config.maxWords != null) {
			if (editor.maxLength != null)
				labelCounter = editor.lang.labelCharcount ? editor.lang.labelCharcount : 'Character counter';
			else
				labelCounter = editor.lang.labelWordcount ? editor.lang.labelWordcount : 'Word counter';
			
			// Attach the key event to this editor
			addEventOnkey(editor);
			
			// And ensure the button is registered
			editor.ui.addButton('CharCount',{
				label: labelCounter, 
				command:'charcount'
			});
		} 

		
	}
});

/**
 * Attach the keyup event to the editor
 * @param {CKEDITOR} editor <p>The editor to attach the events to.</p>
 */
function addEventOnkey(editor) {
	var initilised = false;
	
	editor.on('contentDom', function() {
		// Perform an initial calculation when the contentDom has just been built
		calculate(editor);

		/*
		 * CKE only exposes the editor.document.on method at first creation of
		 * the editor, however if a setData is called, then all events added in
		 * this manner are erased.  setData will perform a contentDom event
		 * every time it is called, so we must directly hook new listeners to
		 * the recreated document.
		 */
		var editable = editor.editable();
		editable.attachListener(editor.document, 'keyup', function() {
			calculate(editor);
		});
		
		// This will only work if the paste as plain text plugin is available
		if (!initilised)
			editor.on('paste', function() {
				calculate(editor);
			});
		
	/*
	 * So that we don't have to hack around any of the main CKE javascript
	 * files, we need to directly modify the button that's added so that it is
	 * not fixed width and has no icon.
	 * Although not available in some browsers getElementsByClassName is a good
	 * way of doing this.
	 */
	var buttons = document.getElementsByClassName('cke_button__charcount');
	for (button in buttons) {
		if (!isNaN(button)) {
			// We know that each button has two spans - the icon and text
			var spans = buttons[button].getElementsByTagName('span');
			spans[0].style.display = 'none';
			spans[1].style.display = 'inline';
			spans[1].style['padding-left'] = '0px';
		}
	}
	});
  
}

/**
 * This performs the necessary character or word calculation and display
 * @param {CKEDITOR} editor <p>The editor that you wish to count.</p>
 */
function calculate(editor) {
	var buttonLabel = document.getElementById('cke_' + editor.name).getElementsByClassName('cke_button__charcount_label')[0],
		plainText = editor.document.getBody().getText();

	if (editor.config.maxLength != null) {
		// Character count
		if (editor.config.maxLength == 0)
			buttonLabel.innerHTML = plainText.length;
		else 
			buttonLabel.innerHTML = editor.config.maxLength - plainText.length
	} else {
		// Word count
		if (editor.config.maxWords == 0)
			buttonLabel.innerHTML = wordCount(plainText);
		else
			buttonLabel.innerHTML = editor.config.maxWords - wordCount(plainText);
	}
}

/**
 * Counts the number of words within the passed text.
 * @param {String} text
 * @return {Integer}
 */
function wordCount(text) {
	var r = 0,
		item = text.trim();
	
	if (item === '') return 0;
	
	/*
	 * This should remove any html tags if they exist
	 */
	item = item.replace(/(<([^>]+)>)/ig, '');
	
	/*
	 * If using a dot to separate words, prevent it, unless it is within a
	 * number
	 */
	item = item.replace(/\.[^0-9]/g, '. $&');

	/*
	 * Replace any underscores with a space - there should be no reason to
	 * use them
	 */
	item = item.replace(/\_/g, ' ');

	// Replace all multiple spaces with a single space
	a = item.replace(/\s/g, ' ');
	a = a.split(' ');
	for (z = 0; z < a.length; z++) {
		if (a[z].length > 0)
			r++;
	}

	return r;
}
