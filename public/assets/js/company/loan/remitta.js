function display_accountNo_salary(){


    var bankcode = $('#remittar_bankCode').val();
    var accno = $('#remittar_accountNo').val();
    if(bankcode != '' && accno.length > 0){
        $('tbody[name="custmerdetailsbank"]').empty();
        $('tbody[name="custmerdetailsbank"]').html("<center><div class='loader'></div></center>");
        $('#salaryPaymentDetailsbank').empty();
        $('#loanHistoryDetailsbank').empty();
        $.ajax({
            url:'/company/remitta/salaryhistory?type=bank&accountNumber='+accno+'&bankCode='+bankcode,
            type: 'GET',
            dataType: 'json',
            success:function(data){
                setData(data);
            },error: function(data) {
            $('tbody[name="custmerdetails"]').append('oops! Network Error Try Again');
            }
        });
    }else{
        alert("No account Number or Bank")
    }
}
function mandatehistory(customerId,mandateRef){
              $('#remitamandateModal').modal('show');
               $('tbody[name="custmerdetails"]').html('<div class="loader"></div>');
              $.ajax({
                    url:'/company/remitta/mandate/payment/history?customerId='+customerId+'&mandateRef='+mandateRef,
                    type: 'GET',
                    dataType: 'json',
                    success:function(data){
                        if(data.status == "success"){

                         $('tbody[name="custmerdetails"]').empty();
                          $('#repayment').empty();
                         $('tbody[name="custmerdetails"]').append(
                             '<tr><td>'+data.data.firstName+' '+data.data.lastName+'</td><td>'+data.data.customerId+'</td><td>'+data.data.phoneNumber+'</td><td>'+data.data.loanMandateReference+'</td><td>'+data.data.totalDisbursed+'</td><td>'+data.data.outstandingLoanBal+'</td><td>'+data.data.loanRepaymentRef+'</td><td>'+data.data.employerName+'</td><td>'+data.data.salaryAccount+'</td><td>'+data.data.salaryBankCode+'</td><td>'+data.data.disbursementAccountBank+'</td><td>'+data.data.collectionStartDate+'</td><td>'+data.data.dateOfDisbursement+'</td><td>'+data.data.status+'</td><td>'+data.data.lenderDetails+'</td></tr>'
                             );
                        let count = 0 ;
                        var repayment = data.data.repayment;
                        repayment.forEach(function(repay){
                            count += 1;
                              $('#repayment').append(
                             '<tr><td>'+count+'</td><td>'+repay.transactionamount+'</td><td>'+repay.deductiondate+'</td><td>'+repay.paymentstatus+'</td></tr>'
                             );
                          });
                        }else{
                             $('#mandates').html(data.responseMsg);
                        }
                    },

                                  error: function(data) {
                    //AJAX request not completed

                    $('tbody[name="custmerdetails"]').html('oops! Network Error Try Again');
                }
                });
    }

