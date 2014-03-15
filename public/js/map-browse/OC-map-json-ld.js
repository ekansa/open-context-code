var legendAndMapDomID = "oc-ml";
var legendDomID = "oc-legend-tab";
var legendDomRowID = "oc-legend-tr";
var mapDomID = "oc-map";
var ocNavFacDomID = "oc-nav-facets";
var loadingDomID = "oc-loading";
var facetsDomID = "oc-facets";
var navDomID = "oc-items-nav";
var resultsDomID = "oc-items";
var map;
var geoJSONurl = "http://opencontext.org/sets/United+States.geojson-ld?chrono=1&geotile=0&geodeep=6&dinaaPer=root";

var bounds; //current bounds of the mapping layer
var OCdataLayer; //data layer with open context mapping data
var heatMapData; //data for the heatmap
var OCheatMapLayer; //heatmap data layer
var OCsearchObj; //results of open context search request
var pointItems; //array of open context search items to display
var pointFeatures; //array of map features of points
var searchTotalFound = 0;

var hasFacetsPred = "oc-api:has-facets"; //has facets predicate
var hasFacetValsPred = "oc-api:has-facet-values"; //has facet values predicate
var ocApiPred = "oc-api:api-url";
var facetValuePred = "oc-api:facet-value";

//color settings for tiles
var startColor = convertToRGB('#FFC800');
var endColor   = convertToRGB('#FF0000');
var trueMaxValue = 0;
var trueMinValue = 1000000000000;
var maxValue = 0;
var minValue = 1000000000000;
var allFeatures = new Array;
var info;
var minIconSize = 15;
var maxIconSize = 50;
var alphaRange = [225,175];
var legendTextColor = "#FFFFFE";

//set up the DOM to take mapping data
function OCinitialize(){
	
     var OCdom = document.getElementById("oc-data"); //the main container for the map
     OCdom.setAttribute("style", "font-family:sans-serif; width:100%;");
     OCdom.setAttribute("class", "container");
     
	var mainRowDom = document.createElement("div"); //the first row, containing search facets and the map
	mainRowDom.setAttribute("id", "oc-facet-map");
	mainRowDom.setAttribute("class", "row");
	OCdom.appendChild(mainRowDom);
	
	var navFacDom = document.createElement("div");
     navFacDom.setAttribute("id", ocNavFacDomID);
     navFacDom.setAttribute("class", "col-md-3");
     mainRowDom.appendChild(navFacDom);
	
	var loadingDom = document.createElement("div");
     loadingDom.setAttribute("id", loadingDomID);
	loadingDom.setAttribute("style", "display:block;");
	var loadingHTML = "<h5>Loading from Open Context...</h5>";
	loadingHTML += "<img src=\"http://opencontext/js/map-browse/ajax-loader.gif\" alt=\"loading-icon\"/>";
     loadingDom.innerHTML = loadingHTML;
     navFacDom.appendChild(loadingDom);
	
	var facetsDom = document.createElement("div");
     facetsDom.setAttribute("id", facetsDomID);
     navFacDom.appendChild(facetsDom);
     
     var mapAndLegendDom = document.createElement("div");
     mapAndLegendDom.setAttribute("id", legendAndMapDomID);
     mapAndLegendDom.setAttribute("class", "col-sm-9");
     mainRowDom.appendChild(mapAndLegendDom);
     
     var legendDom = document.createElement("div");
     legendDom.setAttribute("id", legendDomID);
     legendDom.setAttribute("style", "display:table; width:100%;");
     mapAndLegendDom.appendChild(legendDom);
     
     var legendRowDom = document.createElement("div");
     legendRowDom.setAttribute("id", legendDomRowID);
     legendRowDom.setAttribute("style", "display:table-row; display:none;");
     legendDom.appendChild(legendRowDom);
     
     var mapDom = document.createElement("div");
     mapDom.setAttribute("id", mapDomID);
     mapAndLegendDom.appendChild(mapDom);
	
	var itemsRowDom = document.createElement("div"); //the second row, with search result items
	itemsRowDom.setAttribute("id", "oc-nav-items-row");
	itemsRowDom.setAttribute("class", "row");
	OCdom.appendChild(itemsRowDom);
	
	var itemsNavDom = document.createElement("div"); //the second row, with search result items
	itemsNavDom.setAttribute("id", navDomID);
	itemsNavDom.setAttribute("class", "col-md-3");
	itemsRowDom.appendChild(itemsNavDom);
	
	var itemsDom = document.createElement("div"); //the second row, with search result items
	itemsDom.setAttribute("id", resultsDomID);
	itemsDom.setAttribute("class", "col-md-9");
	itemsRowDom.appendChild(itemsDom);
	
     
     initmap();
}


