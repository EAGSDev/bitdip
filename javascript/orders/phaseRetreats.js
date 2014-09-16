
// See doc/javascript.txt for information on JavaScript in webDiplomacy

function loadOrdersPhase() {


	MyOrders.map(function(OrderObj) {
			OrderObj.updaterequirements = function () {
				var oldrequirements = this.requirements;

				if( this.type == 'Disband')
					this.requirements=['type'];
				else
					this.requirements=['type','toTerrID'];

				this.wipe(oldrequirements.reject(function(r){return this.requirements.member(r);},this));

			};

			OrderObj.updateTypeChoices = function () {
				this.typeChoices = $H({'Retreat':l_t('retreat'),'Disband':l_t('disband')});
				return this.typeChoices;
			};

			OrderObj.updateToTerrChoices = function () {
				if( this.type == 'Disband' )
				{
					this.toTerrChoices = undefined;
					return;
				}

				this.toTerrChoices = this.Unit.getMovableTerritories().select(function(t){

					if( !Object.isUndefined(t.coastParent.standoff) && t.coastParent.standoff )
						return false;
					else if ( !Object.isUndefined(t.coastParent.Unit) )
						return false;
					else if ( this.Unit.Territory.coastParent.occupiedFromTerrID == t.coastParent.id )
						return false;
					else
						return true;
				},this).pluck('id').uniq();

				this.toTerrChoices=this.arrayToChoices(this.toTerrChoices);

				return this.toTerrChoices;
			};

			OrderObj.beginHTML = function () {
				return l_t('The %s at %s ',l_t(this.Unit.type.toLowerCase()),l_t(this.Unit.Territory.name));
			};
			OrderObj.typeHTML = function () {
				return this.formDropDown('type',this.typeChoices,this.type);
			};
			OrderObj.toTerrHTML = function () {
				var toTerrID=this.formDropDown('toTerrID',this.toTerrChoices,this.toTerrID);

				if( toTerrID == '' ) return '';
				else return l_t(' to %s ',toTerrID); // toTerrID comes from the already translated choices.
			};

			OrderObj.updateFromTerrChoices = OrderObj.fNothing;
			OrderObj.updateViaConvoyChoices = OrderObj.fNothing;
			OrderObj.fromTerrHTML = OrderObj.fNothing;
			OrderObj.viaConvoyHTML = OrderObj.fNothing;

			OrderObj.load();
		});
};
