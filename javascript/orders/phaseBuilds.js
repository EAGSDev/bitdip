
// See doc/javascript.txt for information on JavaScript in webDiplomacy

function loadOrdersPhase() {


	MyOrders.map(function(OrderObj) {
			OrderObj.updaterequirements = function () {

				var oldrequirements = this.requirements;

				if( this.type == 'Wait')
					this.requirements=['type'];
				else
					this.requirements=['type','toTerrID'];

				this.wipe(oldrequirements.reject(function(r){return this.requirements.member(r);},this));

			};

			OrderObj.updateTypeChoices = function () {
				switch(this.type)
				{
					case 'Build Army':
					case 'Build Fleet':
					case 'Wait':
						this.typeChoices = $H({'Build Army':l_t('Build an army'),
									'Build Fleet':l_t('Build a fleet'),
									'Wait':l_t('Wait/Postpone build.')});
						break;
					case 'Destroy':
						this.typeChoices = $H({'Destroy':l_t('Destroy a unit')});
				}

				return this.typeChoices;
			};

			OrderObj.updateToTerrChoices = function () {
				switch( this.type )
				{
					case 'Wait':
						this.toTerrChoices = undefined;
						return;
					case 'Build Army':
					case 'Build Fleet':
						this.toTerrChoices = SupplyCenters.select(function(sc){
							if( this.type=='Build Army' && ( sc.coast=='Parent'||sc.coast=='No') )
								return true;
							else if ( this.type=='Build Fleet' && ( sc.type != 'Land' && sc.coast!='Parent' ) )
								return true;
							else
								return false;
						},this).pluck('id');
						break;
					case 'Destroy':
						this.toTerrChoices = MyUnits.pluck('Territory').pluck('coastParent').pluck('id');
						break;
				}

				this.toTerrChoices=this.arrayToChoices(this.toTerrChoices);

				return this.toTerrChoices;
			};

			OrderObj.updateFromTerrChoices = OrderObj.fNothing;
			OrderObj.updateViaConvoyChoices = OrderObj.fNothing;

			OrderObj.beginHTML = OrderObj.fNothing;
			OrderObj.typeHTML = function () {
				return this.formDropDown('type',this.typeChoices,this.type);
			};
			OrderObj.toTerrHTML = function () {
				var toTerrID=this.formDropDown('toTerrID',this.toTerrChoices,this.toTerrID);
				if(toTerrID=='') return '';
				else return ' at '+toTerrID;
			};

			OrderObj.fromTerrHTML = OrderObj.fNothing;
			OrderObj.viaConvoyHTML = OrderObj.fNothing;

			OrderObj.load();
		});
};
