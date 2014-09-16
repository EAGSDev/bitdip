
// See doc/javascript.txt for information on JavaScript in webDiplomacy

function openThread(threadID) {

}

function replyBox(threadID) {

}

function showThreadsParticipated() {

}

function likeMessageToggle(userID, messageID, token) {
	$("likeMessageToggleLink"+messageID).hide();

	new Ajax.Request('ajax.php?likeMessageToggleToken='+token,
		{
			method: 'get', asynchronous : true,
			onFailure: function(response) {
				$("likeMessageToggleLink"+messageID).show();
			},
			onSuccess: function(response) {

			}
		}
	);
}