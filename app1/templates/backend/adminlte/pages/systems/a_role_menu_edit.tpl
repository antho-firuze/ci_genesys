{var $url_module = $.php.base_url('systems/a_role_menu')}
{var $url_module_main = $.php.base_url('systems/a_role')}

   <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<script src="{$.const.TEMPLATE_URL}plugins/shollu-autofill/js/shollu-autofill.js"></script>
<script>
	{* Get Params *}
	var id = getURLParameter("id");
	var edit = getURLParameter("edit");
	var role_id = getURLParameter("role_id");
	{* Start :: Init for Title, Breadcrumb *}
	{* Set status (new|edit|copy) to Page Title *}
	var desc = function(edit){ if (edit==1) return "(Edit)"; else if (edit==2) return "(New)"; else return "(Copy)"; };
	$(".content").before(BSHelper.PageHeader({ 
		title: "{$window_title}", 
		title_desc: desc(edit), 
		bc_list:[
			{ icon:"fa fa-dashboard", title:"Dashboard", link:"{$.const.APPS_LNK}" },
			{ icon:"", title:"User", link:"javascript:history.back()" },
			{ icon:"", title:"{$window_title}", link:"javascript:history.back()" },
			{ icon:"", title: desc(edit), link:"" },
		]
	}));
	{* Additional for sub module *}
	$.getJSON('{$url_module_main}', { "id": (role_id==null)?-1:role_id }, function(result){ 
		if (!isempty_obj(result.data.rows)) {
			var code_name = ": "+result.data.rows[0].code_name;
			$('.content-header').find('h1').find('small').before(code_name);
		}
	});
	{* End :: Init for Title, Breadcrumb *}
	
	{* For design form interface *}
	var col = [], row = [];
	var form1 = BSHelper.Form({ autocomplete:"off" });	
	var box1 = BSHelper.Box({ type:"info" });
	var req = function(edit){ if (edit==1) return false; else if (edit==2) return true; else return true; };
	{* adding master key id *}
	col.push(BSHelper.Input({ type:"hidden", idname:"role_id", value:role_id }));
	{* standard fields table *}
	col.push(BSHelper.Input({ type:"hidden", idname:"id" }));
	col.push(BSHelper.Combobox({ horz:false, label:"Menu", idname:"menu_id", url:"{$.php.base_url('systems/a_menu')}", remote: true }));
	col.push(BSHelper.Checkbox({ horz:false, label:"Is Active", idname:"is_active", value:1 }));
	form1.append(subRow(subCol(6, col)));
	form1.append(subRow(subCol()));
	col = [];
	col.push( BSHelper.Button({ type:"submit", label:"Submit", idname:"submit_btn" }) );
	col.push( '&nbsp;&nbsp;&nbsp;' );
	col.push( BSHelper.Button({ type:"button", label:"Cancel", cls:"btn-danger", idname:"btn_cancel", onclick:"window.history.back();" }) );
	form1.append( col );
	box1.find('.box-body').append(form1);
	$(".content").append(box1);

	{* Begin: Populate data to form *}
	$.getJSON('{$url_module}', { "id": (id==null)?-1:id }, function(result){ 
		if (!isempty_obj(result.data.rows)) 
			form1.shollu_autofill('load', result.data.rows[0]);  
	});
	
	{* Form submit action *}
	form1.validator().on('submit', function (e) {
		{* e.stopPropagation; *}
		if (e.isDefaultPrevented()) { return false;	} 
		
		$.ajax({ url: '{$url_module ~ "?id="}'+id, method:(edit==1?"PUT":"POST"), async: true, dataType:'json',
			data: form1.serializeJSON(),
			success: function(data) {
				{* console.log(data); *}
				BootstrapDialog.alert('Saving data successfully !', function(){
					window.history.back();
        });
			},
			error: function(data) {
				if (data.status==500){
					var message = data.statusText;
				} else {
					var error = JSON.parse(data.responseText);
					var message = error.message;
				}
				BootstrapDialog.alert({ type:'modal-danger', title:'Notification', message:message });
			}
		});

		return false;
	});
</script>