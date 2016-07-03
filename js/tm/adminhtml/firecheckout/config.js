document.observe("dom:loaded", function() {
    var firecheckoutAjaxRules = $$('#firecheckout_ajax select');
    if (firecheckoutAjaxRules.length) {
        firecheckoutAjaxRules.each(function(el) {
            new Chosen(el, {
                placeholder_text: ' ',
                no_results_text: 'Press Enter to add',
                width: '270px'
            });
        });
    }
});

var AddressSortObserver = Class.create({
    initialize: function(element, observer) {
        this.element  = $(element);
        this.observer = observer;
    },

    onEnd: function() {
        Sortable.unmark();
        this.observer(this.element);
    }
});

var AddressSort = Class.create();
AddressSort.prototype = {
    currentMode: 'dragdrop',

    config: {
        prefix : 'firecheckout_address_form_order_',
        suffix : /_draggable_\d+/,
        clearer: '<p class="clear newline"></p>',
        togglerPrefix: 'firecheckout_toggle_'
    },

    initialize: function(options) {
        this.config = Object.extend(this.config, options);

        this.container = $(options.dragdrop);
        this.elements  = this.container.childElements();

        this.prepareUseDefaultCheckboxes();
        this.addObservers();
        this.rebuild();

        var self = this;
        Sortable.create(options.dragdrop, {
            tag       : 'div',
            overlap   : 'horizontal',
            handles   : $$('#' + options.dragdrop + ' span.move-handle'),
            constraint: false
        });
        Draggables.addObserver(new AddressSortObserver(this.container, function() {
            self.refreshSortOrders();
            self.rebuild();
        }));

        // touchscreen fix
        var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        if ('ontouchstart' in document.documentElement
            && (!isFirefox || matchMedia('(-moz-touch-enabled)').matches)) {

            this.switchModeTo('classic');
        } else {
            this.switchModeTo('dragdrop');
        }
    },

    prepareUseDefaultCheckboxes: function() {
        var label    = $(this.config.classic).down('.use-default label'),
            checkbox = $(this.config.classic).down('.use-default .checkbox');

        if (!checkbox) {
            return;
        }

        $$('.firecheckout-sort-order-mode a').each(function(el) {
            var id      = el.id + '_checkbox',
                checked = checkbox.readAttribute('checked') || checkbox.checked;

            el.insert({
                after: '<input type="checkbox" class="use-default checkbox" id="' + id + '"'
                    + (checked ? ' checked="checked"' : '') + '>'
                    + '<label for="' + id + '">' + label.innerHTML + '</label>'
            });
        });

        // use default togglers
        var mapping = {
                'firecheckout_toggle_dragdrop_checkbox': 'firecheckout_toggle_classic_checkbox',
                'firecheckout_toggle_classic_checkbox' : 'firecheckout_toggle_dragdrop_checkbox'
            },
            self = this;
        $$('.firecheckout-sort-order-mode .use-default').each(function(el) {
            el.observe('click', function() {
                $('address_form_order_classic').select('.config-inherit').each(function(checkbox) {
                    checkbox.checked = el.checked;
                    toggleValueElements(checkbox, Element.previous(checkbox.parentNode));
                });
                var checkbox     = $(mapping[el.id]);
                checkbox.checked = el.checked;
                toggleValueElements(checkbox, Element.previous(checkbox.parentNode));
                self.toggleOverlay(el.checked);
            });
        });
    },

    addObservers: function() {
        var self = this;
        this.container.select('.new-line-trigger').each(function(el) {
            el.observe('click', function(e) {
                var next = this.up().nextSiblings();
                if (next.length) {
                    self.incrementSortOrders(next);
                    self.rebuild();
                }
            });
        });

        // mode togglers
        $$('.firecheckout-sort-order-mode a').each(function(el) {
            el.observe('click', function() {
                var mode = (-1 === this.id.indexOf('dragdrop') ? 'dragdrop' : 'classic');
                self.switchModeTo(mode);
            });
        });
    },

    toggleOverlay: function(flag) {
        var checkbox = $('firecheckout_toggle_dragdrop_checkbox');
        if (checkbox) {
            flag = flag && $('firecheckout_toggle_dragdrop_checkbox').checked;
        } else {
            flag = false;
        }

        // cover drag&drop fields with transparent div
        var dragdrop = $(this.config.dragdrop),
            overlay  = $(this.config.dragdrop + '_overlay');
        if (!flag) {
            overlay.hide();
        } else {
            if ('dragdrop' !== this.currentMode) {
                return;
            }
            overlay.setStyle({
                zIndex   : 10,
                width    : dragdrop.getWidth() + 'px',
                height   : dragdrop.getHeight() + 'px'
            });
            overlay.setOpacity(0.4);
            overlay.show();
        }
    },

    switchModeTo: function(mode) {
        this.currentMode = mode;

        var cnt = this.getContainer(mode);
        cnt.setStyle({
            display: ''
        });
        cnt.previous('.firecheckout-sort-order-mode').setStyle({
            display: ''
        });

        this.toggleOverlay('dragdrop' === mode);

        mode = ('dragdrop' === mode ? 'classic' : 'dragdrop');
        cnt = this.getContainer(mode);
        cnt.setStyle({
            display: 'none'
        });
        cnt.previous('.firecheckout-sort-order-mode').setStyle({
            display: 'none'
        });
    },

    getContainer: function(mode) {
        var cnt = $(this.config[mode]);
        if ('dragdrop' === mode) {
            cnt = cnt.up();
        }
        return cnt;
    },

    refreshSortOrders: function() {
        var self     = this, field,
            elements = this.container.childElements(),
            order    = 10;

        elements.each(function(el) {
            if (el.tagName.toLowerCase() !== 'div') {
                order++;
                return;
            }

            $(self.getInputFieldId(el.readAttribute('id'))).setValue(order++);
        });
    },

    incrementSortOrders: function(elements, increment) {
        increment = increment || 1;
        var i = 0,
            field, div;
        while ((div = elements[i++])) {
            if (div.tagName.toLowerCase() !== 'div') {
                //  we don't need to inrement sort order
                // if first of the next div's already on the new line
                if (i === 1) {
                    break;
                }
                continue;
            }
            field = $(this.getInputFieldId(div.readAttribute('id')));
            if (!field) {
                continue;
            }
            field.setValue(parseInt(field.getValue()) + increment);
        }
    },

    /**
     * Rebuild elements according to field values
     */
    rebuild: function() {
        var values = [],
            self   = this;

        this.elements.each(function(el) {
            var id       = el.readAttribute('id'),
                parentId = self.getInputFieldId(id),
                key      = parentId.replace(self.config.prefix, '');

            values.push({
                'id'   : id,
                'value': parseInt($(parentId).getValue())
            });
        });

        values.sort(function(a, b) {
            if (a.value > b.value) {
                return 1;
            } else if (a.value < b.value) {
                return -1;
            }
            return 0;
        });

        var prevOrder = 0,
            prevEl    = null;
        values.each(function(el) {
            if (!prevEl) {
                prevEl    = $(el.id);
                prevOrder = el.value;
                self.container.insert({
                    top: prevEl
                });
                return;
            }

            prevEl.insert({
                after: $(el.id)
            });

            if (el.value - prevOrder > 1) {
                prevEl.insert({
                    after: self.config.clearer
                });
            }

            prevEl    = $(el.id);
            prevOrder = el.value;
        });

        // remove clears from the end of container
        $(values.last().id).nextSiblings().invoke('remove');
    },

    getInputFieldId: function(divId) {
        return divId.replace(this.config.suffix, '');
    }
};

// script.aculo.us dragdrop.js v1.8.2 fixes
Draggables.register = function(draggable) {
    if(this.drags.length === 0) {
        this.eventMouseUp   = this.endDrag.bindAsEventListener(this);
        this.eventMouseMove = this.updateDrag.bindAsEventListener(this);
        this.eventKeypress  = this.keyPress.bindAsEventListener(this);

        Event.observe(document, "mouseup", this.eventMouseUp);
        Event.observe(document, "mousemove", this.eventMouseMove);
        Event.observe(document, "keypress", this.eventKeypress);
    }
    this.drags.push(draggable);
};

Draggables.unregister = function(draggable) {
    this.drags = this.drags.reject(function(d) { return d==draggable; });
    if (this.drags.length === 0) {
        Event.stopObserving(document, "mouseup", this.eventMouseUp);
        Event.stopObserving(document, "mousemove", this.eventMouseMove);
        Event.stopObserving(document, "keypress", this.eventKeypress);
    }
};
