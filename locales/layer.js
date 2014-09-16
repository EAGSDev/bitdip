

// Substitute text
function l_t(text) {

	var args = [];

	if( arguments.length > 1 ) {
		args = $A(arguments);

		args.shift();
	}

	return Locale.text(text, args);
}
// Substitute static file
function l_s(file) {
	return Locale.staticFile(file);
}

Locale = {
		// Run immidiately after the translation layer JS has been loaded
		initialize : function () {

		},

		// Run before all other webDip scripts
		onLoad : function () {

		},

		// Run after all other webDip scripts
		afterLoad : function () {
		},

		// Text substitution
		text : function (text, args) {
			if( this.textLookup.keys().include(text) ) {
				text = this.textLookup.get(text);
			} else {
				this.failedLookup(text);
			}

			return vsprintf(text, args);
		},

		// Static file substitution
		staticFile : function (file) {
			return file;
		},

		// The text lookup table, usually set via e.g. Italian/lookup.js
		textLookup : $H({}),

		// Report a failure, if in debug mode
		reportFailure: function(failure) {
			if( WEBDIP_DEBUG ) {
				$('jsLocalizationDebug').insert(text+'<br />');
			}
		},

		// Collected failed lookups
		failedLookups : $A(),

		// Reporting a failed lookup (log it if in debug mode)
		failedLookup : function(text) {
			if( WEBDIP_DEBUG && !this.failedLookups.include(text) ) {
				this.failedLookups.push(text);
			}
		}
};
