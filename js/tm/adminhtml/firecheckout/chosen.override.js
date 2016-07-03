Chosen.prototype.result_select = Chosen.prototype.result_select.wrap(function(original, evt) {
    if (this.result_highlight) {
        original(evt);
    } else {
        var value = this.search_field.value.escapeHTML();
        if (this.form_field.select('option[value="' + value + '"]').length) {
            original(evt);
            return;
        }
        this.form_field.insert('<option value="' + value + '">' + value + '</option>');
        Event.fire(this.form_field, 'chosen:updated');
    }
});