function initmap() {
     // set up the map
     
     map = L.map(mapDomID).setView([0, 0], 2); //map the map
     bounds = new L.LatLngBounds();
     var osmTiles = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
     });
    
     var mapboxTiles = L.tileLayer('http://api.tiles.mapbox.com/v3/ekansa.map-tba42j14/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://MapBox.com">MapBox.com</a> '
     });
    
     var ESRISatelliteTiles = L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; <a href="http://services.arcgisonline.com/">ESRI.com</a> '
     });
    
     var gmapRoad = new L.Google('ROADMAP');
     var gmapSat = new L.Google('SATELLITE');
     var gmapTer = new L.Google('TERRAIN');
    
     var baseMaps = {
         "Google-Terrain": gmapTer,
         "Google-Satellite": gmapSat,
         "ESRI-Satellite": ESRISatelliteTiles,
         "Google-Roads": gmapRoad,
         "OpenStreetMap": osmTiles,
         "MapBox": mapboxTiles,
     };
   
    
     map.addLayer(gmapSat);
     map._layersMaxZoom = 20;
     L.control.layers(baseMaps).addTo(map);
     //getOClayer(false, map);
     getOClayer(false, "");
}


function getNewOClayer(url){
	
	if (!jQuery.isEmptyObject(OCdataLayer)) {
		OCdataLayer.clearLayers();
	}
     if (!jQuery.isEmptyObject(OCheatMapLayer)) {
		map.removeLayer(OCheatMapLayer);
	}
	var loadingDom = document.getElementById(loadingDomID);
     loadingDom.setAttribute("style", "display:block;");
	getOClayer(url);
}


function splitUrlParams(rawParamString){
	if (rawParamString.indexOf("&") > -1) {
		var paramsEx = rawParamString.split("&");
	}
	else{
		var paramsEx = Array();
		paramsEx.push(rawParamString);
	}
	return paramsEx;
}

function replaceAll(find, replace, str) {
	var output = str;
	if(str != null){
		if (str.length > 0) {	
			while( str.indexOf(find) > -1){
			  str = str.replace(find, replace);
			}
			output = str;
		}
      
	}
	return output;
}



//open context makes query parameters that are arrays
//want to treat them as such for ajax queries
function paramData(rawParamString){
	var paramsEx = splitUrlParams(rawParamString);
	var tempData = Array();
	for (var i = 0; i < paramsEx.length; i++) {
		var keyValEx = paramsEx[i].split("=");
		var paramKey = keyValEx[0];
		var paramValue = keyValEx[1];
		paramValue = replaceAll("+", " ", paramValue);
		if (paramKey in tempData) {
			tempData[paramKey].push(paramValue);
		}
		else{
			tempData[paramKey] = new Array();
			tempData[paramKey].push(paramValue);	
		}
	}
	for (var i = 0; i < paramsEx.length; i++) {
		var keyValEx = paramsEx[i].split("=");
		var paramKey = keyValEx[0];
		var paramValue = keyValEx[1];
		if(tempData[paramKey].length > 1){
			for (var ii = 0; ii < tempData[paramKey].length; ii++) {
				this[paramKey][ii] = tempData[paramKey][ii];
			}
		}
		else{
			this[paramKey] = tempData[paramKey][0];
		}
	}
	
}




