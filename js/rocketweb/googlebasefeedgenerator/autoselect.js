function rwAutoSelectOptions(key) {
    var Params = document.URL.toQueryParams();
    var select = $('attribute'+key);
    if (select != null && typeof select != 'undefined') {
        for (var i=0; i<select.options.length; i++){
            if (select.options[i].value == Params[key]) {
                select.selectedIndex = i;
                if ("createEvent" in document) {
                    var evt = document.createEvent("HTMLEvents");
                    evt.initEvent("change", false, true);
                    select.dispatchEvent(evt);
                }
                else {
                    select.fireEvent("onchange");
                }
            }
        }
    }
};

function rwAutoUpdate(){
    var attributeId = this.id.replace(/[a-z]*/, '');
    rwAutoSelectOptions(attributeId);
};

Event.observe(window, 'load', function() {
    var n = 1;
    $$('.super-attribute-select').each(function(element){
        window.setTimeout(rwAutoUpdate.bind(element), 300*n++);
    });
});