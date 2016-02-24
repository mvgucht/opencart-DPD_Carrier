// Main Object 'DPD' used as namespace 
var DPD = new function(){

	// ================================= //
	// ===== 1000 HELPER FUNCTIONS ===== //
	
	// 1001: Private function to make a string capital first
	var capitaliseFirstLetter = function(string) {
		return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
	}
	
	// 1002: Private function to create an xmlhttp object for different browsers
	var selectXMLHttpObj = function(){
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			return new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			return new ActiveXObject("Microsoft.XMLHTTP");
		}
	}

	// 1003: Private function to populate the objects dictionary
	var populateDictionary = function(fileUrl, language){
		var result = Array();
		
		// Load the dictionary
		var xmlhttp = selectXMLHttpObj();
		xmlhttp.open("GET", fileUrl, false);
		xmlhttp.send();
		// Temp variables to loop over xml data
		var tmpDictionaryXML = xmlhttp.responseXML;
		var tmpEntries = tmpDictionaryXML.getElementsByTagName('entry');
		
		// For each entry in the xml file
		for (var i = 0, entry; entry = tmpEntries[i]; i++) {
			var entryID = entry.getAttribute("id");	// we get the ID
			var currData = entry.getElementsByTagName(language); // we get the translation according to the language code
			if (currData.length == 0 && language.length == 5) {	// if the translation is not found and the language code is 5 chars long we try the default value eg: 'nl_BE' ==> 'nl'
				currData = entry.getElementsByTagName(language.substring(0,2));
			}
			if (currData.length == 0) { // If there is still no data we use the default language 'en'
				currData = entry.getElementsByTagName('en');
			}
			if (currData.length == 0) { // And if there is still no data we fill the result with 'translation not found'.
				result[entryID] = 'Translation not found';
			} else {
				result[entryID] = currData[0].textContent;
			}
		}
		
		// Clear temp variables
		var tmpDictionaryXML;
		var tmpEntries;
		
		return result;
	}
	// === 1000 END HELPER FUNCTIONS === //
	// ================================= //
	
	// ============================ //
	// ===== 2000 SUB OBJECTS ===== //
	
	// 2001: Public object 'locator'
	// Will render a google map in an allocated container with the nearest shops to a given address.
	this.locator = function(objConfig){
		// Check mandatory fields
		// We need a containerId to know where to place the map
		if(typeof objConfig.containerId == 'undefined') throw "containerId is mandatory";
		
		// ================================= //
		// ===== 2100 HELPER FUNCTIONS ===== //
		
		// 2101: Private function to translate placeholders, feel free to link this to your own database.
		var t = function(stringID){
			return dictionary[stringID];
		}
		
		// 2102: Private function to map weekdays to translation ID.
		// Data in webservice response uses day names.
		var wdt = function(string){
			switch(string){
				case "Monday":
					return 3;
					break;
				case "Tuesday":
					return 4;
					break;
				case "Wednesday":
					return 5;
					break;
				case "Thursday":
					return 6;
					break;
				case "Friday":
					return 7;
					break;
				case "Saturday":
					return 8;
					break;
				case "Sunday":
					return 9;
					break;
			}
		}
		
		// 2103: Private function to render markers (location + DPD markers depending on places)
		var renderMarkers = function (places){
			// Remove the previous markers
			for (var i = 0, marker; marker = markers[i]; i++) {
				marker.setMap(null);
			}
			for (var i = 0, DPDmarker; DPDmarker = DPDmarkers[i]; i++) {
				DPDmarker.setMap(null);
			}
			
			// and clear the lists.
			markers = [];
			DPDmarkers = [];
			DPDShops = [];
			
			var bounds = new google.maps.LatLngBounds();
			for (var i = 0, place; place = places[i]; i++) {
				var image = {
					url: place.icon,
					size: new google.maps.Size(71, 71),
					origin: new google.maps.Point(0, 0),
					anchor: new google.maps.Point(17, 34),
					scaledSize: new google.maps.Size(25, 25)
				};

				// Create a marker for each place.
				var marker = new google.maps.Marker({
					map: map,
					icon: image,
					title: place.name,
					position: place.geometry.location
				});

				markers.push(marker);

				bounds.extend(place.geometry.location);
			}
			
			// If the client selected one place look for DPD Shops.
			if (places.length == 1) {
				var xmlhttp = selectXMLHttpObj();
				
				var selectedLocation = places[0].geometry.location;
				
				var filter;
				if (typeof objConfig.filter == 'undefined') filter = "false";
				
				xmlhttp.open('POST', objConfig.ajaxpath, false);
				xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
				xmlhttp.send('long='+ selectedLocation.lng() + '&lat=' + selectedLocation.lat());
				
				testResult = xmlhttp.responseText;
				if(xmlhttp.status == 404 || testResult == "FALSE" || testResult == "") {
					alert ("Sorry, but something went wrong looking for the Pickup points. \r\n If this problem persists please contact the sites administrator");
				} else {
					// Parse the response to json object.
					objResult = JSON.parse(testResult);
				
					// Add the shops to the current shop object for later reference.
					for (var shopId in objResult) {
						var shop = objResult[shopId];
						var shopActive = (typeof objConfig.country == 'undefined') || shop.isoAlpha2 == '' || shop.isoAlpha2 == objConfig.country;
						
						DPDShops[shop.parcelShopId] = {
							id : shop.parcelShopId,
							name 	: shop.company,
							street 	: capitaliseFirstLetter(shop.street),
							houseNo	: shop.houseNo,
							country	: shop.country,
							zipCode	: shop.zipCode,
							city	: capitaliseFirstLetter(shop.city),
							country	: shop.isoAlpha2
						};
						
						var imageurl;
						
						if(shopActive){
							imageurl = objConfig.imgpath + "/icon_parcelshop.png";
						} else {
							imageurl = objConfig.imgpath + "/icon_parcelshop_na.png";
						}	
						
						// Create marker logo
						var DPDimage = {
							url: imageurl,
							size: new google.maps.Size(110, 120),
							origin: new google.maps.Point(0, 0),
							anchor: new google.maps.Point(17, 34),
							scaledSize: new google.maps.Size(55, 60)
						};
						
						// Create shop location
						var shopLocation = new google.maps.LatLng(shop.latitude, shop.longitude);
						
						// Create shop information bubble (depending on the information)
						var shopInfo = 	'<div class="shopInfoContainer">';
						shopInfo 	+= 	'	<h1>' + shop.company + '</h1>';
						// Uncomment the line below if you want the shop id to be displayed (only for internal use!!)
						//shopInfo 	+= 	'	<h2>' + shop.parcelShopId + '</h2>';						
						shopInfo 	+= 	'	<p>' + capitaliseFirstLetter(shop.street) + ' ' + shop.houseNo + ', ' + shop.zipCode + ' ' + capitaliseFirstLetter(shop.city) + '</p>';
						
						shopInfo	+= '	<div class="openingHours">';
						if(typeof shop.openingHours != 'undefined'){
							shopInfo	+= '		<h2>' + t(2) + '</h2>';
							shopInfo	+= '		<table>';
							for(var j = 0, day; day = shop.openingHours[j]; j++) {
								shopInfo	+= '			<tr>';
								shopInfo	+= '				<td>' + t(wdt(day.weekday)) + '</td>';
								shopInfo	+= '				<td>' + day.openMorning;
								if( day.openMorning != '' && day.closeMorning != '') {
									shopInfo	+= ' - ';
								} 
								shopInfo	+= day.closeMorning + '</td>';
								shopInfo	+= '				<td>' + day.openAfternoon;
								if( day.openAfternoon != '' && day.closeAfternoon != '') {
									shopInfo	+= ' - ';
								} 
								shopInfo	+= day.closeAfternoon + '</td>';
								shopInfo	+= '			</tr>';
							}
							shopInfo	+= '		</table>';
						}
						shopInfo	+=	'	</div>';
						shopInfo	+=	'	<div class="centerText">';
						if(shopActive){
							shopInfo	+=	'		<input class="choiceButton" type="button" value="' + t(10) + '" onclick="javascript:' + objConfig.callback + '(\'' + shop.parcelShopId + '\');">';
						} else {
							shopInfo 	+= 	'<p>' + t(12) + '</p>';
						}
						shopInfo	+=	'	</div>';
						shopInfo 	+= 	'</div>';
						
						// Create a marker for each shop.
						var marker = new google.maps.Marker({
							map: map,
							icon: DPDimage,
							title: shop.company,
							position: shopLocation,
							info: new google.maps.InfoWindow({
								content: shopInfo
							})
						});
						
						// Add a lister to display the info when a marker is clicked.
						google.maps.event.addListener(marker, 'click', function() {
							this.info.open(map, this);
						});
						
						// Add the marker to the map.
						DPDmarkers.push(marker);

						bounds.extend(shopLocation);
					}
				}
			}

			map.fitBounds(bounds);
		}

		// === 2100 END HELPER FUNCTIONS === //
		// ================================= //
		
		// ============================ //
		// ===== 2200 CONSTRUCTOR ===== //
		
		// 2201: Public variables
		this.map; // Map variable (this allows the user to change the map to his preferences. 
		this.DPDShops = []; // Should be approached by getShopInfo(), but this allows the shipper to get all the last proposed shops.
		
		// 2202: Private variables
		var language; 	// Used to get the correct translations.
		var dictionary; // Used to store the chosen translation.
		var markers = []; // Used to store the clients search markers
		var DPDmarkers = []; // Used to store the proposed shop markers
		
		// Check if the language was set in the config otherwise use the Default language (en)
		if(typeof objConfig.language == 'undefined') {
			// Default language code
			language = "en";
		} else {
			language = objConfig.language;
		}
		
		// Populate objects dictionary
		dictionary = populateDictionary( objConfig.dictionaryXML, language );
		
		// === 2200 END CONSTRUCTOR === //
		// ============================ //
		
		// =============================== //
		// ===== 2300 PUBLIC METHODS ===== //
		
		// 2301: Public function to get information about a shop.
		this.getShopInfo = function(shopID){
			return DPDShops[shopID];
		}
		
		// 2302: Public function to show locator (in container)
		this.showLocator = function(center){
			var objMapContainer = document.getElementById('DPDlocator');
			if(typeof objConfig.fullscreen != 'undefined' && objConfig.fullscreen){
				objMapContainer.style.position =  "absolute";
				objMapContainer.style.width =  "100%";
				objMapContainer.style.height =  "100%";
				objMapContainer.style.top = "0";
				objMapContainer.style.left = "0";
				document.body.scrollTop = document.documentElement.scrollTop = 0;
				document.body.style.overflow = "hidden";
			} else {
				objMapContainer.style.position =  "relative";
				objMapContainer.style.width =  "800px";
				objMapContainer.style.height =  "600px";
				if(objConfig.width != 'undefined') objMapContainer.style.width = objConfig.width;
				if(objConfig.height != 'undefined') objMapContainer.style.height = objConfig.height;	
			}
			objMapContainer.style.display = 'block';
			objMapContainer.style.visibility = 'visible';

			var currCenter = map.getCenter();
			google.maps.event.trigger(map, "resize"); // Trigger google map resize to rerender the map.
			
			// Check if a new center (address) is set
			if(typeof center != 'undefined'){
				input = document.getElementById('pac-input');
				input.value = center;
				
				var request = {
					query: center
				};

				service = new google.maps.places.PlacesService(map);
				service.textSearch(request, function(places){
					renderMarkers(places);
				});
			} else {
				// otherwise recenter on last known center.
				map.setCenter(currCenter);
			}
		}
		
		// 2303: Public function to hide the locator
		this.hideLocator = function(){
			var objMapContainer = document.getElementById('DPDlocator');
			objMapContainer.style.display = 'none';
			objMapContainer.style.visibility = 'hidden';
			document.body.style.overflow = "scroll";
		}
		
		// 2304: Initialization function (to be called onload of body for example)
		this.initialize = function() {
			// If the start center is set in the config object use it, otherwise we center the map on our headquarters
			var startCenter;
			if(typeof objConfig.mapCenter != 'undefined'){
				startCenter = new google.maps.LatLng(objConfig.mapCenter.lat, objConfig.mapCenter.lng);
			} else {
				startCenter = new google.maps.LatLng(51.0110348, 4.5061507);
			}
			
			// The map options.
			var mapOptions = {
				// User Controls
				panControl: true,
				zoomControl: true,
				mapTypeControl: true,
				scaleControl: true,
				streetViewControl: true,
				overviewMapControl: true,
				center: startCenter,
				zoom: 10,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			var objContainer = document.getElementById(objConfig.containerId);
			
			// Create the main container (so we can easily switch from inline to fullscreen)
			var objMainContainer = document.createElement("div");
			objMainContainer.id = "DPDlocator";
			objContainer.appendChild(objMainContainer);
			
			// Create the start point search box
			var input 	= document.createElement("input");
			input.type 	= "text";
			input.name 	= "DPDsearchBar";
			input.id	= "pac-input";
			input.className = "controls";
			input.setAttribute("placeholder", t(1));
			objMainContainer.appendChild(input);
			
			// Create the close button
			var close 	= document.createElement("input");
			close.type 	= "button";
			close.name 	= "DPDcloseBtn";
			close.id	= "pac-close";
			close.className = "controls";
			close.value = t(11);
			objMainContainer.appendChild(close);
	
			// Create the map canvas.
			var mapCanvas = document.createElement("div");
			mapCanvas.id = "map-canvas";
			objMainContainer.appendChild(mapCanvas);
			
			// Finaly create the map.
			map = new google.maps.Map(mapCanvas, mapOptions);
			
			// And link the searchbox to the map
			map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
			map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(close);

			var searchBox = new google.maps.places.SearchBox(
			/** @type {HTMLInputElement} */(input));

			// Listen for the event fired when the user selects an item from the
			// pick list. Retrieve the matching places for that item.
			google.maps.event.addListener(searchBox, 'places_changed', function() {
				// Get the proposed places
				var places = searchBox.getPlaces();

				renderMarkers(places);
			});
			
			var that = this; 
			google.maps.event.addDomListener(close, 'click',function(){that.hideLocator()});

			// Bias the SearchBox results towards places that are within the bounds of the
			// current map's viewport.
			google.maps.event.addListener(map, 'bounds_changed', function() {
				var bounds = map.getBounds();
				searchBox.setBounds(bounds);
			});
		}
		
		// 2305: Toggle fulscreen
		this.toggleFullscreen = function(){
			objConfig.fullscreen = !objConfig.fullscreen;
		}
	};
}
