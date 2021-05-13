$(document).ready(function() {
    // Acción para el botón de expandir y colapsar
    $('button.resource-branch-manip').button({
        icons: {
            primary: 'ui-icon-minusthick'
        },
        text: false
    }).click(function(event) {
        event.preventDefault();
        var tr_button = $(this).parents('tr').first();
        if ($(this).children('span').hasClass('ui-icon-minusthick')) {
            collapseChildren(tr_button);
        } else {
            expandChildren(tr_button);
        }
    });

    $('input[type=checkbox][name="resource_access[]"], input[type=checkbox][name="privileges[]"]').each(function(index, element) {
        // Decorar los checkbox según cómo estén seteados
        $(this).button({
            icons: {
                primary: $(this).is(':checked') ? 'ui-icon-check' : 'ui-icon-blank'
            },
            text: false
        });
    });
    $('input[type=checkbox][name="privileges[]"]').click(function(event) {
        // Actualizar el icono según el nuevo estado
        $(this).button({
            icons: {
                primary: $(this).is(':checked') ? 'ui-icon-check' : 'ui-icon-blank'
            },
            text: false
        });
    });

    $('input[type=checkbox][name="resource_access[]"]').click(function(event) {
        // Actualizar el icono según el nuevo estado
        $(this).button({
            icons: {
                primary: $(this).is(':checked') ? 'ui-icon-check' : 'ui-icon-blank'
            },
            text: false
        });

        // Actualizar la cuenta de módulos activos
        var curmodule_id = $(this).parent('td').parent('tr').find('input[name=id]').val();
        var parentmodule_id;
        do {
            parentmodule_id = $('input[name=id][value='+curmodule_id+']').parent('td').parent('tr').find('input[name=idparent]').val();
            if (parentmodule_id != undefined) curmodule_id = parentmodule_id;
        } while (parentmodule_id != undefined);

        updateEnabledCount(curmodule_id);
    });

    // Colapsar todos los niveles
    $('button.level-1').click();
});

function expandChildren(tr_button)
{
    var resource_id = tr_button.find('input[name=id]').val();
    $('input[name=idparent][value='+resource_id+']').parent('td').parent('tr').each(function () {
        $(this).show();
    });
    tr_button.find('button > span')
        .removeClass('ui-icon-plusthick')
        .addClass('ui-icon-minusthick');
}

function collapseChildren(tr_button)
{
    var resource_id = tr_button.find('input[name=id]').val();
    $('input[name=idparent][value='+resource_id+']').parent('td').parent('tr').each(function () {
        collapseChildren($(this));
        $(this).hide();
    });

    tr_button.find('button > span')
        .removeClass('ui-icon-minusthick')
        .addClass('ui-icon-plusthick');
}

function updateEnabledCount(curmodule_id)
{
    // Enumerar hijos, si existen
    var children_list = $('input[name=idparent][value='+curmodule_id+']')
        .parent('td').parent('tr');
    if (children_list.length > 0) {
        var children_count = 0;
        children_list.each(function() {
            children_count += updateEnabledCount($(this).find('input[name=id]').val());
        });
        var spancount = $('input[name=id][value='+curmodule_id+']').parent('td').parent('tr')
            .find('span.enabledcount');
        if (children_count > 0)
            spancount.css('font-weight', 'bold');
        else
            spancount.css('font-weight', 'normal');
        spancount.text(children_count);
        return children_count;
    } else {
        return $('#resource-access-'+curmodule_id).is(':checked') ? 1 : 0;
    }
}
