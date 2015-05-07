Number.prototype.round = function(places) {
    return +(Math.round(this + "e+" + places)  + "e-" + places);
}

function fractionToFloat(str)
{
    // Check for complex fraction
var complexParts = str.split(' ');

var result = 0;
var f;

if (complexParts.length == 1) {
    f = complexParts[0];
}
else {
    result += parseInt(complexParts[0]);
    f = complexParts[1];
}

var parts = f.split('/');

if (parts.length == 1) {
    // No fractions
    return str;
}
else {
    result += parseInt(parts[0]) / parseInt(parts[1]);
}

return result;
}
