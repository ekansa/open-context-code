// mapping and identification of resource types
define('types', [], function() {
    
    // get canonical type name
    function map(name) {
        return typeMap[name] || name;
    }
    // get label for type
    function label(type) {
        return labels[map(type)] || 'Uncategorized';
    }
    // get plural lable for type
    function pluralLabel(type) {
        type = map(type);
        return type in pluralLabels ? labels[type] : labels[type] + 's';
    }
    // get type from CSS class(es)
    function fromClass(cls) {
        var classes = (cls || '').split(/\s+/),
            i = classes.length,
            match;
        while (i--)
            if (match = classes[i].match(/awld-type-(.+)/))
                return map(match[1]);
    }
    
    // set up types
    var TYPE_PERSON     = 'person',
        TYPE_PLACE      = 'place',
        TYPE_EVENT      = 'event',
        TYPE_CITATION   = 'citation',
        TYPE_TEXT       = 'text',
        TYPE_OBJECT     = 'object',
        TYPE_DESCRIPTION = 'description',
        // type maps
        types = [TYPE_CITATION, TYPE_EVENT, TYPE_PERSON, 
                 TYPE_PLACE, TYPE_OBJECT, TYPE_TEXT, TYPE_DESCRIPTION],
        labels = {},
        pluralLabels = {},
        typeMap = {};
    
    // set labels
    labels[TYPE_PERSON]     = 'Person';
    labels[TYPE_PLACE]      = 'Place';
    labels[TYPE_EVENT]      = 'Event';
    labels[TYPE_CITATION]   = 'Bibliographic Citation';
    labels[TYPE_TEXT]       = 'Text';
    labels[TYPE_OBJECT]     = 'Physical Object';
    labels[TYPE_DESCRIPTION] = 'Description';
    
    // map alternate type names
    typeMap['dc:Agent']     = TYPE_PERSON;
    typeMap['foaf:Person']  = TYPE_PERSON;
    typeMap['dc:Location']  = TYPE_PLACE;
    typeMap['dc:BibliographicResource'] = TYPE_CITATION;
    typeMap['dcmi:PhysicalObject']      = TYPE_OBJECT;
    typeMap['dcmi:Event']   = TYPE_EVENT;
    typeMap['dcmi:Text']    = TYPE_TEXT;
    typeMap['dc:description'] = TYPE_DESCRIPTION;
    
    return {
        types: types,
        map: map,
        label: label,
        pluralLabel: pluralLabel,
        fromClass: fromClass
    }
});
