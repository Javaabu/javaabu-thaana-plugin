/*
 * Javascript Thaana Keyboard 4.2.1
 *
 * Copyright (c) 2009 Jawish Hameed (jawish.org)
 * Licensed under the MIT license.
 */

var thaanaKeyboard = {
	
	// Default keyboard (used only when not explicitly declared using state)
	defaultKeyboard: '',
	
	// Setup Ascii Thaana -> Unicode translation matrix
	_transFrom: 'qwertyuiop[]\\asdfghjkl;\'zxcvbnm,./QWERTYUIOP{}|ASDFGHJKL:\"ZXCVBNM<>?()',
	_transToKbd: {	'phonetic': 'ްއެރތޔުިޮޕ][\\ަސދފގހޖކލ؛\'ޒ×ޗވބނމ،./ޤޢޭޜޓޠޫީޯ÷}{|ާށޑﷲޣޙޛޚޅ:\"ޡޘޝޥޞޏޟ><؟)(',
			'phonetic-hh': 'ޤަެރތޔުިޮޕ][\\އސދފގހޖކލ؛\'ޒޝްވބނމ،./ﷲާޭޜޓޠޫީޯޕ}{|ޢށޑޟޣޙޛޚޅ:\"ޡޘޗޥޞޏމ><؟)(',
			'typewriter': 'ޫޮާީޭގރމތހލ[]ިުްަެވއނކފﷲޒޑސޔޅދބށޓޯ×’“/:ޤޜޣޠޙ÷{}<>.،\"ޥޢޘޚޡ؛ޖޕޏޗޟޛޝ\\ޞ؟)('
			},
	
	// Default class name used for hooking at initialization
	_className: 'thaanaKeyboardInput',
	
	
	/**
	 * Apply input handling to elements with the special JTK class name
	 */
	init: function () {
		this.setHandlerByClass(this._className, 'enable');
	},
	
	/**
	 * Set key event handlers for elements with the specified id
	 * 
	 * @param id		String id of the element to affect
	 * @param action	String specifying action to take. Should be 'enable' or 'disable'.
	 */
	setHandlerById: function (id, action) {
		// Get the element object
		var elem = document.getElementById(id);
		
		// Respond to action specified
		switch (action) {
			case 'enable':
				// Enable input handling
				elem.onkeypress = this.handleKey;
				
				break;
				
			case 'disable':
				// Disable input handling
				elem.onkeypress = null;
				
				break;
		}
	},
	
	/**
	 * Set key event handlers for elements of the specified class
	 * 
	 * @param clsname	String class name of the elements to affect
	 * @param action	String specifying action to take. Should be 'enable' or 'disable'.
	 */
	setHandlerByClass: function (clsname, action) {
		// Setup regular expression for finding the target class
		var rePat = new RegExp('\\b' + clsname + '\\b');
		
		// Get all the elements on page
		var elemList = document.all || document.getElementsByTagName('*');
		
		// Loop through the elements
		for (n = 0; n < elemList.length; n++) {
			// Ensure the current element is of the target class
			if (rePat.test(elemList[n].className)) {
				// Set handler
				this.setHandlerById(elemList[n].id, action);
			}
		}
	},
	
	/**
	 * Do character translation on key activity
	 */
	handleKey: function (e) {		
		// Get the event data
		if (!e) var e = window.event;
		
		// Get the pressed key
		if (e.which == null) {
			// IE:
			keycode = e.keyCode;
		}
		else if (e.which > 0) {
			// Non-IE printable chars:
			keycode = e.which;
			
			// Handle special keys misfire in Opera
			if (window.opera && [45, 46, 35, 36].indexOf(keycode) == 1) return true;
		}
		else {
			// Ignore special keys
			return true;
		}
		
		// Check for CTRL modifier key
		if (e.modifier) {
			var ctrl = e.modifiers & Event.CONTROL_MASK;
		}
		else if (typeof(e.ctrlKey) != 'undefined') {
			var ctrl = e.ctrlKey;
		}
		
		// Get translation details for the entered char
		try {
			var transIndex = thaanaKeyboard._transFrom.indexOf(String.fromCharCode(keycode));
			
			// If pressed key does not require translation, let default action proceed
			if (transIndex == -1 || ctrl) return true;
			
			
			// Set default state
			var state = thaanaKeyboard.defaultKeyboard;
			
			// Get state
			var elemState = document.getElementsByName(this.id + '_thaanaKeyboardState');
			if (elemState) {
				// State element has been defined:
				
				// Get the state specified by the element
				for (i = 0; i < elemState.length; i++) {
					if (elemState[i].type == 'radio' || elemState[i].type == 'checkbox') {
						// Handle radio/checkbox fields
						if (elemState[i].checked == true) {
							state = elemState[i].value;
							break;
						}
					}
					else if (elemState[i].type == 'select-one') {
						// Handle select/combo list fields
						state = elemState[i].options[elemState[i].selectedIndex].value;
						break;
					}
					else if (elemState[i].type == 'hidden' || elemState[i].type == 'text') {
						// Handle hidden and text fields
						state = elemState[i].value;
						break;
					}
					
				}
			}
			
			// Look up the translated char
			var transChar = thaanaKeyboard._transToKbd[state].substr(transIndex, 1);
		}
		catch (err) {
			// Incorrect type or type not defined, return immediately
			return;
		}
		
		// Cancel default action for the key
		if (typeof e.preventDefault == 'function') {
			e.preventDefault();
		}
		else {
			e.returnValue = false;
		}
		
		// Store scroll position for textarea
		var sPos = this.scrollTop;
		
		// Insert text at cursor position
		if (this.selectionStart) {
			// For Firefox/Safari:
			var selOld = this.selectionStart + 1;
			this.value = this.value.substring(0, this.selectionStart) 
						+ transChar 
						+ this.value.substring(this.selectionEnd, this.value.length);
			
			this.setSelectionRange(selOld, selOld);
			this.focus();
		}
		else if (document.selection) {
			// For IE/Opera:
			sel = document.selection.createRange();
			sel.text = transChar;
			this.focus();
		}
		else {
			// If all fails, just append
			this.value += transChar;
		}
		
		// Restore scroll position for textarea
		this.scrollTop = sPos;
	}

};

// Start the Thaana Keyboard soon as the page is loaded
var old = (window.onload) ? window.onload : function () {};
window.onload = function(){old(); thaanaKeyboard.init();};