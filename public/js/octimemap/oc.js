// Deal with very large time spans

/**
 * Monkey-patch SIMILE with new time intervals.
 *
 * @param {String} intervalName     Name of the new interval (SIMILE style is all caps)
 * @param {int} intervalLength      Length of new interval, in multipleOf units
 * @param {int} multipleOf          What unit this new interval is a multiple of. Defaults
 *                                  to Timeline.DateTime.YEAR.
 * @param {function} labeller       Custom labeller function - should take a date param and
 *                                  return {text:"label", emphasized:false}
 */
SimileAjax.DateTime.addInterval = function(intervalName, intervalLength, multipleOf, labeller) {
    // set defaults
    if (!multipleOf) multipleOf = Timeline.DateTime.YEAR;
    if (!labeller) labeller = function(date) {
        var y = date.getUTCFullYear();
        text = (y > 0) ? y : (0-y) + "BC";
        return { text: text, emphasized: false };
    };
    
    // get relevant objects
    var dt = SimileAjax.DateTime || Timeline.DateTime;
    var unitArray = dt.gregorianUnitLengths;
    
    // map appropriate getters
    var getterMap = [], setterMap = [];
    getterMap[dt.MILLISECOND] = 'getUTCMilliseconds';
    getterMap[dt.SECOND]      = 'getUTCSeconds';
    getterMap[dt.MINUTE]      = 'getUTCMinutes';
    getterMap[dt.HOUR]        = 'getUTCHours';
    getterMap[dt.DAY]         = 'getUTCDate';
    getterMap[dt.WEEK]        = ''; // XXX: don't really want to deal now
    getterMap[dt.MONTH]       = 'getUTCMonth';
    getterMap[dt.YEAR]        = 'getUTCFullYear';    
    // map appropriate setters
    setterMap[dt.MILLISECOND] = 'setUTCMilliseconds';
    setterMap[dt.SECOND]      = 'setUTCSeconds';
    setterMap[dt.MINUTE]      = 'setUTCMinutes';
    setterMap[dt.HOUR]        = 'setUTCHours';
    setterMap[dt.DAY]         = 'setUTCDate';
    setterMap[dt.WEEK]        = ''; // XXX: don't really want to deal now
    setterMap[dt.MONTH]       = 'setUTCMonth';
    setterMap[dt.YEAR]        = 'setUTCFullYear';
    
    // add new interval name
    dt[intervalName] = unitArray.length;
    // set length
    unitArray[dt[intervalName]] = unitArray[multipleOf] * intervalLength;
    
    // add to round-down function
    (function(f) {
        dt.roundDownToInterval = function(date, intervalUnit, timeZone, multiple, firstDayOfWeek) {
            // call original function if we're not dealing with new unit
            if (intervalUnit != dt[intervalName]) f(date, intervalUnit, timeZone, multiple, firstDayOfWeek);
            else {
                // Sadly, this block is mostly a repeat of SIMILE code
                var timeShift = timeZone * unitArray[dt.HOUR];
            
                var date2 = new Date(date.getTime() + timeShift);
                var clearInDay = function(d) {
                    // overly clever way of clearing out millisecond thru hour
                    for (var x=0; x<4; x++) {
                        d[setterMap[x]](0);
                    }
                };
                var clearInYear = function(d) {
                    clearInDay(d);
                    d.setUTCDate(1);
                    d.setUTCMonth(0);
                };
                // end copy
                
                // deal with new interval
                if (multipleOf < dt.YEAR) clearInDay(date2);
                else clearInYear(date2);
                // round down
                date2[setterMap[multipleOf]](
                    Math.floor(date2[getterMap[multipleOf]]() / intervalLength) * intervalLength
                );
                
                // set time - copied
                date.setTime(date2.getTime() - timeShift);
            }
        };
    })(dt.roundDownToInterval);
    
    // add to increment function
    (function(f) {
        dt.incrementByInterval = function(date, intervalUnit, timeZone) {
            // call original function if we're not dealing with new unit
            if (intervalUnit != dt[intervalName]) f(date, intervalUnit, timeZone);
            else {
                // block copied from SIMILE code
                timeZone = (typeof timeZone == 'undefined') ? 0 : timeZone;

                var timeShift = timeZone * 
                    SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.HOUR];
                    
                var date2 = new Date(date.getTime() + timeShift);
                // end copy
                
                // deal with new interval
                date2[setterMap[multipleOf]](
                    date2[getterMap[multipleOf]]() + intervalLength
                );
                
                // set time - copied
                date.setTime(date2.getTime() - timeShift);
            }
        };
    })(dt.incrementByInterval);
    
    // prototype requires a different patch approach
    var proto;
    
    // add custom labeller
    if (labeller) {
        proto = Timeline.GregorianDateLabeller.prototype;
        (function(f) {
            proto.defaultLabelInterval = function(date, intervalUnit) {
                // call original function if we're not dealing with new unit
                var f2 = (intervalUnit != dt[intervalName]) ? f : labeller;
                return f2.call(this, date, intervalUnit);
            };
        })(proto.defaultLabelInterval);
    }
    
    // patch Band prototype to avoid dying on invalid dates
    var earliest = -8640000000000000, latest = 8640000000000000;
    proto = Timeline._Band.prototype;
    // min date
    (function(f) {
        proto.getMinDate = function() {
            // try original function
            var d = f.call(this);
            // check for invalid dates
            if (!d.getFullYear()) d = new Date(earliest);
            return d;
        };
    })(proto.getMinDate);
    // max date
    (function(f) {
        proto.getMaxDate = function() {
            // try original function
            var d = f.call(this);
            // check for invalid dates
            if (!d.getFullYear()) d = new Date(latest);
            return d;
        };
    })(proto.getMaxDate);

};


/*
 * Tests: Uncomment to run. If I move this into timemap.js, this will
 * get turned into JSUnit tests.
 *

var myDate;
// 50,000 year interval
SimileAjax.DateTime.addInterval('FIFTYK', 50000);
if (Timeline.DateTime.FIFTYK != 11) 
    throw "FIFTYK Not added to DateTime";
if (Timeline.DateTime.gregorianUnitLengths[Timeline.DateTime.FIFTYK] != 1576800000000000)
    throw "FIFTYK Unit length wrong";
myDate = new Date(200400, 1, 20);
Timeline.DateTime.roundDownToInterval(myDate, Timeline.DateTime.FIFTYK, -8, 1, 0);
if (myDate.getUTCFullYear() != 200000)
    throw "FIFTYK Didn't round down properly";
Timeline.DateTime.incrementByInterval(myDate, Timeline.DateTime.FIFTYK, -8);
if (myDate.getUTCFullYear() != 250000)
    throw "FIFTYK Didn't increment properly";

// same for 50 year interval
SimileAjax.DateTime.addInterval('FIFTY_YR', 50);
if (Timeline.DateTime.FIFTY_YR != 12) 
    throw "FIFTY_YR Not added to DateTime";
if (Timeline.DateTime.gregorianUnitLengths[Timeline.DateTime.FIFTY_YR] != 1576800000000)
    throw "FIFTY_YR Unit length wrong";
myDate = new Date(2009, 1, 20);
Timeline.DateTime.roundDownToInterval(myDate, Timeline.DateTime.FIFTY_YR, -8, 1, 0);
if (myDate.getUTCFullYear() != 2000)
    throw "FIFTY_YR Didn't round down properly";
Timeline.DateTime.incrementByInterval(myDate, Timeline.DateTime.FIFTY_YR, -8);
if (myDate.getUTCFullYear() != 2050)
    throw "FIFTY_YR Didn't increment properly";
// */

