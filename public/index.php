<?php
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('America/Los_Angeles');
// directory setup and class loading
set_include_path('.' . PATH_SEPARATOR . '../library/'
     . PATH_SEPARATOR . '../application/models'
     . PATH_SEPARATOR . get_include_path());
include 'Zend/Loader.php';
mb_internal_encoding( 'UTF-8' );

Zend_Loader::registerAutoload();

// setup controller
$frontController = Zend_Controller_Front::getInstance();



// Custom routes
$router = $frontController->getRouter();

// ---------
// Subjects
// subjects view
$subjectsViewRoute = new Zend_Controller_Router_Route('subjects/:uuid', array('controller' => 'subjects', 'action' => 'view'));
// Add it to the router
$router->addRoute('subjectsView', $subjectsViewRoute); // 'subjects refers to a unique route name



// subjects atom
$subjectsAtomRoute = new Zend_Controller_Router_Route_Regex('subjects/(.*)\.atom',
                                                array('controller' => 'subjects', 'action' => 'atom'),
                                                array(1 => 'uuid'), 'subjects/%s.atom');

// add it to the router
$router->addRoute('subjectsAtom', $subjectsAtomRoute);

// subjects atom
$subjectsXMLRoute = new Zend_Controller_Router_Route_Regex('subjects/(.*)\.xml',
                                                array('controller' => 'subjects', 'action' => 'xml'),
                                                array(1 => 'uuid'), 'subjects/%s.xml');

// add it to the router
$router->addRoute('subjectsXML', $subjectsXMLRoute);

// subjects RDF
$subjectsRDFRoute = new Zend_Controller_Router_Route_Regex('subjects/(.*)\.rdf',
                                                array('controller' => 'subjects', 'action' => 'cidoc'),
                                                array(1 => 'uuid'), 'subjects/%s.rdf');

// add it to the router
$router->addRoute('subjectsRDF', $subjectsRDFRoute);

// end Subjects
// ---------


// ---------
// Media
// media view
$mediaViewRoute = new Zend_Controller_Router_Route('media/:uuid', array('controller' => 'media', 'action' => 'view'));
// Add it to the router
$router->addRoute('mediaView', $mediaViewRoute); // 'subjects refers to a unique route name

// media view
$mediaViewFullRoute = new Zend_Controller_Router_Route('media/:uuid/full', array('controller' => 'media', 'action' => 'fullview'));
// Add it to the router
$router->addRoute('mediaFullView', $mediaViewFullRoute); // 'subjects refers to a unique route name

// end media
// ---------

// subjects atom
$mediaAtomRoute = new Zend_Controller_Router_Route_Regex('media/(.*)\.atom',
                                                array('controller' => 'media', 'action' => 'atom'),
                                                array(1 => 'uuid'), 'media/%s.atom');

// add it to the router
$router->addRoute('mediaAtom', $mediaAtomRoute);

// xml media
$mediaXMLRoute = new Zend_Controller_Router_Route_Regex('media/(.*)\.xml',
                                                array('controller' => 'media', 'action' => 'xml'),
                                                array(1 => 'uuid'), 'media/%s.xml');

// add it to the router
$router->addRoute('mediaXML', $mediaXMLRoute);





// ---------
// Persons
// person view
$persViewRoute = new Zend_Controller_Router_Route('persons/:person_uuid', array('controller' => 'persons', 'action' => 'view'));
// Add it to the router
$router->addRoute('personsView', $persViewRoute); // 'subjects refers to a unique route name
// person atom
$persAtomRoute = new Zend_Controller_Router_Route_Regex('persons/(.*)\.atom',
                                                array('controller' => 'persons', 'action' => 'atom'),
                                                array(1 => 'person_uuid'), 'persons/%s.atom');

// add it to the router
$router->addRoute('personAtom', $persAtomRoute);

$persXMLRoute = new Zend_Controller_Router_Route_Regex('persons/(.*)\.xml',
                                                array('controller' => 'persons', 'action' => 'xml'),
                                                array(1 => 'person_uuid'), 'persons/%s.xml');

