/*! 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

Timemap.js
By Nick Rabinowitz (www.nickrabinowitz.com)

The timemap.js library is intended to sync a SIMILE Timeline with a Google Map.
Dependencies: Google Maps API v2, SIMILE Timeline v1.2 or later
Thanks to Jörn Clausen (http://www.oe-files.de) for initial concept and code.
-------------------------------------------------------------------------------

Getting Started

The best way to get started depends on your learning style, but here are the
places you should look:

  * Working Examples: ./examples/index.html
  * Basic Usage: http://code.google.com/p/timemap/wiki/BasicUsage
  * Code Documentation: ./docs/index.html
  * Homepage: http://code.google.com/p/timemap/
  * Discussion Group: http://groups.google.com/group/timemap-development

-------------------------------------------------------------------------------

Files in the project, in order of importance:

Packed files (YUI Compressor)
  * timemap_full.pack.js:  The library and all helper files
  * timemap.pack.js:       Just the core library file

Source files
  * timemap.js:       The core TimeMap library - all you need to load and display local JSON data on a timemap.
  * manipulation.js:  Additional functions to manipulate a TimeMap after loading
  * export.js         Additional functions to help export a TimeMap as serialized JSON

Loaders (in loaders/ directory)
  * flickr.js         Loader for geotagged Flickr feeds
  * kml.js            Loader for KML files
  * georss.js         Loader for GeoRSS files
  * google_spreadsheet.js Loader for the Google Spreadsheets API
  * json.js:          Loaders for JSON (both string and jsonp)
  * metaweb.js        Loader for Metaweb data from freebase.com

Documentation
  * LICENSE.txt       The license
  * README.txt        This file
  * docs/             Code documentation produced by jsdoc-toolkit

Other stuff
  * edit/             Semi-experimental editing UI - depends on jQuery
  * examples/         Example HTML code
  * images/           Simple icons for timeline events
  * lib/              External libraries that may be useful - lib/timeline-api.js is a local optimized copy of Timeline v.1.2 (English locale only)
  * tests/            jsUnit tests

Comments welcomed at nick (at) nickrabinowitz (dot) com.
