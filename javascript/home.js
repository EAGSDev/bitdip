
// See doc/javascript.txt for information on JavaScript in webDiplomacy

/*
 * For each game panel on the home page add event handlers so that unrelated game panels and
 * notices will be hidden/faded when the mouse is over a certain game panel or game link.
 */
function homeGameHighlighter() {

	$$("span[gameID].homeGameTitleBar").map(function (gameTitleBar) {
		var gameID = gameTitleBar.getAttribute("gameID");

		// A function to determine if a given element is of the same game
		var isUnrelated = function(testElement) {
			var testGameID = testElement.getAttribute("gameID");
			return !( !Object.isUndefined(testGameID) && testGameID==gameID );
		};

		// Unrelated game-panels
		var panelsUnrelated = $$("div[gameID].gamePanelHome").findAll( isUnrelated );
		// Unrelated game-notices
		var noticesUnrelated = $$("div.homeNotice").findAll( isUnrelated );

		// Functions to set/unset transparency
		var fadeOut = function(e) { e.setOpacity(0.5); };
		var fadeIn = function(e) { e.setOpacity(1.0); };

		// Mouse over the game title bar; hide unrelated notices, fade unrelated game panels
		gameTitleBar.onmouseover = function () {
			panelsUnrelated.map(fadeOut);
			noticesUnrelated.invoke('hide');
		};
		gameTitleBar.onmouseout = function () {
			panelsUnrelated.map(fadeIn);
			noticesUnrelated.invoke('show');
		};

		// Mouse over any game link; fade unrelated notices, fade unrelated game panels
		$$('a[gameID="'+gameID+'"]').map(function(gameNoticeLink) {
			gameNoticeLink.onmouseover = function() {
				panelsUnrelated.map(fadeOut);
				noticesUnrelated.map(fadeOut);
			};
			gameNoticeLink.onmouseout = function() {
				panelsUnrelated.map(fadeIn);
				noticesUnrelated.map(fadeIn);
			};
		});

	},this);
}