function getOClayer(rawUrl){
     if (!rawUrl) {
        rawUrl = geoJSONurl;
     }
     
	var url = rawUrl;
	var data = new Object;
	
          $.ajax({
              type: "GET",
              url: url,
		    data: data,
              dataType: 'json',
              success: function(data){
                  OCsearchObj = data;
                  //console.log(OCsearchObj);
                  var ocData = data;
                  processOCresults(ocData);
                  addOClayer(ocData);
			   displayResultItems(ocData);
              }
          });
     
}

function processOCresults(ocData){
     //alert(ocData["oc-api:has-facets"].length + " facets found");
     searchTotalFound = ocData["numFound"];
	var loadingDom = document.getElementById(loadingDomID);
     loadingDom.setAttribute("style", "display:none;");
	
	
     var facetsDom = document.getElementById(facetsDomID);
     facetsDom.innerHTML = "";
     var totalDom = document.createElement("h4");
     totalDom.innerHTML = "Number Found: " + searchTotalFound;
     facetsDom.appendChild(totalDom);
	
	var filterDom = document.createElement("h5");
	filterDom.setAttribute("style", "padding-top:5%;");
     filterDom.innerHTML = "Filter By: ";
     facetsDom.appendChild(filterDom);
     
     var facetAccordion = document.createElement("div");
     facetAccordion.setAttribute("class", "panel-group");
     facetAccordion.setAttribute("id", "oc-fac-accordion");
     facetsDom.appendChild(facetAccordion);
     
     for (var i = 0; i < ocData[hasFacetsPred].length; i++) {
          var actFacet = ocData[hasFacetsPred][i];
          var actFacID = actFacet.id.replace("#","");
          var aDom = document.createElement("div");
          aDom.setAttribute("class", "panel panel-default");
          facetAccordion.appendChild(aDom);
          var bDom = document.createElement("div");
          bDom.setAttribute("class", "panel-heading");
          bDom.innerHTML = "<h4 class=\"panel-title\"><a data-toggle=\"collapse\" data-parent=\"#oc-fac-accordion\" href=\"#" + actFacID + "\">" + actFacet.label + "</a></h4>";
          aDom.appendChild(bDom);
          var cDom = document.createElement("div");
          cDom.setAttribute("class", "panel-collapse collapse collapse");
          cDom.setAttribute("id", actFacID);
          aDom.appendChild(cDom);
          var dDom = document.createElement("div");
          dDom.setAttribute("class", "panel-body");
          cDom.appendChild(dDom);
          var listDom = document.createElement("ul");
          dDom.appendChild(listDom);
          for (var ii = 0; ii < actFacet[hasFacetValsPred].length; ii++) {
               var actFacetVal = actFacet[hasFacetValsPred][ii];
               var itemDom = document.createElement("li");
               var actFilterURL = actFacetVal[ocApiPred] 
               var actionLink = "javascript:getNewOClayer('"+ actFilterURL + "')";
               itemDom.innerHTML = "<a href=\"" + actionLink + "\">" + actFacetVal.label + "</a> " + actFacetVal.count;
               listDom.appendChild(itemDom);
          }
     }
	
}

