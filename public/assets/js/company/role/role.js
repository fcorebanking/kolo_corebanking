var $_getValidationField = document.getElementsByClassName('validation-text');
$("#addrole").on('submit',function() {
    if($('#role_name').val() != ''){
        var form = $(this);    
     
        $_getValidationField[0].style.display = 'none';
       
    ajax_operation('put','role/add',form.serialize());
    renderpage();
    }else{
        $_getValidationField[0].innerHTML = 'Role Name must be filled out';
        $_getValidationField[0].style.display = 'block';
    }
   return false;
});
function edit_role(id,name){
    $('#edit_exampleModal').modal('show');
    $('#edit_role_name').val(name); 
    $('#id').val(id);

  
        $.ajax({
            url:'role/get-permission/'+id,
            type: 'get',
           
            dataType: 'json',
          
            success:function(data){ 
                
                $('input:checkbox').prop('checked', false);
                data.forEach(function(item){
                   $('input[value="' + item.permission_id + '"]:checkbox').prop('checked', true);
                });
            }
        });
    
}

$("#updaterole").on('submit',function() {
    if($('#edit_role_name').val() != ''){
        var form = $(this);        
        $_getValidationField[1].style.display = 'none';
    ajax_operation('post','role/update',form.serialize());
    renderpage();
    }else{
        $_getValidationField[1].innerHTML = 'Role Name must be filled out';
        $_getValidationField[1].style.display = 'block';
    }
   return false;
});


function delete_role(val){
    if (confirm("Are you sure, you want to delete this record?") == true) {
    ajax_operation('delete','role/delete/'+val,{id:val});
    renderpage();
    }
return false;
}
function renderpage(){
    $send_method = 'GET';
    $pageUrl = 'role/ajax_view_role'; 
      $data_info = {}
      ajax_fetch($send_method,$pageUrl,$data_info);   
}
renderpage();

