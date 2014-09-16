
// See doc/javascript.txt for information on JavaScript in webDiplomacy

// Load basic data into classes
function loadBoard()
{
	MyUnits = new Array();

	var ProtoTerritory=new TerritoryClass();
	var ProtoUnit=new UnitClass();

	// Link coastal sub-territories with their parent territories
	Territories.each(function(p){
		var t=p[1];

		Object.extend(t, ProtoTerritory);

		t.prepare();
	});

	// Load units into territories, and territories into units, and create MyUnits list
	TerrStatus.map(function(ts) {
		var t=Territories.get(ts.id);
		Object.extend(t, ts);

		if( t.unitID != null )
		{
			var u = Units.get(ts.unitID);
			Object.extend(u, ProtoUnit);

			t.unitID = u.id;
			t.Unit = u;
			if( u.terrID == t.id )
				u.Territory = t;
			else
				u.Territory = Territories.get(u.terrID);

			if( u.countryID == context.countryID )
				MyUnits.push(u);
		}
	},this);

	if( context.phase == 'Diplomacy' )
	{
		// Load and initialize ConvoyGroups
		CGs=new Array();
		Units.values().map(function(f) {
			if( f.type == 'Fleet' && f.Territory.type=='Sea' )
			{
				var CG=new ConvoyGroupClass();
				CG.loadFleet(f);
				CG.loadCoasts();
				CGs.push(CG);
			}
		},this);
		CGs.map(function(CG){CG.linkGroups();},this);
		CGs.map(function(CG){CG.prepare();},this);
	}
	else if( context.phase == 'Retreats' )
	{
		// Find retreating units
		RetreatingUnits = new Array();

		Units.each(function(p) {
			var u=p[1];

			// Retreating units don't yet have any Territory set
			if( Object.isUndefined(u.Territory) )
			{
				var unit = Units.get(u.id);
				Object.extend(unit, ProtoUnit);

				unit.Territory = Territories.get(unit.terrID);

				RetreatingUnits.push(unit);

				if( unit.countryID == context.countryID )
					MyUnits.push(unit);
			}
		},this);
	}
	else if( context.phase == 'Builds' )
	{
		// Find supply centers belonging to the current user
		SupplyCenters = new Array();

		Territories.each(function(p){
			var t=p[1];
			if( t.coastParent.supply && t.coastParent.countryID == context.countryID && t.coastParent.ownerCountryID == context.countryID && Object.isUndefined(t.coastParent.Unit) )
			{
				SupplyCenters.push(t);
			}
		},this);
	}
}