//displays a list of search item results
function displayResultItems(ocData){
	
	var itemsDom = document.getElementById(resultsDomID);
     itemsDom.innerHTML = "";
	
	var carouselDom = document.createElement("div");
	carouselDom.setAttribute("id", "oc-items-carousel");
	carouselDom.setAttribute("class", "carousel slide");
	carouselDom.setAttribute("data-ride", "carousel");
	carouselDom.setAttribute("data-interval", "false");
	carouselDom.setAttribute("style", "padding-top:2%;");
	itemsDom.appendChild(carouselDom);
	
	var cIndicators = document.createElement("ul");
	cIndicators.setAttribute("class", "carousel-indicators");
	carouselDom.appendChild(cIndicators);
	
	var cItem =  document.createElement("div");
	cItem.setAttribute("class", "carousel-inner");
	cItem.setAttribute("style", "width:75%; margin-left:auto; margin-right:auto;");
	carouselDom.appendChild(cItem);
	
	pointItems = new Array();
	for (var i = 0; i < ocData.features.length; i++) {
		var feature = ocData.features[i];
		if (feature.geometry.type == "Point") {
			pointItems.push(feature.properties);
		}
	}
	
	addSlideIntroImage(cIndicators, cItem); //add intro slide, that will be active
	for (var i = 0; i < pointItems.length; i++) {
		var props = pointItems[i];
		var indItem = document.createElement("li");
		indItem.setAttribute("data-target", "oc-items-carousel");
		var itemAct = "item";
		if (i < 1) {
			//indItem.setAttribute("class", "active");
			//itemAct = "item active";
		}
		indItem.setAttribute("data-slide-to", i+1);
		cIndicators.appendChild(indItem);
		
		var ccItem = document.createElement("div");
		ccItem.setAttribute("class", itemAct);
		ccItem.setAttribute("id", "slide-" + props.itemNumber);
		ccItem.setAttribute("style", "font-size:75%;");
		cItem.appendChild(ccItem);
		
		
		iPropTab = document.createElement("table");
		iPropTab.setAttribute("class", "table table-condensed table-striped table-hover");
		propertyTable(props, iPropTab); //add property rows to the table.
		ccItem.appendChild(iPropTab);
		
		var cccItem = document.createElement("div");
		cccItem.setAttribute("class", "carousel-caption");
		//cccItem.setAttribute("style", "margin-top:5%; padding-top: 20%;");
		cccItem.innerHTML = "<h4>Item: '" + props.label + " (" + props.category + ")</h4>";
		//ccItem.appendChild(cccItem);
	}
	
	var lcont = document.createElement("a");
	lcont.setAttribute("class", "left carousel-control");
	lcont.setAttribute("href", "#oc-items-carousel");
	lcont.setAttribute("data-slide", "prev");
	var lcontsp = document.createElement("span");
	lcontsp.setAttribute("class", "glyphicon glyphicon-chevron-left");
	lcont.appendChild(lcontsp);
	carouselDom.appendChild(lcont);
	
	var rcont = document.createElement("a");
	rcont.setAttribute("class", "right carousel-control");
	rcont.setAttribute("href", "#oc-items-carousel");
	rcont.setAttribute("data-slide", "next");
	var rcontsp = document.createElement("span");
	rcontsp.setAttribute("class", "glyphicon glyphicon-chevron-right");
	rcont.appendChild(rcontsp);
	carouselDom.appendChild(rcont);
	
	$('#oc-items-carousel').on('slid.bs.carousel', function () {
		var slideDom = document.getElementById("slide-0");
		if(slideDom.className.indexOf("active") > -1){
			for (ii = 0; ii < pointFeatures.length; ii++) {
				pointFeatures[ii].hide();
			}
		}
		else{
			for (i = 0; i < pointItems.length; i++) {
				var slideDom = document.getElementById("slide-" + pointItems[i].itemNumber);
				if(slideDom.className.indexOf("active") > -1){
					//pointFeatures
					for (ii = 0; ii < pointFeatures.length; ii++) {
						if (pointFeatures[ii].pointID == pointItems[i].itemNumber) {
							pointFeatures[ii].show();
						}
						else{
							pointFeatures[ii].hide();
						}
					}
				}
			}
		}
	});
	
}

