var TextMap = function () {
    'use strict';
    // initialize map
    this.map = new Map($('#map'));
    // intialize text list
    this.textList = new TextList($('.text-list'), this.map);
};


var appRoot = new TextMap();
console.log('Initialization complete');

