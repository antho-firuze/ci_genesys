{* 
	Template Name: AdminLTE 
	Modified By: Firuze
	Email: antho.firuze@gmail.com
	Github: antho-firuze
*}
{var $template_url = $.php.base_url() ~ "templates/" ~ $theme_path}
{* {var $template_url = "{$template_url}"} *}
{var $resource['home1']}
	<link rel="stylesheet" href="{$template_url}bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="{$template_url}font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/ionicons/css/ionicons.min.css">
	<link rel="stylesheet" href="{$template_url}dist/css/AdminLTE.min.css">
	<link rel="stylesheet" href="{$template_url}dist/css/skins/_all-skins.min.css">
	<link rel="stylesheet" href="{$template_url}css/custom.css">
	<link rel="stylesheet" href="{$template_url}plugins/bootstrap-dialog/css/bootstrap-dialog.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/iCheck/flat/blue.css">
	<link rel="stylesheet" href="{$template_url}plugins/morris/morris.css">
	<link rel="stylesheet" href="{$template_url}plugins/jvectormap/jquery-jvectormap-1.2.2.css">
	<link rel="stylesheet" href="{$template_url}plugins/datepicker/datepicker3.css">
	<link rel="stylesheet" href="{$template_url}plugins/daterangepicker/daterangepicker-bs3.css">
	<link rel="stylesheet" href="{$template_url}plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/pace/pace.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/autoComplete/jquery.auto-complete.css">
	<link rel="stylesheet" href="{$template_url}plugins/marquee/css/jquery.marquee.min.css">
	
	<script src="{$template_url}plugins/jQuery/jQuery-2.1.4.min.js"></script>
	<script src="{$template_url}plugins/jQueryUI/jquery-ui.min.js"></script>
	<script>$.widget.bridge("uibutton", $.ui.button);</script>
	<script src="{$template_url}bootstrap/js/bootstrap.min.js"></script>
	<script src="{$template_url}plugins/pace/pace.min.js"></script>
	<script src="{$template_url}plugins/bootstrap-dialog/js/bootstrap-dialog.min.js"></script>
	<script src="{$template_url}plugins/raphael/raphael-min.js"></script>
	<script src="{$template_url}plugins/morris/morris.min.js"></script>
	<script src="{$template_url}plugins/sparkline/jquery.sparkline.min.js"></script>
	<script src="{$template_url}plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
	<script src="{$template_url}plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
	<script src="{$template_url}plugins/knob/jquery.knob.js"></script>
	<script src="{$template_url}plugins/moment/min/moment.min.js"></script>
	<script src="{$template_url}plugins/daterangepicker/daterangepicker.js"></script>
	<script src="{$template_url}plugins/datepicker/bootstrap-datepicker.js"></script>
	<script src="{$template_url}plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
	<script src="{$template_url}plugins/slimScroll/jquery.slimscroll.min.js"></script>
	<script src="{$template_url}plugins/fastclick/fastclick.js"></script>
	<script src="{$template_url}plugins/autoComplete/jquery.auto-complete.min.js"></script>
	<script src="{$template_url}dist/js/app.min.js"></script>
	<script src="{$template_url}plugins/validation/jquery.validate.min.js"></script>
	<script src="{$template_url}plugins/marquee/lib/jquery.marquee.min.js"></script>
{/var}
{var  $resource['home2']}
	<link rel="stylesheet" href="{$template_url}bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/pace/pace.min.css">
	<link rel="stylesheet" href="{$template_url}font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/ionicons/css/ionicons.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/jvectormap/jquery-jvectormap-1.2.2.css">
	<link rel="stylesheet" href="{$template_url}dist/css/AdminLTE.min.css">
	<link rel="stylesheet" href="{$template_url}dist/css/skins/_all-skins.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/jQueryUI/jquery-ui.min.css">
	<link rel="stylesheet" href="{$template_url}plugins/autoComplete/jquery.auto-complete.css">
	<link rel="stylesheet" href="{$template_url}css/custom.css">
	
	<script src="{$template_url}plugins/jQuery/jQuery-2.1.4.min.js"></script>
	<script src="{$template_url}bootstrap/js/bootstrap.min.js"></script>
	<script src="{$template_url}plugins/pace/pace.min.js"></script>
	<script src="{$template_url}plugins/fastclick/fastclick.js"></script>
	<script src="{$template_url}plugins/autoComplete/jquery.auto-complete.min.js"></script>
	<script src="{$template_url}dist/js/app.min.js"></script>
	<script src="{$template_url}plugins/sparkline/jquery.sparkline.min.js"></script>
	<script src="{$template_url}plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
	<script src="{$template_url}plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
	<script src="{$template_url}plugins/slimScroll/jquery.slimscroll.min.js"></script>
	<script src="{$template_url}plugins/chartjs/Chart.min.js"></script>
	<script src="{$template_url}dist/js/pages/dashboard2.js"></script>
	<script src="{$template_url}plugins/jQueryUI/jquery-ui.min.js"></script>
	<script src="{$template_url}plugins/validation/jquery.validate.min.js"></script>
{/var}
<!DOCTYPE html>
<html>
<head>
<script type="text/javascript" src='{$.php.base_url()}assets/genesys/js/common.func.js'></script>
<script>
	{* DECLARE VARIABLE *}
	{var $api_base_url = $.const.API_BASE_URL}
	{var $head_title = $head_title !: $.const.TITLE_F}
	{var $page_title = $.const.TITLE_F}
	{var $logo_text_mn = $.const.WEB_LOGO_TEXT_MN_F}
	{var $logo_text_lg = $.const.WEB_LOGO_TEXT_LG_F}
	{* {var $photo_url = $api_base_url ~ $.php.urldecode( ($.session.photo_url) )} *}
	{* {var $name = $.session.name} *}
	{* {var $description = $.session.description} *}
	{* {var $email = $.session.email} *}
	{* {var $client_name = $.session.client_name} *}
	{* {var $org_name = $.session.org_name} *}
	{* {var $role_name = $.session.role_name} *}
	
	{var $home_link = $.php.base_url()~$.const.HOME_F_LNK}
	{var $login_link = $.php.base_url()~$.const.LOGIN_LNK}
	{var $skin = $.session.skin !: 'skin-purple'}
	{var $sidebar = $.session.sidebar !: ''}
	var base_url = '{$.php.base_url()}';
	var api_base_url = '{$.const.API_BASE_URL}';
	var InfoLst_url = '{$.php.base_url()~$.const.INFOLST_LNK}';
	var username = '{$.session.name}';

	store('skin', '{$skin}');
	store('sidebar', '{$sidebar}');
	store('screen_timeout', '{$.session.screen_timeout !: 60000}');
	
	{var $dashboard = $dashboard !: []}
	{var $content_box_3 = $content_box_3 !: []}
	{var $include_box_3 = $include_box_3 !: []}
	{var $content_box_5 = $content_box_5 !: []}
	{var $include_box_5 = $include_box_5 !: []}
	{var $content_box_7 = $content_box_7 !: []}
	{var $include_box_7 = $include_box_7 !: []}
	{foreach $dashboard as $board}
		{if ($board->type=='BOX-3')}
			{var $content_box_3[] = "{$board->url}"}
			{if (! empty($board->include_files))}
				{var $include_box_3[] = "{$board->include_files}"}
			{/if}
		{elseif ($board->type=='BOX-5')}
			{var $content_box_5[] = "{$board->url}"}
			{if (! empty($board->include_files))}
				{var $include_box_5[] = "{$board->include_files}"}
			{/if}
		{elseif ($board->type=='BOX-7')}
			{var $content_box_7[] = "{$board->url}"}
			{if (! empty($board->include_files))}
				{var $include_box_7[] = "{$board->include_files}"}
			{/if}
		{/if}
	{/foreach}
