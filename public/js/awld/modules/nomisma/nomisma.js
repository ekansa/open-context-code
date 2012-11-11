// Module: Nomisma.org API

define(['jquery'], function($) {
    return {
        name: 'Nomisma.org Entities',
        dataType: 'xml',
        // data URI is the same
        corsEnabled: true,
        parseData: function(xml) {
            var getText = awld.accessor(xml);


            var name = getText('[property="skos:prefLabel"]');
            name = typeof name === 'undefined' ? getText('[about]','about') : name;

            var description = getText('[property="skos:definition"]');
            description = typeof description === 'undefined' ? '' : description;

            // try getting latlon as property
            var latlon = getText('[property="gml:pos"]');
            // test if that worked, if not try as @content of @property = "findspot"
            if ( typeof latlon === 'undefined' ) { latlon = getText('[property="findspot"]','content') };
            // if stil undefined '', otherwise split
            latlon = typeof latlon === 'undefined' ? '' : latlon.split(' ');


            var related = getText('[rel*="skos:related"]', 'href')
            related = typeof related === 'undefined'? '' : related;

            return {
                name: name,
                description: description,
                latlon: latlon,
                related: related,
            };
        },
        getType: function(xml) {
            var map = {
                    'roman_emperor': 'person',
                    'ruler': 'person',
                    'authority': 'person',
                    'nomisma_region': 'place',
                    'hoard': 'place',
                    'mint': 'place',
                    'material': 'object',
                    'type_series_item': 'object',
                },
                type = $('[typeof]', xml).first().attr('typeof');
            if (type) return map[type];
        }
    };
});