// add it to the router
$router->addRoute('personXML', $persXMLRoute);

// person json
$persJsonRoute = new Zend_Controller_Router_Route_Regex('persons/(.*)\.json',
                                                array('controller' => 'persons', 'action' => 'json'),
                                                array(1 => 'person_uuid'), 'persons/%s.json');

// add it to the router
$router->addRoute('personJson', $persJsonRoute);







// ---------
// Projects
// projects view
$projViewRoute = new Zend_Controller_Router_Route('projects/:proj_uuid', array('controller' => 'projects', 'action' => 'view'));
// Add it to the router
$router->addRoute('projectsView', $projViewRoute); // 'subjects refers to a unique route name
// project atom
$projAtomRoute = new Zend_Controller_Router_Route_Regex('projects/(.*)\.atom',
                                                array('controller' => 'projects', 'action' => 'atom'),
                                                array(1 => 'proj_uuid'), 'projects/%s.atom');

// add it to the router
$router->addRoute('projectAtom', $projAtomRoute);

// project json
$projJsonRoute = new Zend_Controller_Router_Route_Regex('projects/(.*)\.json',
                                                array('controller' => 'projects', 'action' => 'json'),
                                                array(1 => 'proj_uuid'), 'projects/%s.json');

// add it to the router
$router->addRoute('projectJson', $projJsonRoute);

// project atom
$projXMLRoute = new Zend_Controller_Router_Route_Regex('projects/(.*)\.xml',
                                                array('controller' => 'projects', 'action' => 'xml'),
                                                array(1 => 'proj_uuid'), 'projects/%s.xml');

// add it to the router
$router->addRoute('projectXML', $projXMLRoute);



$SubprojViewRoute = new Zend_Controller_Router_Route('projects/:proj_uuid/:class_name', array('controller' => 'projects', 'action' => 'subproj'));
// Add it to the router
$router->addRoute('SubProjectsView', $SubprojViewRoute); // 'subjects refers to a unique route name

$SubprojAtomRoute = new Zend_Controller_Router_Route_Regex('projects/(.*)/(.*)\.atom',
                                                array('controller' => 'projects', 'action' => 'subatom'),
                                                array(1 => 'proj_uuid', 2 => 'class_name'), 'projects/%s/.atom');

// add it to the router
$router->addRoute('subprojectAtom', $SubprojAtomRoute);

$SubprojJsonRoute = new Zend_Controller_Router_Route_Regex('projects/(.*)/(.*)\.json',
                                                array('controller' => 'projects', 'action' => 'subjson'),
                                                array(1 => 'proj_uuid', 2 => 'class_name'), 'projects/%s/.json');

// add it to the router
$router->addRoute('subprojectJson', $SubprojJsonRoute);


// ---------
// Documents
// documents view
$docViewRoute = new Zend_Controller_Router_Route('documents/:uuid', array('controller' => 'documents', 'action' => 'view'));
// Add it to the router
$router->addRoute('documentsView', $docViewRoute); // 'subjects refers to a unique route name
// person atom
$docAtomRoute = new Zend_Controller_Router_Route_Regex('documents/(.*)\.atom',
                                                array('controller' => 'documents', 'action' => 'atom'),
                                                array(1 => 'uuid'), 'documents/%s.atom');

// add it to the router
$router->addRoute('documentsAtom', $docAtomRoute);

$docXMLRoute = new Zend_Controller_Router_Route_Regex('documents/(.*)\.xml',
                                                array('controller' => 'documents', 'action' => 'xml'),
                                                array(1 => 'uuid'), 'documents/%s.xml');

// add it to the router
$router->addRoute('documentsXML', $docXMLRoute);




//------
//All or Archival Feed data
//------
$allAtomRouteA = new Zend_Controller_Router_Route('all.atom', array('controller' => 'all', 'action' => 'atom'));
$router->addRoute('allAtomA', $allAtomRouteA);