</script>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

<title>{$head_title}</title>

{$resource[$category]}
</head>

<body class="hold-transition {$skin} fixed sidebar-mini {$sidebar}">

<!-- Site wrapper -->
<div class="wrapper">

  {* $main_header *}
  {include $theme_path ~ "include/main_header.tpl"}

  <!-- =============================================== -->

  <!-- Left side column. contains the sidebar -->
  {* $navbar_left *}
  {include $theme_path ~ "include/navbar_left.tpl"}

  <!-- =============================================== -->

  <!-- Content Wrapper. Contains page content -->
  {include $content}
  {* {include "frontend_theme/adminlte/page/home.tpl"} *}
  <!-- /.content-wrapper -->

  {* $main_footer *}
  {include $theme_path ~ "include/main_footer.tpl"}

  <!-- Control Sidebar -->
  {* $navbar_right *}
  {include $theme_path ~ "include/navbar_right.tpl"}
  
  <!-- /.control-sidebar -->
  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
  
</div>
<!-- ./wrapper -->

{if (count($content_box_5) > 0 || count($content_box_7) > 0)}
	{foreach $include_box_5 as $inc}
		{include $theme_path ~ "{$inc}"}
	{/foreach}
	{foreach $include_box_7 as $inc}
		{include $theme_path ~ "{$inc}"}
	{/foreach}
{/if}
<script src="{$template_url}js/custom.js"></script>
</body>
</html>