//initial introduction slide
function addSlideIntroImage(cIndicators, cItem) {
	var indItem = document.createElement("li");
	indItem.setAttribute("data-target", "oc-items-carousel");
	indItem.setAttribute("class", "active");
	indItem.setAttribute("data-slide-to", 0);
	cIndicators.appendChild(indItem);
	
	var ccItem = document.createElement("div");
	ccItem.setAttribute("class", "item active");
	ccItem.setAttribute("id", "slide-0");
	ccItem.setAttribute("style", "text-align:center;");
	var html = "";
	if (geoJSONurl.indexOf("dinaa")) {
		html = "<h5>Browse through DINAA compiled Site Records</h5>";
		html += "<div style=\"display: block; text-align:center; width:75%; margin-left:auto; margin-right:auto;\">";
		html += "<img src=\"http://opencontext.org/js/map-browse/DINAA-logo-small.png\" alt=\"DINAA logo\" />";
		html += "</div>";
		html += "<p><a target=\"_bank\" href=\"http://ux.opencontext.org/blog/archaeology-site-data/\">(Click for more about DINAA on Open Context)</a></p>";
	}
	else{
		html = "<h4>Browse through items published by Open Context</h4>";
		html += "<div style=\"display: block; text-align:center; width:75%; margin-left:auto; margin-right:auto;\">";
		html += "<img src=\"http://opencontext.org/images/layout/open-context-gen-logo-med.png\" alt=\"Open COntext logo\" />";
		html += "</div>";
		html += "<p><a target=\"_bank\" href=\"http://opencontext.org/about/\">(Click for more about Open Context)</a></p>";
	}
	ccItem.innerHTML = html;
	
	//var cccItem = document.createElement("div");
	//cccItem.setAttribute("class", "carousel-caption");
	//cccItem.setAttribute("style", "margin-top:5%; padding-top: 20%;");
	//cccItem.innerHTML = "<h4>Item: '" + props.label + " (" + props.category + ")</h4>";
	//ccItem.appendChild(cccItem);
	
	cItem.appendChild(ccItem);
	
}


function propertyTable(props, iPropTab){
	
	var tHead = document.createElement("thead");
	tHead.innerHTML = "<tr><th>Property</th><th>Value</th></tr>";
	iPropTab.appendChild(tHead);
	
	var tBody = document.createElement("tbody");
	var keys = Object.keys(props);
	console.log(keys);
	console.log(props);
	var tBodyContent = "";
	for (var i = 0; i < keys.length; i++) {
		var actKey = keys[i];
		var actVal = props[actKey];
		if (actVal.length>0) {
			if (actKey == "id") {
				tBodyContent += "<tr><td>Link (URI)</td><td><a href=\"" + actVal + "\" target=\"_blank\">" + actVal + "</a></td></tr>";
			}
			else if (actKey == "label") {
				tBodyContent += "<tr><td>Label</td><td>" + actVal + " (" + props["itemNumber"] + " of "+ searchTotalFound+ " found)</td></tr>";
			}
			else if (actKey == "category") {
				
			}
			else if (actKey == "itemNumber") {
				//tBodyContent += "<tr><td>Search Result</td><td>" + actVal + " of " + searchTotalFound + "</td></tr>";
			}
			else{
				tBodyContent += "<tr><td>" + actKey + "</td><td>" + actVal + "</td></tr>";
			}
		}
		
	}
	tBody.innerHTML = tBodyContent;
	iPropTab.appendChild(tBody);
}




