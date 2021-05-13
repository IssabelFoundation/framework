<?php
$theme = isset($content['theme'])?$content['theme']:"elastixneo";
$size = "100%";
$styleBody = "";
$styleDiv = "";
switch($theme){
    default:
          $image = "/images/issabel_logo_mini2.png";
          break;
}
$theme         = "/themes/$theme";
$currentYear   = date("Y");
$msg           = isset($content['msg'])?$content['msg']:"";
$title         = isset($content['title'])?$content['title']:"";
?>

<html>
<head>
<title>Issabel - <?php echo $title; ?></title>
<link rel="stylesheet" href="<?php echo $theme; ?>/styles.css">
</head>

<body bgcolor="#ffffff" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" <?php echo $styleBody; ?> >
  <table cellspacing="0" cellpadding="0" width="<?php echo $size; ?>" border="0" class="menulogo2" height="74">
    <tr>
       <td class="menulogo" valign="top">
           <a target="_blank" href="http://www.issabel.org">
               <img border="0" src="<?php echo $image; ?>"/>
           </a>
       </td>
    </tr>
  </table>
  <div align="center" <?php echo $styleDiv; ?> >
    <?php echo $msg; ?>
  <div/>
  <br /><br />
  <div align="center" class="copyright"><a href="http://www.issabel.org" target='_blank'>Issabel</a> {$ISSABEL_LICENSED} <a href="http://www.opensource.org/licenses/gpl-license.php" target='_blank'>GPL</a>. 2006 - <?php echo $currentYear; ?>.</div>
  <br />
</body>
</html>
