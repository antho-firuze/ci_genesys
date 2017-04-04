{var $url_module = $.php.base_url('systems/a_org')}

   <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        {$window_title}
        <small>{$description}</small>
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

      <!-- Default box -->
      <div class="box">
				<div class="box-body"></div>
      </div>
      <!-- /.box -->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<script src="{$.const.TEMPLATE_URL}plugins/shollu-autofill/js/shollu-autofill.js"></script>
<script>
	var a = [];	var col = [];
	var id = getURLParameter("id");
	var edit = getURLParameter("edit");
	var formContent = $('<form "autocomplete"="off"></form>');
	
	{* Set status to Page Title *}
	var desc = function(edit){ if (edit==1) return "(Edit)"; else if (edit==2) return "(New)"; else return "(Copy)"; };
	$('.content-header').find('h1').find('small').html(desc(edit));
	
	{* For design form interface *}
	var req = function(edit){ if (edit==1) return false; else if (edit==2) return true; else return true; };
	a.push(BSHelper.Input({ type:"hidden", idname:"id" }));
	a.push(BSHelper.Input({ horz:false, type:"text", label:"Code", idname:"code", required: true }));
	a.push(BSHelper.Input({ horz:false, type:"text", label:"Name", idname:"name", required: true }));
	a.push(BSHelper.Input({ horz:false, type:"textarea", label:"Description", idname:"description" }));
	a.push(BSHelper.Checkbox({ horz:false, label:"Is Active", idname:"is_active", value:1 }));
	a.push(BSHelper.Checkbox({ horz:false, label:"Is Parent", idname:"is_parent", value:0 }));
	a.push(BSHelper.Combobox({ horz:false, label:"Parent Org", idname:"parent_id", url:"{$.php.base_url('systems/a_org')}?filter=is_parent='1'", remote: true }));
	a.push(BSHelper.Combobox({ horz:false, label:"Org Type", label_link:"{$.const.PAGE_LNK}?pageid=19", idname:"orgtype_id", url:"{$.php.base_url('systems/a_orgtype')}", remote: true }));
	col.push(subCol(6, a));
	a = [];
	a.push(BSHelper.Combobox({ horz:false, label:"Supervisor", label_link:"{$.const.PAGE_LNK}?pageid=20", idname:"supervisor_id", url:"{$.php.base_url('systems/a_user')}", remote: true }));
	a.push(BSHelper.Input({ horz:false, type:"text", label:"Phone", idname:"phone", required: false }));
	a.push(BSHelper.Input({ horz:false, type:"text", label:"Phone 2", idname:"phone2", required: false }));
	a.push(BSHelper.Input({ horz:false, type:"text", label:"Fax", idname:"fax", required: false }));
	a.push(BSHelper.Input({ horz:false, type:"email", label:"Email", idname:"email", required: false }));
	a.push(BSHelper.Input({ horz:false, type:"text", label:"Website", idname:"website", required: false }));
	a.push(BSHelper.Input({ horz:false, type:"decimal", label:"SWG Margin", idname:"swg_margin", required: false }));
	col.push(subCol(6, a));
	formContent.append(subRow(col));
	a = [];
	a.push( BSHelper.Button({ type:"submit", label:"Submit", idname:"submit_btn" }) );
	a.push( '&nbsp;&nbsp;&nbsp;' );
	a.push( BSHelper.Button({ type:"button", label:"Cancel", cls:"btn-danger", idname:"btn_cancel", onclick:"window.history.back();" }) );
	formContent.append( a );
	$('div.box-body').append(formContent);

	{* Begin: Populate data to form *}
	$.getJSON('{$url_module}', { "id": (id==null)?-1:id }, function(result){ 
		if (!isempty_obj(result.data.rows)) 
			formContent.shollu_autofill('load', result.data.rows[0]);  
	});
	
	{* Init data for combogrid *}

	{* Form submit action *}
	formContent.validator().on('submit', function (e) {
		{* e.stopPropagation; *}
		if (e.isDefaultPrevented()) { return false;	} 
		
		$.ajax({ url: '{$url_module ~ "?id="}'+id, method:(edit==1?"PUT":"POST"), async: true, dataType:'json',
			data: formContent.serializeJSON(),
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