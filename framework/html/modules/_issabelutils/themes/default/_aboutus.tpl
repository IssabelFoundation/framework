<div id="animIssabel" style='width:200px; height:200px; margin:auto;'></div>
<table border='0' cellspacing="0" cellpadding="2" width='100%' style="margin-top:25px;">
    <tr>
        <td align='center'>
            {$ABOUT_ISSABEL_CONTENT}<br />
            <a href='http://www.issabel.org' target='_blank'>www.issabel.org</a>
        </td>
    </tr>
</table>
<script src="{$WEBPATH}themes/{$THEMENAME}/js/lottie.min.js"></script>
<script>
    var anim;
    var animData = {
        container: document.getElementById('animIssabel'),
        renderer: 'svg',
        loop: false,
        autoplay: true,
        rendererSettings: {
            progressiveLoad: false
        },
        path: '{$WEBPATH}themes/{$THEMENAME}/images/animIssabel.json'
    };
    anim = bodymovin.loadAnimation(animData);
    anim.setSpeed(1.5);
</script>
