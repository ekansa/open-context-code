// Module: Encyclopedia of Life HTML

define(['jquery'], function($) {
    return {
        name: 'EOL Entries',
        type: 'description',
        dataType: 'html',
        parseData: function(html) {
            var getText = awld.accessor(html);

            var name = getText('h1.scientific_name');
            name = typeof name === 'undefined' ? "EOL Entry" : "EOL Entry: " + name;
            
            var description = getText('#text_summary .copy');
            description = description.replace("/assets/","http://eol.org/assets/");
            description = typeof name === 'undefined' ? '' : description;

            return {
                name: name,
                description: description,
            };
        },
    };
});
