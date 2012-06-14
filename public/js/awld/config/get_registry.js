var fs = require('fs'); 

// a bit ugly
var text = fs.readFileSync('src/registry.js').toString();
global.define = function(name, o) { return o };
var registry = eval(text);

// spit out registered modules
var out = [];
for (var key in registry) {
    out.push(registry[key]);
}
console.log(out.join(','));