//main function to display mapping data    
function addOClayer(ocData) {
     
	trueMaxValue = 0;
	trueMinValue = 1000000000000;
	maxValue = 0;
	minValue = 1000000000000;
	heatMapData = new Array();
	allFeatures = new Array();
	pointFeatures = new Array();
	
    //loop through features to get the maximum count, needed for assigning colors
     for (var i = 0; i < ocData.features.length; i++) {
		if ("count" in ocData.features[i].properties) {
			if(maxValue < ocData.features[i].properties.count){
				maxValue = ocData.features[i].properties.count;
			}
			if(minValue > ocData.features[i].properties.count){
				minValue = ocData.features[i].properties.count;
			}
		}
     }
     
     colorLegend(); //make the color legend
     
     for (i = 0; i < ocData.features.length; i++) {
          if(ocData.features[i].geometry.type == "Polygon"){
               var pointCoords = getPolyCenter(ocData.features[i].geometry.coordinates);
               var latlng = new L.LatLng(pointCoords[0], pointCoords[1]);
               bounds.extend(latlng);
			if ("count" in ocData.features[i].properties) {
				//var heatItem = new heatMapDataItem(pointCoords[0], pointCoords[1], ocData.features[i].properties.count);
				var heatItem = new Array();
				heatItem["lat"] = pointCoords[0];
				heatItem["lon"] = pointCoords[1];
				heatItem["value"] = ocData.features[i].properties.count;
				
				heatMapData.push(heatItem);
			}
          }
          if(ocData.features[i].geometry.type == "Point"){
               var latlng = new L.LatLng(ocData.features[i].geometry.coordinates[1], ocData.features[i].geometry.coordinates[0]);
               bounds.extend(latlng);
          }
          
     }
     
	
	OCheatMapLayer =  L.TileLayer.heatMap({
                    radius: 20,
                    opacity: 0.8,
				zIndex: 10,
                    gradient: {
                        0.45: "rgb(0,0,255)",
                        0.55: "rgb(0,255,255)",
                        0.65: "rgb(0,255,0)",
                        0.95: "yellow",
                        1.0: "rgb(255,0,0)"
                    }
                });
 
	
     OCheatMapLayer.addData(heatMapData);
	//console.log(heatMapData);
	//console.log(OCheatMapLayer);
	map.addLayer(OCheatMapLayer);
	
   
     OCdataLayer = L.geoJson(ocData, {
			style: function(feature) {
				if(feature.geometry.type == "Polygon"){
					var actCount = feature.properties.count;
					if("denominator" in feature.properties){
						if( feature.properties.denominator > 0){
							actCount = actCount / feature.properties.denominator;
						}
					}
					var newColorRGB = assignColorByCount(actCount, maxValue);
					var newColorHex =  "#" + convertToHex(newColorRGB);
                         var fillOpacity = assignOpacityByCount(actCount, maxValue, .75);
					
					return {
						color: newColorHex,
						zIndex: 8,
						opacity: .01,
                              fillOpacity: 0.01
					}
				}
				else if(feature.geometry.type == "Point"){
					/*
					return {
						filter: false
					}
					*/
				}
			},
			onEachFeature: onEachFeature
			
			
		}
     ).addTo(map);
	
	
    map.fitBounds(bounds);
}


//function for special processing of each feature in the map
function onEachFeature(feature, layer) {
	
	feature.reColor = function(){
		if(feature.geometry.type == "Polygon"){
			var actCount = feature.properties.count;
			if("denominator" in feature.properties){
				if( feature.properties.denominator > 0){
					actCount = actCount / feature.properties.denominator;
				}
			}
			var newColorRGB = assignColorByCount(actCount, trueMaxValue, trueMinValue); //add a color with the true Max count as the highest color 
			var newColorHex =  "#" + convertToHex(newColorRGB);
		 
			layer.setStyle({
				color: newColorHex
			});
		}
	}
	feature.reVertColor = function(){
		 OCdataLayer.resetStyle(layer);
	}
	feature.show = function(){
		layer.setOpacity(1);
		layer.openPopup();
	}
	feature.hide = function(){
		layer.setOpacity(0);
		layer.closePopup();
	}
	
	if(feature.geometry.type == "Polygon"){
		var newbounds = layer.getBounds();
		bounds.extend(newbounds.getSouthWest());
		bounds.extend(newbounds.getNorthEast());
		feature.pointID = false;
		 
	}
	if(feature.geometry.type == "Point"){
		feature.hide();	
		var newbounds = new Array();
		newbounds[0] = feature.geometry.coordinates[1]; //annoyance of flipping point coordinates!
		newbounds[1] = feature.geometry.coordinates[0];
		bounds.extend(newbounds);
		if (feature.properties) {
			feature.pointID = feature.properties.itemNumber;
			var popupContent = "<div><h5>Item: '" + feature.properties.label  + "' (" + feature.properties.category +")</h5>";
			popupContent += "<a href=\"#slide-"+feature.properties.itemNumber+"\">View details below</a>";
			popupContent += "</div>";
			layer.bindPopup(popupContent);
			pointFeatures.push(feature);
		}
	}
	
	allFeatures.push(feature);
}



