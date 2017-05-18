$(document).ready(function() {
	$('#rpminfo_changemode').click(function() {
		if ($('#rpminfo_htmlmode').is(':visible')) {
			$('div#rpminfo_changemode>span#rpms_textmode').hide();
			$('div#rpminfo_changemode>span#rpms_htmlmode').show();
			$('#rpminfo_htmlmode').hide();
			$('#rpminfo_textmode').show();
		} else {
			$('div#rpminfo_changemode>span#rpms_htmlmode').hide();
			$('div#rpminfo_changemode>span#rpms_textmode').show();
			$('#rpminfo_textmode').hide();
			$('#rpminfo_htmlmode').show();
		}
	});

    $.post("index.php", {
    	menu:		'_elastixutils',
    	action:		'versionRPM',
    	rawmode:	'yes'
    	
    }, function(message) {
        $("#rpminfo_loading").hide();
        $("div#rpminfo_changemode").show();
        $('div#rpminfo_changemode>span#rpms_htmlmode').hide();
        
        var txtcontent = "";
        var key = "";
        var i = 0;
        var cont = 3;
        
        var packageTable = $('#rpminfo_htmlmode>table>tbody');
        var packageType = packageTable.find('tr.tdRPMDetail').detach();
        var packageRow = packageTable.find('tr').detach();
        for (key in message) {
        	var pt = packageType.clone();
        	pt.find('td').text(key);
        	pt.appendTo(packageTable);
        	
        	txtcontent += "\n " + key+"\n";
        	cont += 2;
        	
        	for (i = 0; i < message[key].length; i++) {
        		var pkgdata;
        		if (key == 'Kernel') {
        			// Formato especial para kernel
        			var krelease = message[key][i][1].split('-', 2);
        			pkgdata = [
                        message[key][i][0] + '(' + message[key][i][2] + ')',
                        krelease[0],
                        krelease[1]
        			];
        		} else {
        			pkgdata = message[key][i];
        		}
        		
        		var pr =packageRow.clone()
        		pr.find('td:first')
        			.next().text(pkgdata[0])
        			.next().text(pkgdata[1])
        			.next().text(pkgdata[2]);
        		pr.appendTo(packageTable);
        		
        		txtcontent+= "   " +pkgdata[0] + "-" + pkgdata[1] + "-" + pkgdata[2] + "\n";
        		cont++;
        	}
        }
        cont = cont + 2;

        $("#rpminfo_textmode > textarea")
        	.attr("rows", cont)
        	.val(txtcontent);
        $("#rpminfo_htmlmode").show();
    });
});