$allAtomRoute = new Zend_Controller_Router_Route_Regex('all/(.*)\.atom',
                                                array('controller' => 'all', 'action' => 'atom'),
                                                array(1 => 'nothing'), 'documents/%s.atom');

// add it to the router
$router->addRoute('allAtom', $allAtomRoute);




// ---------
// Properties
// properties view
$propViewRoute = new Zend_Controller_Router_Route('properties/:property_uuid', array('controller' => 'properties', 'action' => 'view'));
// Add it to the router
$router->addRoute('propertiesView', $propViewRoute); // 'subjects refers to a unique route name

$propXMLRoute = new Zend_Controller_Router_Route_Regex('properties/(.*)\.xml',
                                                array('controller' => 'properties', 'action' => 'xml'),
                                                array(1 => 'property_uuid'), 'properties/%s.xml');

// add it to the router
$router->addRoute('propertiesXML', $propXMLRoute);


// person atom
$propAtomRoute = new Zend_Controller_Router_Route_Regex('properties/(.*)\.atom',
                                                array('controller' => 'properties', 'action' => 'atom'),
                                                array(1 => 'property_uuid'), 'properties/%s.atom');

// add it to the router
$router->addRoute('propertiesAtom', $propAtomRoute);

$propJSONRoute = new Zend_Controller_Router_Route_Regex('properties/(.*)\.json',
                                                array('controller' => 'properties', 'action' => 'json'),
                                                array(1 => 'property_uuid'), 'properties/%s.json');

// add it to the router
$router->addRoute('propertiesJSON', $propJSONRoute);




// the About / services controller
$aboutServicesRoute = new Zend_Controller_Router_Route_Regex('about/services/(.*)', array('controller' => 'about', 'action' => 'services'));

$router->addRoute('aboutServices', $aboutServicesRoute);





// ---------
// Contexts

// contexts view - handle root-level items
$contextsViewRootRoute = new Zend_Controller_Router_Route_Regex('contexts/(.*)',
                                                            array('controller' => 'contexts', 'action' => 'view'),
                                                            array(1 => 'item_label'), 'contexts/%s');
// add it to the router
$router->addRoute('contextsViewRoot', $contextsViewRootRoute);


// contexts view
$contextsViewRoute = new Zend_Controller_Router_Route_Regex('contexts/(.*)/(.*)',
                                                            array('controller' => 'contexts', 'action' => 'view'),
                                                            array(1 => 'default_context_path', 2 => 'item_label'), 'contexts/%s/%s');
// add it to the router
$router->addRoute('contextsView', $contextsViewRoute);


// contexts atom - handle root-level items
$contextsAtomRootRoute = new Zend_Controller_Router_Route_Regex('contexts/(.*)\.atom',
                                                            array('controller' => 'contexts', 'action' => 'atom'),
                                                            array(1 => 'item_label'), 'contexts/%s.atom');
// add it to the router
$router->addRoute('contextsRootAtom', $contextsAtomRootRoute);


// Tables (OLD)
/*
$tablesViewRoute = new Zend_Controller_Router_Route('tables/:tableid', array('controller' => 'tables', 'action' => 'view'));
// Add it to the router
$router->addRoute('tablesView', $tablesViewRoute); // 'tables refers to a unique route name

$tableAddRoute = new Zend_Controller_Router_Route_Regex('createtab/newtable', array('controller' => 'createtab', 'action' => 'newtable'));
// add it to the router
$router->addRoute('createtabAdd', $tableAddRoute);

// make JSON for getting fields used in a given set, needed for knowing what fields are used in a table
$TablesSetsfieldsJsonRoute = new Zend_Controller_Router_Route_Regex('createtab/setsfields/(.*)\.json', array('controller' => 'createtab', 'action' => 'setfields'), array(1 => 'default_context_path'), 'tables/%s/');

$router->addRoute('setsfieldsJson', $TablesSetsfieldsJsonRoute);

//make JSON for populating a table with the correct fields
$TableopoulatefieldsJsonRoute = new Zend_Controller_Router_Route_Regex('createtab/tablepopulate/(.*)\.json', array('controller' => 'createtab', 'action' => 'tablepopulate'), array(1 => 'default_context_path'), 'tables/%s/');

$router->addRoute('tablepopulateJson', $TableopoulatefieldsJsonRoute);
*/

