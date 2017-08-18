<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<!-- Main content -->
	<section class="content">
		<!-- /.row -->
		<div class="box box-body datagrid table-responsive no-padding"></div>
		<!-- /.box -->
	</section>
	<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script src="{$.const.TEMPLATE_URL}plugins/accounting/accounting.min.js"></script>
<script>
	var $url_module = "{$.php.base_url()~$class~'/'~$method}", $table = "{$table}", $bread = {$.php.json_encode($bread)};
	{* Toolbar Init *}
	var Toolbar_Init = {
		enable: true,
		toolbarBtn: ['btn-new','btn-copy','btn-refresh','btn-delete','btn-message','btn-print','btn-export','btn-import','btn-viewlog','btn-process'],
		disableBtn: ['btn-copy','btn-message','btn-process'],
		hiddenBtn: ['btn-copy','btn-message'],
		processMenu: [{ id:"btn-process1", title:"Process 1" }, { id:"btn-process2", title:"Process 2" }, ],
		processMenuDisable: ['btn-process1'],
	};
	if ("{$is_canimport}" == "0") Toolbar_Init.disableBtn.push('btn-import');
	if ("{$is_canexport}" == "0") Toolbar_Init.disableBtn.push('btn-export');
	{* DataTable Init *}
	var format_money = function(money){ return accounting.formatMoney(money, '', {$.session.number_digit_decimal}, "{$.session.group_symbol}", "{$.session.decimal_symbol}") };
	var DataTable_Init = {
		enable: true,
		tableWidth: '125%',
		act_menu: { copy: true, edit: true, delete: true },
		sub_menu: [
			{* { pageid: 106, subKey: 'invoice_id', title: 'Invoice Line', }, *}
			{* { pageid: 107, subKey: 'invoice_id', title: 'Invoice Plan', }, *}
		],
		columns: [
			{ width:"30px", orderable:false, className:"dt-head-center dt-body-center", data:"is_receipt", title:"In/Out", render: function(data, type, row){ return data == '1' ? 'IN' : 'OUT'; } },
			{ width:"100px", orderable:false, data:"doc_no", title:"Invoice No" },
			{ width:"60px", orderable:false, className:"dt-head-center dt-body-center", data:"doc_date", title:"Invoice Date" },
			{ width:"100px", orderable:false, data:"doc_no_ar_ap", title:"AR/AP Doc No" },
			{ width:"50px", orderable:false, className:"dt-head-center dt-body-center", data:"doc_date_ar_ap", title:"AR/AP Doc Date" },
			{ width:"150px", orderable:false, data:"bpartner_name", title:"Business Partner" },
			{ width:"200px", orderable:false, data:"note", title:"Note" },
			{ width:"200px", orderable:false, data:"description", title:"Description" },
			{ width:"100px", orderable:false, className:"dt-head-center dt-body-right", data:"amount", title:"Amount", render: function(data, type, row){ return format_money(data); } },
			{ width:"100px", orderable:false, className:"dt-head-center dt-body-right", data:"adj_amount", title:"Adj Amount", render: function(data, type, row){ return format_money(data); } },
			{ width:"100px", orderable:false, className:"dt-head-center dt-body-right", data:"net_amount", title:"Net Amount", render: function(data, type, row){ return format_money(data); } },
		],
		order: ['id desc'],
	};
	
</script>
<script src="{$.const.ASSET_URL}js/window_view.js"></script>
