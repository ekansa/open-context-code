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
  * timemap_full.pack.js:  The library and all helper files. This is the recommended file to use in production.
  * timemap.pack.js:       Just the core library file

Documentation
  * docs/             Code documentation produced by jsdoc-toolkit
  * examples/         Example HTML code
  * LICENSE.txt       The MIT license
  * README.txt        This file

Source files
  * timemap.js:       The core timemap.js library
  * param.js          Abstraction layer for parameters
  * state.js          Functions for loading and serializing timemap state
  * manipulation.js:  Additional functions to manipulate a timemap after loading
  * export.js         Additional functions to help export a timemap as serialized JSON

Loaders (in loaders/ directory)
  * flickr.js         Loader for geotagged Flickr feeds
  * kml.js            Loader for KML files
  * georss.js         Loader for GeoRSS files
  * xml.js            Base loader for XML files
  * google_spreadsheet.js Loader for the Google Spreadsheets API
  * json.js:          Loaders for JSON (both string and jsonp)
  * progressive.js    Loader for data loaded in chunks based on timeline location
  * metaweb.js        Loader for Metaweb data from freebase.com

Other stuff
  * edit/             Semi-experimental editing UI - depends on jQuery, may no longer work
  * images/           Simple icons for timeline events
  * lib/              External libraries that may be useful, including compressed local versions of Timeline
  * tests/            jsUnit tests
  
Comments welcomed at nick (at) nickrabinowitz (dot) com.
