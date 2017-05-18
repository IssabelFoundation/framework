<link rel='stylesheet' href='modules/{$module_name}/themes/default/css/rpms_version.css' />
<script type='text/javascript' src="modules/{$module_name}/themes/default/js/rpms_version.js"></script>
<div id="rpminfo_changemode">
    (<span id="rpms_textmode">{$textMode}</span>
    <span id="rpms_htmlmode">{$htmlMode}</span>)
</div>
<div id="rpminfo_loading">
    <img src="images/loading.gif" alt="loading" />
</div>
<div id="rpminfo_htmlmode" class="letra12">
	<table>
        <thead>
	        <tr>
	            <th>Name</th>
	            <th>Package Name</th>
	            <th>Version</th>
	            <th>Release</th>
	        </tr>
        </thead>
        <tbody>
            {* Estas filas se quitan con jquery para agregar paquetes *}
            <tr class='tdRPMDetail'><td colspan='4' align='left'></td></tr>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
	    </tbody>
	</table>
</div>
<div id="rpminfo_textmode">
    <textarea readonly="readonly"></textarea>
</div>
