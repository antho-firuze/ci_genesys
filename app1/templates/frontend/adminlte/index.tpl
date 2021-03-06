{* 
	Template Name: AdminLTE 
	Modified By: Firuze
	Email: antho.firuze@gmail.com
	Github: antho-firuze
*}
<!DOCTYPE html>
<html>
<head>
<meta name="robots" content="no-cache, no-cache">
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

<title>{$head_title}</title>

{$.php.link_tag('favicon.ico', 'shortcut icon', 'image/ico')}
{$.php.link_tag($.const.TEMPLATE_URL~'bootstrap/css/bootstrap.min.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'font-awesome/css/font-awesome.min.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'plugins/ionicons/css/ionicons.min.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'dist/css/AdminLTE.min.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'dist/css/skins/_all-skins.min.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'css/custom.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'plugins/pace/pace.min.css')}
{$.php.link_tag($.const.TEMPLATE_URL~'plugins/iCheck/flat/blue.css')}
{* {$.php.link_tag($.const.TEMPLATE_URL~'plugins/marquee/css/jquery.marquee.min.css')} *}

<script src="{$.const.ASSET_URL}js/common.func.js"></script>
<script>
	{* DECLARE VARIABLE *}
	var $skin = 'skin_f{$.const.DEFAULT_CLIENT_ID~$.const.DEFAULT_ORG_ID}';
	var $sidebar = 'sidebar_f{$.const.DEFAULT_CLIENT_ID~$.const.DEFAULT_ORG_ID}';
	
	if (!get($skin)) store($skin, '{$skin_color}');
</script>

<script src="{$.const.TEMPLATE_URL}plugins/jQuery/jQuery-2.1.4.min.js"></script>
{* <script src="{$.const.TEMPLATE_URL}plugins/jQueryUI/jquery-ui.min.js"></script> *}
{* <script>$.widget.bridge("uibutton", $.ui.button);</script> *}
<script src="{$.const.TEMPLATE_URL}bootstrap/js/bootstrap.min.js"></script>
<script src="{$.const.TEMPLATE_URL}plugins/pace/pace.min.js"></script>
<script src="{$.const.TEMPLATE_URL}plugins/moment/min/moment.min.js"></script>
<script src="{$.const.TEMPLATE_URL}plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="{$.const.TEMPLATE_URL}plugins/fastclick/fastclick.js"></script>
<script src="{$.const.TEMPLATE_URL}dist/js/app.min.js"></script>
{* <script src="{$.const.TEMPLATE_URL}plugins/marquee/lib/jquery.marquee.min.js"></script> *}
<script src="{$.const.ASSET_URL}js/bootstrap.helper.js"></script>
</head>

<body class="hold-transition sidebar-mini">

<!-- Site wrapper -->
<div class="wrapper">

  {* $main_header *}
  {include $.const.TEMPLATE_PATH ~ "include/main_header.tpl"}

  <!-- =============================================== -->

  <!-- Left side column. contains the sidebar -->
  {* $navbar_left *}
  {include $.const.TEMPLATE_PATH ~ "include/navbar_left.tpl"}

  <!-- =============================================== -->

  <!-- Content Wrapper. Contains page content -->
  {include $content}
  {* {include "frontend_theme/adminlte/page/home.tpl"} *}
  <!-- /.content-wrapper -->

  {* $main_footer *}
  {include $.const.TEMPLATE_PATH ~ "include/main_footer.tpl"}

  <!-- Control Sidebar -->
  {* $navbar_right *}
  {include $.const.TEMPLATE_PATH ~ "include/navbar_right.tpl"}
  
  <!-- /.control-sidebar -->
  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
  
</div>
<!-- ./wrapper -->
<script type="text/javascript" src="{$.const.TEMPLATE_URL}js/custom.js"></script>
<script>$(document.body).addClass(get($sidebar)).addClass(get($skin));</script>
</body>
</html>