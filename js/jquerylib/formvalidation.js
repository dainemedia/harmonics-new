Validation.add('validate-alphanum-with-spaces', 'Please use only letters (a-z or A-Z) or spaces only in this field.', function(v) {
     return Validation.get('IsEmpty').test(v) || /^[a-zA-Z ]+$/.test(v)
})
Validation.add('validate-alphanum', 'Please use only letters (a-z or A-Z), numbers (0-9) or spaces only in this field.', function(v) {
     return Validation.get('IsEmpty').test(v) || /^(?=.*?[0-9])[a-zA-Z0-9 ]+$/.test(v)
})


