var legendAndMapDomID = "oc-ml";
var legendDomID = "oc-legend-tab";
var legendDomRowID = "oc-legend-tr";
var mapDomID = "oc-map";
var ocNavFacDomID = "oc-nav-facets";
var loadingDomID = "oc-loading";
var facetsDomID = "oc-facets";
var map;
var geoJSONurl = "http://opencontext.org/sets/United+States.geojson-ld?chrono=1&geotile=0&geodeep=6&dinaaPer=root";

var bounds; //current bounds of the mapping layer
var OCdataLayer; //data layer with open context mapping data
var heatMapData; //data for the heatmap
var OCheatMapLayer; //heatmap data layer
var OCsearchObj; //results of open context search request
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


function OCinitialize(){
     var OCdom = document.getElementById("oc-data");
     OCdom.setAttribute("style", "font-family:sans-serif;");
     OCdom.setAttribute("class", "container");
     
	var navFacDom = document.createElement("div");
     navFacDom.setAttribute("id", ocNavFacDomID);
     navFacDom.setAttribute("class", "col-sm-3");
     OCdom.appendChild(navFacDom);
	
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
     OCdom.appendChild(mapAndLegendDom);
     
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

    
function addOClayer(ocData) {
     
	trueMaxValue = 0;
	trueMinValue = 1000000000000;
	maxValue = 0;
	minValue = 1000000000000;
	heatMapData = new Array();
	
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
	console.log(heatMapData);
	console.log(OCheatMapLayer);
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
                              fillOpacity: 0.01};
				}
			}
		}
     ).addTo(map);
	
	
    map.fitBounds(bounds);
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








//function applied to each feature
function onEachFeaturePrep(feature, layer) {
	
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
	allFeatures.push(feature);
	
	if(feature.geometry.type == "Polygon"){
		var newbounds = layer.getBounds();
		bounds.extend(newbounds.getSouthWest());
		bounds.extend(newbounds.getNorthEast());
	}
	if(feature.geometry.type == "Point"){
		 var newbounds = new Array();
		 newbounds[0] = feature.geometry.coordinates[1]; //annoyance of flipping point coordinates!
		 newbounds[1] = feature.geometry.coordinates[0];
		 bounds.extend(newbounds);
	}
	if (feature.properties) {
		 var popupContent = "<div> The context <em>'" + feature.properties.name  + "'</em> has " + feature.properties.count;
		 //popupContent += " items. "
		 //popupContent += "<a href='" + feature.properties.href + "'>Click here</a> to filter by this geographic region</div>";
		 
		 
		 popupContent += " '" + nominatorCurrentVal + "'";
		 popupContent += " items";
		 if(proportional){
			  popupContent += " (" + Math.round((feature.properties.count / feature.properties.denominator) * 100, 1);
			  popupContent += "% of all " + feature.properties.denominator + " '" + feature.properties.propOf + "' in this context). ";
		 }
		 popupContent += ". ";
		 if(feature.properties.href){
			  popupContent += "<a href='" + feature.properties.href + "'>Click here</a> to filter by this region / context."
		 }
		 popupContent += "</div>";
		 
		 layer.bindPopup(popupContent);
		 
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