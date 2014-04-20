var trueMaxValue = 0;
var trueMinValue = 1000000000000;
var maxValue = 0;
var minValue = 1000000000000;
var allFeatures = new Array;
var info;
var minIconSize = 15;
var maxIconSize = 50;
var alphaRange = [225,175];


function colorLegend(){
					 
	var rDiv = document.getElementById("map-legend-row");
	
	if(proportional){
		 //startDiv.innerHTML = Math.round(minValue * 100, 1) + "%";
		 //endDiv.innerHTML = Math.round(minValue * 100, 1) + "%";
		 var startInner ="1%";
		 var endInner = "100%";
		 var curMapView = "Percent '" + nominatorCurrentVal + "' of '" + proportionLinkText + "'";
		 var controlInner = "Raw Count of '" + nominatorCurrentVal + "'";
	}
	else{
		 var startInner = minValue;
		 var endInner = maxValue;
	
		 var curMapView = "Raw Count of '" + nominatorCurrentVal + "'";
		 var controlInner = "Percent '" + nominatorCurrentVal + "' of '" + proportionLinkText + "'";
	}
	
	if(proportionLinkURL != false){
		 var rcDiv = document.getElementById("map-contr-row");
		 var actDiv = document.createElement("div");
		 actDiv.setAttribute("class", "map-contr-cell");
		 actDiv.innerHTML = "Map view: <span class=\"act-map-view\">" + curMapView + "</span>, ";
		 actDiv.innerHTML += "Swtich view to: <a href=\"" + proportionLinkURL + "\">" + controlInner + "</a>";
		 rcDiv.appendChild(actDiv);
	}
	
	
	var subDivs = 25;
	for (var i = 1; i <= subDivs; i++) {
		 var actCount = maxValue * ( i / subDivs);
		 var actColor = assignColorByCount(actCount, maxValue);
		 var actDiv = document.createElement("div");
		 
		 actDiv.setAttribute("style", ("background-color: rgb(" + actColor + ");"));
		 if(i == 1){
			  actDiv.setAttribute("class", "map-legend-cell-ends");
			  actDiv.setAttribute("id", "map-legend-start");
			  actDiv.innerHTML = startInner;
		 }
		 else if(i == subDivs){
			  actDiv.setAttribute("class", "map-legend-cell-ends");
			   actDiv.setAttribute("id", "map-legend-end");
			  actDiv.innerHTML = endInner;
		 }
		 else{
			  actDiv.setAttribute("class", "map-legend-cell");
		 }
		 
		 rDiv.appendChild(actDiv);
	}
	
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
function assignColorByCount(actCount, maxValue, minValue){
	
	minValue = typeof minValue !== 'undefined' ? minValue : 0;
	
	if(maxValue > 0 ){
		actProp = actCount / (maxValue - minValue);
	}
	else{
		actProp = 1;
	} 
	
	var newColor = new Array();
	if(actProp >= .5){
		var activeStartColor = midColor;
		var activeEndColor = endColor;
		var midProp = 1 - ((1 - actProp) * 2);
	}
	else{
		var activeStartColor = startColor;
		var activeEndColor = midColor;
		var midProp = 1 - ((.5 - actProp) * 2);
	}
	
	newColor[0] =  activeStartColor[0] + Math.round((activeEndColor[0] - activeStartColor[0]) * midProp);
	newColor[1] =  activeStartColor[1] + Math.round((activeEndColor[1] - activeStartColor[1]) * midProp);
	newColor[2] =  activeStartColor[2] + Math.round((activeEndColor[2] - activeStartColor[2]) * midProp);
	
	return newColor;
}


//generate a color for a polygon by its count
function assignOpacityByCount(actCount, maxValue, baseOpacity){	
	
	if(maxValue > 0 ){
		actProp = actCount / (maxValue - minValue);
	}
	else{
		actProp = 1;
	} 
	
	var opacity =  Math.round(baseOpacity * Math.sqrt(actProp)+ (baseOpacity * .5 * Math.sqrt(actProp *.3)), 2);
	//var opacity =  Math.round(baseOpacity * actProp, 2);
	opacity += .2;
	
	if(opacity > baseOpacity){
		opacity = baseOpacity;
	}
	return opacity;
}


function calculateIconSize(actCount, maxValue){
	var size = Math.round(maxIconSize * actCount/maxValue,0);
	if(size < minIconSize){
		size= minIconSize;
	}
	return size;
}

//get a map icon of the right color
function createColorMapIconURL(actCount, maxValue){
	/*
	return "http://chart.apis.google.com/" + 
        "chart?cht=it&chs=" + size + "x" + size + 
        "&chco=" + color + ",00000001,ffffff01" +
        "&chf=bg,s,00000000|a,s,000000" + alpha + "&ext=.png";
   
	var width = 20;
	var height = 40;
	*/
	var width = calculateIconSize(actCount, maxValue);
	var height =  width;
	var baseUrl = "http://chart.apis.google.com/chart?cht=it";
	var strokeColor = "000000";
	var cornerColor = "FFFFFF";
	var primaryColor = convertToHex(assignColorByCount(actCount, maxValue));
	/*
	var iconUrl = baseUrl + "&chs=" + width + "x" + height + 
		 "&chco=" + cornerColor + "," + 
		 primaryColor + "," + 
		 strokeColor + "&chf=bg,s,00000000|a,s,000000" + "&ext=.png";
	*/
	var alphaDif = (alphaRange[1] -  alphaRange[0]);
	alphaDif  = alphaDif  * ( (maxValue - actCount) / maxValue);
	
	var alpha = Math.round((alphaRange[0] + alphaDif), 0);
	alpha = alpha.toString(16); 
	
	var iconUrl = baseUrl + "&chs=" + width + "x" + height + 
		 "&chco=" + primaryColor + ",00000001,ffffff01" + "&chf=bg,s,00000000|a,s,000000" + alpha + "&ext=.png";
		 
	return iconUrl;
}


function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
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


var directionLatLngs = new Array();

function filterBox(geoJSONuri){
	L.control.layers(tileData).removeLayer;
}

function recolor(){
	//map.removeLayer(OCdataLayer);
	var legStartDom = document.getElementById("map-legend-start");
	var legEndDom = document.getElementById("map-legend-end");
	var contolDom = document.getElementById("color-control");
	contolDom.innerHTML = '<p><small><a href="javascript:javascript:resetcolor();">Reset colors</a></small></p>';
	if(trueMinValue >= .01){
		legStartDom.innerHTML = Math.round(trueMinValue * 1000)/10 + "%";
	}
	else{
		legStartDom.innerHTML = "<1%";
	}
	legEndDom.innerHTML = Math.round(trueMaxValue * 1000)/10 + "%";
	
	for (var i = 0; i < allFeatures.length; i++) {
		var featureObj = allFeatures[i];
		featureObj.reColor();
	}
}
function resetcolor(){
	var legStartDom = document.getElementById("map-legend-start");
	var legEndDom = document.getElementById("map-legend-end");
	var contolDom = document.getElementById("color-control");
	contolDom.innerHTML = '<p><small><a href="javascript:recolor();">Rescale colors</a></small></p>';
	legStartDom.innerHTML = "1%";
	legEndDom.innerHTML = "100%";
	for (var i = 0; i < allFeatures.length; i++) {
		var featureObj = allFeatures[i];
		featureObj.reVertColor();
	}
}

function colorFeatures(){
	
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
			  var newOpacity = assignOpacityByCount(actCount, trueMaxValue, .75);
			
			  layer.setStyle({
					fillOpacity: newOpacity,
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

function zoomMap(){
	if(map.getZoom()>14){
		 map.addLayer(gmapSat);
	}
}

function addInfoProportional(){
	if(proportional){          
		info = L.control();
		info.onAdd = function (map) {
			 this._div = L.DomUtil.create('div', 'info'); // create a div with a class "info"
			 this._div.setAttribute("id", "color-control");
			 this._div.innerHTML = '<p><small><a href="javascript:recolor();">Rescale colors</a></small></p>';
			 return this._div;
		};
		info.setPosition('topleft');
		info.addTo(map);
	}
}


function RGBtoHex(c) {
   var m = /rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/.exec(c);
   return m ? (1 << 24 | m[1] << 16 | m[2] << 8 | m[3]).toString(16).substr(1) : c;
}

function scaledShadowCircleURL(actCount, maxValue){
	var size = calculateIconSize(actCount, maxValue) + 2;
   var alpha = (alphaRange[1]-75).toString(16); 
   var color = "8A8A8A";
   return "http://chart.apis.google.com/" + 
        "chart?cht=it&chs=" + size + "x" + size + 
        "&chco=" + color + ",00000001,ffffff01" +
        "&chf=bg,s,00000000|a,s,000000" + alpha + "&ext=.png";
}
