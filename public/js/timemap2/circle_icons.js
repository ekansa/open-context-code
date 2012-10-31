
/**
 * Create the URL for a Google Charts circle image.
 */
TimeMapTheme.getCircleUrl = function(size, color, alpha) {
    
    if(color.substring(0,3) === "rgb"){
        color = RGBtoHex(color);
    }
    
    return "http://chart.apis.google.com/" + 
        "chart?cht=it&chs=" + size + "x" + size + 
        "&chco=" + color + ",00000001,ffffff01" +
        "&chf=bg,s,00000000|a,s,000000" + alpha + "&ext=.png";
};

TimeMapTheme.getCircleShadowUrl = function(size) {
    alpha = "bb";
    color = "8A8A8A";
    return "http://chart.apis.google.com/" + 
        "chart?cht=it&chs=" + size + "x" + size + 
        "&chco=" + color + ",00000001,ffffff01" +
        "&chf=bg,s,00000000|a,s,000000" + alpha + "&ext=.png";
};

/**
 * Create a timemap theme with matching event icons and sized map circles
 *  
 * @param {Object} [opts]       Config options
 * @param {Number} [opts.size=20]           Diameter of map circle
 * @param {Number} [opts.eventIconSize=10]  Diameter of event circle
 * @param {String} [opts.color='1f77b4']    Circle color (map + event), in RRGGBB hex or rgb(r,g,b) format
 * @param {String} [opts.alpha='bb']        Circle alpha (map), in AA hex
 * @param {String} [opts.eventAlpha='ff']   Circle alpha (event), in AA hex
 */
TimeMapTheme.createCircleTheme = function(opts) {
    var defaults = {
            size:20,
            color:'1f77b4',
            alpha:'bb',
            eventIconSize:10,
            eventAlpha:'ff'
        };
    opts = $.extend(defaults, opts);
    return new TimeMapTheme({
        icon: TimeMapTheme.getCircleUrl(opts.size, opts.color, opts.alpha),
        iconShadow:TimeMapTheme.getCircleShadowUrl(opts.size+2),
        //iconShadow: null,
        iconShadowSize: [52, 32],
        iconSize: [opts.size, opts.size],
        iconAnchor: [opts.size/2, opts.size/2],
        eventIcon: TimeMapTheme.getCircleUrl(opts.eventIconSize, opts.color, opts.eventAlpha),
        color: opts.color
    });
};


function RGBtoHex(c) {
    var m = /rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/.exec(c);
    return m ? (1 << 24 | m[1] << 16 | m[2] << 8 | m[3]).toString(16).substr(1) : c;
}
function circleURL(size, color, alpha){
    if(color.substring(0,3) === "rgb"){
        color = RGBtoHex(color);
    }
    return "http://chart.apis.google.com/" + 
        "chart?cht=it&chs=" + size + "x" + size + 
        "&chco=" + color + ",00000001,ffffff01" +
        "&chf=bg,s,00000000|a,s,000000" + alpha + "&ext=.png";
}