// Tables
$tablesAtomRoute = new Zend_Controller_Router_Route('tables.atom', array('controller' => 'tables', 'action' => 'atom'));
$router->addRoute('tablesAtom', $tablesAtomRoute); // 'tables refers to a unique route name

$tablesSearchRoute = new Zend_Controller_Router_Route('tables/search/:tag', array('controller' => 'tables', 'action' => 'search'));
$router->addRoute('tablesSearch', $tablesSearchRoute); // 'tables refers to a unique route name

$tablesViewRouteA = new Zend_Controller_Router_Route('tables/:tableid', array('controller' => 'tables', 'action' => 'view'));
$router->addRoute('tablesViewA', $tablesViewRouteA); // 'tables refers to a unique route name

$tablesViewRouteB = new Zend_Controller_Router_Route('tables/:tableid/:partID', array('controller' => 'tables', 'action' => 'view'));
$router->addRoute('tablesViewB', $tablesViewRouteB); // 'tables refers to a unique route name

$tableCSVRoute = new Zend_Controller_Router_Route_Regex('tables/(.*)\.csv',array('controller' => 'file', 'action' => 'downloadcsv'),array(1 => 'tableid'));
$router->addRoute('tablesCSVView', $tableCSVRoute); // 'tables refers to a unique route name

$tableJSONRoute = new Zend_Controller_Router_Route_Regex('tables/(.*)\.json',array('controller' => 'tables', 'action' => 'tabjson'),array(1 => 'tableid'));
$router->addRoute('tablesJSONView', $tableJSONRoute); // 'tables refers to a unique route name

$tabAtomRoute = new Zend_Controller_Router_Route_Regex('tables/(.*)\.atom',array('controller' => 'tables', 'action' => 'tabatom'),array(1 => 'tableid'));
$router->addRoute('tabAtomView', $tabAtomRoute); // 'tables refers to a unique route name

$tablesGoogleRoute = new Zend_Controller_Router_Route('tables/googleservice', array('controller' => 'tables', 'action' => 'googleservice'));
$router->addRoute('tablesGoogle', $tablesGoogleRoute); // 'tables refers to a unique route name

$tablesHelpRoute = new Zend_Controller_Router_Route('tables/help', array('controller' => 'tables', 'action' => 'help'));
$router->addRoute('tablesHelp', $tablesHelpRoute); // 'tables refers to a unique route name

$tablesUpdateRoute = new Zend_Controller_Router_Route('tables/update', array('controller' => 'tables', 'action' => 'update'));
$router->addRoute('tablesUpdate', $tablesUpdateRoute); // 'tables refers to a unique route name



$tableAddRoute = new Zend_Controller_Router_Route_Regex('createtab/newtable', array('controller' => 'createtab', 'action' => 'newtable'));
// add it to the router
$router->addRoute('createtabtestAdd', $tableAddRoute);

// make JSON for getting fields used in a given set, needed for knowing what fields are used in a table
$TablesSetsfieldsJsonRoute = new Zend_Controller_Router_Route_Regex('createtab/setfields/(.*)', array('controller' => 'createtab', 'action' => 'setfields'), array(1 => 'default_context_path'), 'tables/%s/');

$router->addRoute('setsfieldsJson', $TablesSetsfieldsJsonRoute);


//make JSON for populating a table with the correct fields
$TableopoulatefieldsJsonRoute = new Zend_Controller_Router_Route_Regex('createtab/tablepopulate/(.*)', array('controller' => 'createtab', 'action' => 'tablepopulate'), array(1 => 'default_context_path'), 'tables/%s/');

$router->addRoute('tablepopulateJson', $TableopoulatefieldsJsonRoute);







//------------------
// Sets

