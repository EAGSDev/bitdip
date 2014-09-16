
// See doc/javascript.txt for information on JavaScript in webDiplomacy

/*
 * Filter out unwanted text from certain players and countries
 */

function muteAll() {
	muteUsers.map(function(m) {
		$$(".userID"+m).map(function(mt) {
			mt.hide();
		});
	});
	muteCountries.map(function(m) {
		$$(".gameID"+m[0]+"countryID"+m[1]).map(function(mt) {
			mt.hide();
		});
	});
	muteThreads.map(function(m) {
		$$(".threadID"+m).map(function(mt) {
			mt.hide();
		});
	});
}