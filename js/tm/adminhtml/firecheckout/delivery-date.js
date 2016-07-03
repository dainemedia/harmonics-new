var FireCheckout = {};

Function.prototype.firecheckoutInterceptor = function(fcn, scope) {
    var method = this;
    return (typeof fcn !== 'function') ?
        this :
        function() {
            var me   = this,
                args = arguments;
            fcn.target = me;
            fcn.method = method;
            return (fcn.apply(scope || me || window, args) !== false) ?
                method.apply(me || window, args) :
                null;
        };
}

FireCheckout.DeliveryDate = Class.create();
FireCheckout.DeliveryDate.prototype = {

    config: {
        excludedDates     : [],
        periodicalDates   : [],
        nonPeriodicalDates: [],
        excludeWeekend    : 0,
        useCalendar       : 1,
        weekendDays       : '0,6',
        ifFormat          : '%m/%e/%Y',
        shippingMethods   : [],
        todayOffset       : 0
    },

    initialize: function(options) {
        Object.extend(this.config, options);

        if (options.startDate) {
            var todayParts = options.startDate.split(' ');
            todayParts = todayParts[0].split('-');
            var today = new Date(todayParts[0], todayParts[1] - 1, todayParts[2]);
        } else {
            var today = new Date();
        }
        today.setDate(today.getDate() + this.config.todayOffset);
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);
        today.setMilliseconds(0);
        this.today = today;

        if (this.config.useCalendar) {
            var label = $('shipping_form_delivery_date').down('.delivery-date label'),
                html  = label.innerHTML,
                date = new Date();

            date.setDate(25);
            date.setMonth(10);

            label.update(html.replace('{{date}}', date.print(this.config.ifFormat)));
            this.initCalendar();
        }

        this.toggleDisplay();

        var self = this;
        $$('input[name="order[shipping_method]"]').each(function(el) {
            el.observe('click', function() {
                self.toggleDisplay(this.value);
            });
        });
    },

    toggleDisplay: function(shippingMethod) {
        if (!this.config.shippingMethods.length) {
            return;
        }

        if (!shippingMethod) {
            $('order-shipping_method').select('input[name="order[shipping_method]"]').each(function(el) {
                if (el.checked || el.readAttribute('checked') === 'checked') {
                    shippingMethod = el.value;
                    return;
                }
            });
        }

        if (-1 === this.config.shippingMethods.indexOf(shippingMethod)) {
            $('shipping_form_delivery_date').hide();
        } else {
            $('shipping_form_delivery_date').show();
        }
    },

    initCalendar: function() {
        var self = this;

        Calendar.prototype._init = Calendar.prototype._init.firecheckoutInterceptor(
            function(firstDayOfWeek, date) {
                if ('delivery_date' === this.params.inputField.id) {
                    return self.shiftToFirstAvailableDate(date);
                }
            }
        );

        Calendar.setup({
            inputField : 'delivery_date',
            ifFormat   : this.config.ifFormat,
            weekNumbers: false,
            button     : 'delivery_date_button',
            // hiliteToday: false,
            showOthers : true,
            range      : [
                self.today.getFullYear(),
                self.today.getFullYear() + 1
            ],
            disableFunc: function(date) {
//                if (self.today > date) {
//                    return true;
//                }

                if (self.config.excludeWeekend && self.config.weekendDays.indexOf(date.getDay()) >= 0) {
                    return true;
                }

                var i     = 0,
                    sDate = self.dateToString(date);
                while (self.config.excludedDates[i]) {
                    if (0 === sDate.indexOf(self.config.excludedDates[i])) {
                        return true;
                    }
                    i++;
                }
                return false;
            }
        });
    },

    shiftToFirstAvailableDate: function(date) {
        var i = 0,
            c = this.config;

        while (-1 != c.nonPeriodicalDates.indexOf(this.dateToString(date))
            || -1 != c.periodicalDates.indexOf(this.dateToString(date).substr(0, 7))
            || (c.excludeWeekend && -1 != c.weekendDays.indexOf(date.getDay()))
            /*|| this.today > date*/) {

            date.setDate(date.getDate() + 1);
            if (i++ >= 365) {
                // possible continious loop fix
                // alert('There are no avaialable dates for the possible delivery within ' + i + ' days');
                return false;
            }
        }

        return true;
    },

    dateToString: function(date) {
        var year  = date.getFullYear(),
            month = date.getMonth() + 1,
            day   = date.getDate(),
            month = month < 10 ? '0' + month : month,
            day   = day   < 10 ? '0' + day   : day;

        return month + '/' + day + '/' + year;
    }
};