function show_remita_salaryhistory(){
    var phone = $('#remitaPhone').val();
    if(phone != ''&& phone != ' '){
       $('#myRemita').modal('show');
       $('tbody[name="custmerdetails"]').empty();
         $('tbody[name="custmerdetails"]').html("<center><div class='loader'></div></center>");

        $('#salaryPaymentDetails').empty();
        $('#loanHistoryDetails').empty();
         $.ajax({
                    url:'/company/remitta/salaryhistory?type=phone&phone='+phone,
                    type: 'GET',
                    dataType: 'json',
                    success:function(data){
                        setData(data);
                    },
                      error: function(data) {
        //AJAX request not completed

        $('tbody[name="custmerdetails"]').append('oops! Network Error Try Again');
    }
                });
    }else{
        alert("No account Phone Number")
    }
}
function setData(data){
    if(data.status == "success"){
        $('tbody[name="custmerdetails"]').empty();

         $('tbody[name="custmerdetails"]').append(
             '<tr><td>'+data.data.customerName+'</td><td>'+data.data.customerId+'</td><td>'+data.data.accountNumber+'</td><td>'+data.data.bankCode+'</td><td>'+data.data.companyName+'</td><td>'+data.data.firstPaymentDate+'</td><td>'+data.data.salaryCount+'</td></tr>'
             );
             $('#customerId').val(data.data.customerId)
        let count = 0 ;
        var salaries = data.data.salaryPaymentDetails;
        salaries.forEach(function(salary){
            count += 1;

              $('#salaryPaymentDetails').append(
             '<tr><td>'+count+'</td><td>'+salary.paymentDate+'</td><td>'+salary.amount+'</td><td>'+salary.accountNumber+'</td><td>'+salary.bankCode+'</td></tr>'
             );
           });

           let countv =0;
           var loanHistoryDetails = data.data.loanHistoryDetails;
        loanHistoryDetails.forEach(function(loanHistory){
            countv += 1;

              $('#loanHistoryDetails').append(
             '<tr><td>'+countv+'</td><td>'+loanHistory.loanProvider+'</td><td>'+loanHistory.loanAmount+'</td><td>'+loanHistory.outstandingAmount+'</td><td>'+loanHistory.loanDisbursementDate+'</td><td>'+loanHistory.status+'</td><td>'+loanHistory.repaymentAmount+'</td><td>'+loanHistory.repaymentFreq+'</td></tr>'
             );
           });
           //loanHistoryDetails
           $('#originalCustomerId').val(data.data.originalCustomerId);
         }else{
            $('tbody[name="custmerdetails"]').append(data.responseMsg );
            $('tbody[name="custmerdetails"]').append(data.data.responseDescription);
        }
}
$("#postNotification").on("submit", function(e){
    e.preventDefault();
      $('.msg_three').html('<div class="loader"></div>');
        $.ajax({
            type: 'POST',
            url: '/company/remitta/post-notification',
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData:false,
            beforeSend: function(){
                $('.sbutton').attr("disabled","disabled");
                $('#postNotification').css("opacity",".5");
            },
            success: function(response){
                if(response.status == true){
                    mandateTable();
                    $('.msg_three').html('<div class="alert alert-success" style="font-size:14px;"><h4><b> '+response.message+'<b></h4></div>');
                }else{
                     $('.msg_three').html('<div class="alert alert-danger" style="font-size:14px;"><h4><b> '+response.message+'<b></h4></div>');
                }
                $('#postNotification').css("opacity","");
                $(".sbutton").removeAttr("disabled");
            },
              error: function(data) {
                    //AJAX request not completed
                   $('.msg_three').html('<div class="alert alert-danger" style="font-size:14px;"><h4><b> oops! Network Error Try Again<b></h4></div>');
                }
        });
});
function mandateTable(){
    $('#mandateTable').html('<div class="loader"></div>');
    $sn =0;
$loanid = $('#loanid').val();
       $.ajax({
           type: 'GET',
           url: '/company/remitta/mandate/'+$loanid,
           dataType: 'json',
           success: function(data){
               $('#mandateTable').empty();
               console.log(data)
                data.forEach(function(datas){
                    $status =(datas.status == 'Active') ? "<span class='badge badge-success'>"+datas.status+"</span>":"<span class='badge badge-danger'>"+datas.status+"</span>";
                    $sn++;
                    $view_history="<button onclick='mandatehistory("+datas.customerId+","+datas.mandateReference+")' class='btn btn-info'>Payment History</button>";
                    $btn = (datas.status == 'Active')?"<button onclick='stopdebit("+datas.customerId+","+datas.mandateReference+")' class='btn btn-danger'>Stop mandate Debit</button>":"<button disabled class='btn btn-warning'>Stopped</button>"
                    $('#mandateTable').append("<tr><td>"+$sn+"</td><td>"+datas.mandateReference+"</td><td>"+datas.authorisationCode+"</td><td>"+datas.customerId+"</td><td>"+$status+"</td><td>"+datas.updated_at+"</td><td>"+$btn+"</td><td>"+$view_history+"</tr>");
                });


           },
             error: function(data) {
                   //AJAX request not completed

                  $('#mandateTable').html('<div class="alert alert-danger" style="font-size:14px;"><h4><b> oops! Network Error Try Again<b></h4></div>');
               }
       });
}
mandateTable();
function stopdebit(customerid,mandateref){
    var r = confirm("Are you sure to stop remita from charging this loan?");
        if (r == true) {
            $('.response_message').html('<div class="loader"></div>');
            $.get("/company/remitta/mandate/stop/reference",
                    {customerId:customerid,mandateRef:mandateref},
                    function(response){
                        if(response.status == 'success'){
                            mandateTable();
                            $('.response_message').html('<div class="alert alert-success" style="font-size:14px;"><h4><b> '+response.message+'<b></h4></div>');
                        }else{
                                $('.response_message').html('<div class="alert alert-danger" style="font-size:14px;"><h4><b> '+response.message+'<b></h4></div>');
                        }
                    },
                    'json'
                );
        }
}