function colorLegend(){
					 
     var rDiv = document.getElementById(legendDomRowID);
     rDiv.innerHTML = "";
     
     var startInner = minValue;
     var endInner = maxValue;
	
	var subDivs = 30;
	for (var i = 1; i <= subDivs; i++) {
          var actCount = maxValue * ( i / subDivs);
          var actColor = assignColorByCount(actCount, maxValue);
          var actDiv = document.createElement("div");
          
          var actStyle = "display:table-cell; padding: 2px; background-color: rgb(" + actColor + ");";
          if(i == 1){
              actDiv.setAttribute("id", "map-legend-start");
              actStyle += " text-shadow: 2px 2px #3B3B3B; text-align:left; color:" + legendTextColor;
              actDiv.innerHTML = startInner;
          }
          else if(i == subDivs){
               actDiv.setAttribute("id", "map-legend-end");
               actStyle += " text-shadow: 2px 2px #3B3B3B; text-align:right; color:" + legendTextColor;
               actDiv.innerHTML = endInner;
          }
          else{
              //actDiv.setAttribute("class", "map-legend-cell");
          }
          actDiv.setAttribute("style", actStyle);
		 
		rDiv.appendChild(actDiv);
	}
	
}














//return the center of a polygon
function getPolyCenter(coordinates){
	
	var pts = coordinates[0];
	var off = pts[0];
	var twicearea = 0;
	var x = 0;
	var y = 0;
	var nPts = pts.length;
	var p1,p2;
	var f;
	for (var i = 0, j = nPts - 1; i < nPts; j = i++) {
		 p1 = pts[i];
		 p2 = pts[j];
		 f = (p1[1] - off[1]) * (p2[0] - off[0]) - (p2[1] - off[1]) * (p1[0] - off[0]);
		 twicearea += f;
		 x += (p1[1] + p2[1] - 2 * off[1]) * f;
		 y += (p1[0] + p2[0] - 2 * off[0]) * f;
	}
	f = twicearea * 3;
	
	return new Array(
		 x / f + off[1],
		 y / f + off[0]
		 );
}



function assignColorByCount(actCount, maxValue, minValue){
	
	minValue = typeof minValue !== 'undefined' ? minValue : 0;
	
	if(maxValue > 0 ){
		actProp = actCount / (maxValue - minValue);
	}
	else{
		actProp = 1;
	} 
	
	var newColor = new Array();
	newColor[0] =  startColor[0] + Math.round((endColor[0] - startColor[0]) * actProp);
	newColor[1] =  startColor[1] + Math.round((endColor[1] - startColor[1]) * actProp);
	newColor[2] =  startColor[2] + Math.round((endColor[2] - startColor[2]) * actProp);
	
	return newColor;
}

function hex (c) {
	var s = "0123456789abcdef";
	var i = parseInt (c);
	if (i == 0 || isNaN (c))
	  return "00";
	i = Math.round (Math.min (Math.max (0, i), 255));
	return s.charAt ((i - i % 16) / 16) + s.charAt (i % 16);
 }
 
/* Convert an RGB triplet to a hex string */
function convertToHex (rgb) {
 return hex(rgb[0]) + hex(rgb[1]) + hex(rgb[2]);
}
 
/* Remove '#' in color hex string */
function trim (s) { return (s.charAt(0) == '#') ? s.substring(1, 7) : s }
 
/* Convert a hex string to an RGB triplet */
function convertToRGB (hex) {
	var color = new Array();
	color[0] = parseInt ((trim(hex)).substring (0, 2), 16);
	color[1] = parseInt ((trim(hex)).substring (2, 4), 16);
	color[2] = parseInt ((trim(hex)).substring (4, 6), 16);
	return color;
}

//generate a color for a polygon by its count
function assignOpacityByCount(actCount, maxValue, baseOpacity){	
	
	if(maxValue > 0 ){
		actProp = actCount / (maxValue - minValue);
	}
	else{
		actProp = 1;
	} 
	
	var opacity =  Math.round(baseOpacity * Math.sqrt(actProp), 2);
	
	if(opacity > baseOpacity){
		opacity = baseOpacity;
	}
	return opacity;
}