function onCheckbox(e) {
    row = $(e.parentNode.parentNode);
    $(':radio', row)
	.prop('disabled', !e.checked)
	.prop('checked', false)
	.change();
}

function onRadio(e) {
    if ($(':checked:radio').length) {
	$(':submit')
	    .attr('disabled', false)
	    .addClass('btn-success');
    }else{
	$(':submit')
	    .attr('disabled', true)
	    .removeClass('btn-success');
    }
}

function onName(e) {
    row = $(e.parentNode.parentNode);
    name = e.value;
    if ((name.length) && (row.attr('id') == 'template')) {
	tmplRow = row.clone(true);
	$(':text', tmplRow).val('');
	row
	    .removeAttr('id')
	    .after(tmplRow);
    }
    $(':checkbox', row)
	.prop('disabled', !name.length)
	.prop('checked', false)
	.change();
    $(':radio, :checkbox', row)
	.prop('value', name);

    if ((!name.length) && (row.attr('id') != 'template')) {
	row.remove();
	$(':text', tmplRow).focus();
    }
}
