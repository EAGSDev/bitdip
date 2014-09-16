// See doc/javascript.txt for information on JavaScript in webDiplomacy

// Current turn, -2 is undefined, -1 is pre-game
var turn=-2;

// Increment or decrement the turn safely, factoring in the limits, then load the new turn
function loadMapStep(gameID, currentTurn, step)
{
	var oldTurn = turn;

	if( turn==-2 ) turn=currentTurn; // Initializing, display current turn

	turn += step;

	// Respect limits
	if ( turn < -1 )
		turn = -1;
	else if ( turn > currentTurn )
		turn = currentTurn;

	// Turn has changed
	if( oldTurn != turn )
		loadMap(gameID, currentTurn, turn);
}

// Update the map arrows for the new turn, making the disabled arrows gray
function mapArrows(currentTurn, newTurn)
{
	if ( newTurn == -1 )
	{
		$('Start').src = l_s("images/historyicons/Start_disabled.png");
		$('Backward').src = l_s("images/historyicons/Backward_disabled.png");
	}
	else
	{
		$('Start').src = l_s("images/historyicons/Start.png");
		$('Backward').src = l_s("images/historyicons/Backward.png");
	}

	// Draw the greyed icons if the user can go no further forward
	if ( newTurn == currentTurn )
	{
		$('Forward').src = l_s("images/historyicons/Forward_disabled.png");
		$('End').src = l_s("images/historyicons/End_disabled.png");
	}
	else
	{
		$('Forward').src = l_s("images/historyicons/Forward.png");
		$('End').src = l_s("images/historyicons/End.png");
	}
}
turnToText='';//() { return ''; }

// Load the map for the specified turn, refresh arrows. Assumes newTurn is valid, sets turn=newTurn
function loadMap(gameID, currentTurn, newTurn)
{
	turn=newTurn;

	// Draw the greyed icons if the user can go no further back
	mapArrows(currentTurn, newTurn);

	// Display the current date being viewed
	if( turn == currentTurn )
		$('History').hide(); // .. if viewing an old turn
	else
	{
		$('History').innerHTML = turnToText(turn);

		$('History').show();
	}

	// Update the link to the large map
	$('LargeMapLink').innerHTML =
			' <a href="map.php?gameID='+gameID+'&turn='+newTurn+'&mapType=large" target="blank" class="light">'+
			'<img src="'+l_s('images/historyicons/external.png')+'" alt="'+l_t('Open large map')+'" ' +
			'title="'+l_t('This button will open the large map in a new window. The large ' +
			'map shows all the moves, and is useful when the small map isn\'t clear enough.')+'" /><\/a>';

	// Update the source for the map image
	$('mapImage').src = 'map.php?gameID='+gameID+'&turn='+newTurn;
}