// the HTML version
$setsViewRoute = new Zend_Controller_Router_Route_Regex('sets/(.*)', array('controller' => 'sets', 'action' => 'index'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsView', $setsViewRoute);


// the Atom Result Feed

$setsResultsRoute = new Zend_Controller_Router_Route_Regex('sets/(.*)\.atom', array('controller' => 'sets', 'action' => 'results'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsResults', $setsResultsRoute);

// Map route for testing
$setsMapRoute = new Zend_Controller_Router_Route_Regex('sets/(.*)\.map', array('controller' => 'sets', 'action' => 'map'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsMap', $setsMapRoute);


// the JSON Result

$setsJsonRoute = new Zend_Controller_Router_Route_Regex('sets/(.*)\.json', array('controller' => 'sets', 'action' => 'json'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsJson', $setsJsonRoute);


// the Open Search service version
$setsSearchRoute = new Zend_Controller_Router_Route_Regex('sets/search/(.*)\.xml', array('controller' => 'sets', 'action' => 'search'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsSearch', $setsSearchRoute);

// the Atom Facet Feed
$setsFacetsRoute = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.atom', array('controller' => 'sets', 'action' => 'facets'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsFacets', $setsFacetsRoute);




// the KML results Version
$setsKMLRoute = new Zend_Controller_Router_Route_Regex('sets/(.*)\.kml', array('controller' => 'sets', 'action' => 'kmlresults'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('kmlGoogle', $setsKMLRoute);
// the KML Facet Version
$setsKMLRoute2 = new Zend_Controller_Router_Route_Regex('sets/(.*)\.kml;balloonFlyto', array('controller' => 'sets', 'action' => 'kmlresults'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('kmlGoogle2', $setsKMLRoute2);
// the KML Facet Version
$setsKMLRoute3 = new Zend_Controller_Router_Route_Regex('sets/(.*)\.kml;balloon', array('controller' => 'sets', 'action' => 'kmlresults'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('kmlGoogle3', $setsKMLRoute3);
$setsKMLRoute4 = new Zend_Controller_Router_Route_Regex('sets/(.*)\.kml;flyto', array('controller' => 'sets', 'action' => 'kmlresults'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('kmlGoogle4', $setsKMLRoute4);

// the KML Facet Version
$setsGoogleRoute = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.kml', array('controller' => 'sets', 'action' => 'googearth'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('setsGoogle', $setsGoogleRoute);

// the KML Facet Version
$setsGoogleRoute2 = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.kml;balloonFlyto', array('controller' => 'sets', 'action' => 'googearth'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('setsGoogle2', $setsGoogleRoute2);
// the KML Facet Version
$setsGoogleRoute3 = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.kml;balloon', array('controller' => 'sets', 'action' => 'googearth'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('setsGoogle3', $setsGoogleRoute3);
$setsGoogleRoute4 = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.kml;flyto', array('controller' => 'sets', 'action' => 'googearth'), array(1 => 'default_context_path'), 'sets/%s/');
$router->addRoute('setsGoogle4', $setsGoogleRoute4);




// the JSON Facet Version
$setsFJSONRoute = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.json', array('controller' => 'sets', 'action' => 'jsonfacets'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsFJSON', $setsFJSONRoute);

// the JSON Reconciliation Version
$setsReconJSONRoute = new Zend_Controller_Router_Route_Regex('sets/reconciliation/(.*)\.json', array('controller' => 'sets', 'action' => 'jsonreconciliation'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsReconJSON', $setsReconJSONRoute);



// the JSON Facet Version
$setsTMAPRoute = new Zend_Controller_Router_Route_Regex('sets/facets/(.*)\.tmap', array('controller' => 'sets', 'action' => 'tmapjson'), array(1 => 'default_context_path'), 'sets/%s/');

$router->addRoute('setsTMap', $setsTMAPRoute);



// end of sets



//-------
//Map
// the HTML version
$mapViewRoute = new Zend_Controller_Router_Route_Regex('maps/(.*)', array('controller' => 'maps', 'action' => 'index'), array(1 => 'default_context_path'), 'maps/%s/');

$router->addRoute('mapsView', $mapViewRoute);


// the JSON TimeMap data (for AJAX interactions)
$mapJsonRoute = new Zend_Controller_Router_Route_Regex('maps/details/(.*)\.json', array('controller' => 'maps', 'action' => 'mapjson'), array(1 => 'default_context_path'), 'maps/details/%s/');

$router->addRoute('mapsJson', $mapJsonRoute);


// the JSON TimeMap initial dataset (from projects)
$mapTimeMapJSONRoute = new Zend_Controller_Router_Route('maps/time-map.json', array('controller' => 'maps', 'action' => 'timemapjson'));

$router->addRoute('mapsTimeMapJSON', $mapTimeMapJSONRoute);


//------------------
// Lightbox

// the HTML version
$lightboxViewRoute = new Zend_Controller_Router_Route_Regex('lightbox/(.*)', array('controller' => 'lightbox', 'action' => 'index'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxView', $lightboxViewRoute);


// the Atom Result Feed

$lightboxResultsRoute = new Zend_Controller_Router_Route_Regex('lightbox/(.*)\.atom', array('controller' => 'lightbox', 'action' => 'results'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxResults', $lightboxResultsRoute);



// the JSON Result
$lightboxJsonRoute = new Zend_Controller_Router_Route_Regex('lightbox/(.*)\.json', array('controller' => 'lightbox', 'action' => 'json'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxJson', $lightboxJsonRoute);


// the Open Search service version
$lightboxSearchRoute = new Zend_Controller_Router_Route_Regex('lightbox/search/(.*)\.xml', array('controller' => 'lightbox', 'action' => 'search'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxSearch', $lightboxSearchRoute);


// the Atom Facet Feed
$lightboxFacetsRoute = new Zend_Controller_Router_Route_Regex('lightbox/facets/(.*)\.atom', array('controller' => 'lightbox', 'action' => 'facets'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxFacets', $lightboxFacetsRoute);


// the KML Facet Version
$lightboxGoogleRoute = new Zend_Controller_Router_Route_Regex('lightbox/facets/(.*)\.kml', array('controller' => 'lightbox', 'action' => 'googearth'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxGoogle', $lightboxGoogleRoute);

// the JSON Facet Version
$lightboxFJSONRoute = new Zend_Controller_Router_Route_Regex('lightbox/facets/(.*)\.json', array('controller' => 'lightbox', 'action' => 'jsonfacets'), array(1 => 'default_context_path'), 'lightbox/%s/');

$router->addRoute('lightboxFJSON', $lightboxFJSONRoute);



// end of slides









// ---------
// SEARCH  - This is for re-routing requests from the old Open context 
// documents view
$searchRoute = new Zend_Controller_Router_Route('search.html', array('controller' => 'search', 'action' => 'index'));
// Add it to the router
$router->addRoute('searchIn', $searchRoute); // 'search route


//------------------
// Search

// the HTML version
$searchViewRoute = new Zend_Controller_Router_Route_Regex('search/(.*)', array('controller' => 'search', 'action' => 'index'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchView', $searchViewRoute);


// the Atom Result Feed
$searchResultsRoute = new Zend_Controller_Router_Route_Regex('search/(.*)\.atom', array('controller' => 'search', 'action' => 'results'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchResults', $searchResultsRoute);



// the JSON Result
$searchJsonRoute = new Zend_Controller_Router_Route_Regex('search/(.*)\.json', array('controller' => 'search', 'action' => 'json'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchJson', $searchJsonRoute);


// the Open Search service version
$searchSearchRoute = new Zend_Controller_Router_Route_Regex('search/search/(.*)\.xml', array('controller' => 'search', 'action' => 'search'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchSearch', $searchSearchRoute);


// the Atom Facet Feed
$searchFacetsRoute = new Zend_Controller_Router_Route_Regex('search/facets/(.*)\.atom', array('controller' => 'search', 'action' => 'facets'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchFacets', $searchFacetsRoute);


// the KML Facet Version
$searchGoogleRoute = new Zend_Controller_Router_Route_Regex('search/facets/(.*)\.kml', array('controller' => 'search', 'action' => 'googearth'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchGoogle', $searchGoogleRoute);

// the JSON Facet Version
$searchFJSONRoute = new Zend_Controller_Router_Route_Regex('search/facets/(.*)\.json', array('controller' => 'search', 'action' => 'jsonfacets'), array(1 => 'default_context_path'), 'search/%s/');
$router->addRoute('searchFJSON', $searchFJSONRoute);












//------------------
// Table-Browse


// the HTML version
$tableBrowseViewRoute = new Zend_Controller_Router_Route_Regex('table-browse/(.*)', array('controller' => 'table-browse', 'action' => 'index'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseView', $tableBrowseViewRoute);


// the Atom Result Feed
$tableBrowseResultsRoute = new Zend_Controller_Router_Route_Regex('table-browse/(.*)\.atom', array('controller' => 'table-browse', 'action' => 'results'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseResults', $tableBrowseResultsRoute);



// the JSON Result
$tableBrowseJsonRoute = new Zend_Controller_Router_Route_Regex('table-browse/(.*)\.json', array('controller' => 'table-browse', 'action' => 'json'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseJson', $tableBrowseJsonRoute);


// the Open tableBrowse service version
$tableBrowseOSearchRoute = new Zend_Controller_Router_Route_Regex('table-browse/search/(.*)\.xml', array('controller' => 'table-browse', 'action' => 'tableBrowse'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseOSearchRoute', $tableBrowseOSearchRoute);


// the Atom Facet Feed
$tableBrowseFacetsRoute = new Zend_Controller_Router_Route_Regex('table-browse/facets/(.*)\.atom', array('controller' => 'table-browse', 'action' => 'facets'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseFacets', $tableBrowseFacetsRoute);


// the KML Facet Version
$tableBrowseGoogleRoute = new Zend_Controller_Router_Route_Regex('table-browse/facets/(.*)\.kml', array('controller' => 'table-browse', 'action' => 'googearth'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseGoogle', $tableBrowseGoogleRoute);

// the JSON Facet Version
$tableBrowseFJSONRoute = new Zend_Controller_Router_Route_Regex('table-browse/facets/(.*)\.json', array('controller' => 'table-browse', 'action' => 'jsonfacets'), array(1 => 'default_context_path'), 'table-browse/%s/');
$router->addRoute('tableBrowseFJSON', $tableBrowseFJSONRoute);








// end of slides









//Robots.txt -this is for re-routing
//
$robotsRoute = new Zend_Controller_Router_Route('robots.txt', array('controller' => 'index', 'action' => 'robots'));
// Add it to the router
$router->addRoute('robotsIn', $robotsRoute); // 'search route


$historyRoute = new Zend_Controller_Router_Route('__history__.html', array('controller' => 'index', 'action' => 'history'));
// Add it to the router
$router->addRoute('historyIndex', $historyRoute); // 'search route






/*
// ---------
// LOGIN  - This is for re-routing requests from the old Open context 
// documents view
$registerRoute = new Zend_Controller_Router_Route('login/register', array('controller' => 'login', 'action' => 'register'));
// Add it to the router
$router->addRoute('newregister', $registerRoute); // 'search route
*/




// ---------
// DATABASE  - This is for re-routing requests from the old Open context 
// documents view
$databaseSpaceRoute = new Zend_Controller_Router_Route('database/space\.php', array('controller' => 'database', 'action' => 'space'));
// Add it to the router
$router->addRoute('databaseSp', $databaseSpaceRoute); // 'subjects refers to a unique route name

$databaseProjRoute = new Zend_Controller_Router_Route('database/project\.php', array('controller' => 'database', 'action' => 'project'));
// Add it to the router
$router->addRoute('databaseProj', $databaseProjRoute); // 'subjects refers to a unique route name

$databaseResRoute = new Zend_Controller_Router_Route('database/resource\.php', array('controller' => 'database', 'action' => 'resource'));
// Add it to the router
$router->addRoute('databaseRes', $databaseResRoute); // 'subjects refers to a unique route name



//OAI
$OAIrequest = new Zend_Controller_Router_Route('oai/request', array('controller' => 'oai', 'action' => 'request'));
// Add it to the router
$router->addRoute('OAIrequest', $OAIrequest); // 'subjects refers to a unique route name


//OAI
$OCFeedRequest = new Zend_Controller_Router_Route('test/oc-feeds', array('controller' => 'test', 'action' => 'oc-feeds'));
// Add it to the router
$router->addRoute('OCFeedRequest', $OCFeedRequest); // 'subjects refers to a unique route name



//ARK
$arkViewRoute = new Zend_Controller_Router_Route('ref/:ark/:noidPrefix/:noidSuffix', array('controller' => 'ark', 'action' => 'view'));
// Add it to the router
$router->addRoute('arkViewer', $arkViewRoute); // 'subjects refers to a unique route name

//ARK
$arkMintRoute = new Zend_Controller_Router_Route('ref/mint-id/:item_type/:item_uuid', array('controller' => 'ark', 'action' => 'mintID'));
// Add it to the router
$router->addRoute('arkMint', $arkMintRoute); // 'subjects refers to a unique route name


//components for sending consolidated / compressed javascript and css
$compCSS_ViewRoute = new Zend_Controller_Router_Route_Regex('components/css/(.*)', array('controller' => 'components', 'action' => 'css'), array(1 => 'pageID'), 'components/%s/');
$router->addRoute('comp_css_View', $compCSS_ViewRoute ); // 'GETS compressed css for a page

$compJS_ViewRoute = new Zend_Controller_Router_Route_Regex('components/js/(.*)', array('controller' => 'components', 'action' => 'js'), array(1 => 'pageID'), 'components/%s/');
$router->addRoute('comp_js_View', $compJS_ViewRoute ); // 'GETS compressed javascript for a page
/*
$compJS_js_ViewRoute = new Zend_Controller_Router_Route_Regex('components/js/(.*).js', array('controller' => 'components', 'action' => 'js'), array(1 => 'pageID'), 'components/%s/');
$router->addRoute('comp_js_js_View', $compJS_js_ViewRoute ); // 'GETS compressed javascript for a page
*/

//unAPI
/*
$unapiViewRoute = new Zend_Controller_Router_Route('unapi(.*)', array('controller' => 'unapi', 'action' => 'view'));
// Add it to the router
$router->addRoute('unapiView', $unapiViewRoute); // 'subjects refers to a unique route name
*/




$frontController->throwExceptions(true);
$frontController->setControllerDirectory('../application/controllers');
try {
    $frontController->dispatch();

}catch (Exception $e){
    // handle exceptions yourself
    
    if(stristr($e, "action") && stristr($e, "does not exist and was not trapped") ){
        
        unset($frontController);
        $frontController = Zend_Controller_Front::getInstance();
        $router = $frontController->getRouter();
        $router->removeDefaultRoutes();
        $allroute = new Zend_Controller_Router_Route('*', array('controller' => 'error', 'action' => 'not-found'));
        $router->addRoute('allroute', $allroute); // 'subjects refers to a unique route name
        $request = new Zend_Controller_Request_Http();
        $request->setModuleName("defaut")
                ->setControllerName("error")
                ->setActionName("not-found");
        $response   = $frontController->dispatch($request);
    }
    else{
        $frontController = Zend_Controller_Front::getInstance();
        $router = $frontController->getRouter();
        $router->removeDefaultRoutes();
        $allroute = new Zend_Controller_Router_Route('*', array('controller' => 'error', 'action' => 'not-available'));
        $router->addRoute('allroute', $allroute); // 'subjects refers to a unique route name
        $request = new Zend_Controller_Request_Http();
        $request->setModuleName("defaut")
                ->setControllerName("error")
                ->setActionName("not-found");
        $response   = $frontController->dispatch($request);
    }
    
    //echo $e;
}//end caught exceptions
?>    
