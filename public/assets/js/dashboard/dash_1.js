// try {


//   var d_2options1 = {
//     chart: {
//       height: 160,
//       type: 'bar',
//       stacked: true,
//       stackType: '100%',
//       toolbar: {
//         show: false,
//       }
//     },
//     dataLabels: {
//       enabled: false,
//     },
//     stroke: {
//       show: true,
//       width: 1,
//     },
//     colors: ['003314', '#e0e6ed'],
//     responsive: [{
//       breakpoint: 480,
//       options: {
//         legend: {
//           position: 'bottom',
//           offsetX: -10,
//           offsetY: 0
//         }
//       }
//     }],
//     series: [{
//       name: 'Sales',
//       data: [44, 55, 41, 67, 22, 43, 21]
//     }, {
//       name: 'Last Week',
//       data: [13, 23, 20, 8, 13, 27, 33]
//     }],
//     xaxis: {
//       labels: {
//         show: false,
//       },
//       categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thur', 'Fri', 'Sat'],
//     },
//     yaxis: {
//       show: false
//     },
//     fill: {
//       opacity: 1
//     },
//     plotOptions: {
//       bar: {
//         horizontal: false,
//         endingShape: 'rounded',
//         columnWidth: '25%',
//       }
//     },
//     legend: {
//       show: false,
//     },
//     grid: {
//       show: false,
//       xaxis: {
//         lines: {
//           show: false
//         }
//       },
//       padding: {
//         top: 10,
//         right: 0,
//         bottom: -40,
//         left: 0
//       },
//     },
//   }
//   /*
//       ============================
//           Daily Sales | Render
//       ============================
//   */
//   var d_2C_1 = new ApexCharts(document.querySelector("#daily-sales"), d_2options1);
//   d_2C_1.render();
//   /*
//       =============================
//           Total Orders | Options
//       =============================
//   */
//   // const formatToCurrency = amount => {
//   //   return  amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
//   // };
//   let dollarUSLocale = Intl.NumberFormat('en-US');
//   function formatToCurrency(number, decPlaces = 2, decSep = '.', thouSep = ',') {

//     var value = (number).toLocaleString(
//       undefined, // leave undefined to use the visitor's browser
//       // locale or a string like 'en-US' to override it.
//       { minimumFractionDigits: 2 }
//     );

//     return value;
//   }

//   let monthlydata;
//   let monthlylabel;
//   let dailytxn;
//   let total_daily;
//   let label_user;
//   let label_data;
//   let total_month;
//   let currency;


// //   function load_data() {
// //     $.ajax({
// //       url: '/dashboarddata?page=latestTXN',
// //       type: 'get',
// //       dataType: 'json',
// //       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
// //       success: function (data) {
// //         if (data.permission_to_see) {
// //           //console.log(data.monthly_data.txn.monthly_TXN);
// //           monthlydata = data.monthly_data.txn.monthly_TXN;
// //           monthlylabel = data.monthly_data.txn.monthly_label;
// //           dailytxn = data.transaction_type_day;
// //           total_daily = data.total_daily;
// //           currency = data.currency;
// //           $('.link_cards').html(data.user_has_card);
// //           $('.pending_card').html(data.user_penidng_card);

// //           $permission_to_see = data.permission_to_see;
// //           total_month = currency + formatToCurrency(data.total_month ?? 0);
// //           $('.total_daily').html(currency + formatToCurrency(Number(total_daily ?? 0)));
// //           $recent_txn = data.latest_txn;
// //           $lagest_txn = data.largest_txn;
// //           $transaction_type = data.transaction_type;

// //           $investment = data.display_card.investment;
// //           $product = data.display_card.product;
// //           $dom = data.display_card.dom;

// //           $('.deposit-total').html(currency + formatToCurrency(Number(data.display_card.deposit.total ?? 0)));
// //           $('.withdraw-total').html(currency + formatToCurrency(data.display_card.withdraw.total ?? 0));
// //           $('.system-balance').html(currency + formatToCurrency(Number(data.display_card.deposit.total) - Number(data.display_card.withdraw.total)));

// //         if (typeof data.user !== 'undefined') {
// //             $('.total-branches').html(data.user.data[2]);
// //           }
// //           $('.transaction_type').empty();
// //           $('#recent_txt').empty( );
// //           $('#largest_txn').empty();
// //           // step 1 restriction
// //           if ($permission_to_see) {
// //             $('.branch_data').empty();
// //             label_user = data.user.label_user;
// //             label_data = data.user.data;
// //             $branch = data.branch;
// //             $balance = data.wallet_data.balance;
// //             $sms = data.wallet_data.sms;
// //             $ussd = data.wallet_data.ussd;
// //             $whatsapp = data.wallet_data.whatsapp;
// //             if($branch){
// //                 $branch.forEach(function (branch) {
// //                 $badge_txn = (1 == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
// //                 $updown = '';

// //                 $string = branch.name.toUpperCase();
// //                 $badge = $string.slice(0, 2); // "01"
// //                 $badge = $badge.toUpperCase();
// //                 $('.branch_data').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + branch.name.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + (formatToCurrency(branch.balance)) + '</span> ' + $badge_txn + '</p></div> </div></div>');

// //                 });
// //             }

// //             $('.acc-amount').html(currency + formatToCurrency($balance ?? 0));
// //             $('.sms').html(currency + formatToCurrency($sms ?? 0));
// //             $('.ussd').html(currency + formatToCurrency($ussd ?? 0));
// //             $('.whatsapp').html(currency + formatToCurrency($whatsapp ?? 0));
// //           }


// //           $today_deposit = $today_withdraw = 0;
// //           $transaction_type.forEach(function (txntype) {
// //             $badge_txn = (txntype.credit_debit_type_id == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
// //             $updown = (txntype.credit_debit_type_id == 1) ? 'rate-inc' : 'rate-dec';
// //             if (txntype.credit_debit_type_id == 1) {
// //               $today_deposit += txntype.total;
// //             } else {
// //               $today_withdraw += txntype.total;
// //             }

// //             $string = txntype.transaction_type;
// //             $badge = $string.slice(0, 2); // "01"
// //             $badge = $badge.toUpperCase();
// //             $('.transaction_type').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + $string.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + formatToCurrency(txntype.total) + '</span> ' + $badge_txn + '</p></div> </div></div>');

// //           });

// //           $recent_txn.forEach(function (txn) {
// //             $badge = (txn.credit_debit_type_id == 1) ? 'outline-badge-success' : 'outline-badge-warning';
// //             $('#recent_txt').append('<tr><td><div class="td-content customer-name">' + txn.first_name + ' ' + txn.last_name + '</div></td><td><div class="td-content product-brand">' + txn.virtual_accnum + '</div></td><td><div class="td-content product-brand">' + txn.transaction_type + '</div></td><td><div class="td-content"><a class="btn btn-flat"  href="/transaction-receipt/' + txn.transaction_reference + '">' + txn.transaction_reference + '</a></div></td><td><div class="td-content pricing"><span class="">' + currency + formatToCurrency(txn.amount) + '</span></div></td><td><div class="td-content"><span class="badge ' + $badge + '">' + txn.type + '</span></div></td></tr>');
// //           });
// //           $lagest_txn.forEach(function (largetxn) {
// //             $badge = (largetxn.credit_debit_type_id == 1) ? 'outline-badge-success' : 'outline-badge-warning';
// //             $('#largest_txn').append('<tr><td><div class="td-content customer-name">' + largetxn.first_name + ' ' + largetxn.last_name + '</div></td><td><div class="td-content product-brand">' + largetxn.virtual_accnum + '</div></td><td><div class="td-content product-brand">' + largetxn.transaction_type + '</div></td><td><div class="td-content"><a class="btn btn-flat" href="/transaction-receipt/' + largetxn.transaction_reference + '">' + largetxn.transaction_reference + '</a></div></td><td><div class="td-content pricing"><span class="">' + currency + formatToCurrency(largetxn.amount) + '</span></div></td><td><div class="td-content"><span class="badge ' + $badge + '">' + largetxn.type + '</span></div></td></tr>');
// //           });


// //           // let withchrg= Number(data.display_card.withdraw.total_charge);
// //           // $('.sms-charge').html(currency + formatToCurrency(Number(data.display_card.sms)));
// //           // $('.reg-charge').html(currency + formatToCurrency(Number(data.display_card.total_new_entry)));
// //           useNextLoad();
// //         } else {

// //           // monthlydata = data.monthly_data.txn.monthly_TXN;
// //           // monthlylabel = data.monthly_data.txn.monthly_label;
// //           dailytxn = data.transaction_type_day;
// //           total_daily = data.total_daily;
// //           currency = data.currency;

// //           $permission_to_see = data.permission_to_see;
// //           total_month = currency + formatToCurrency(data.total_month ?? 0);
// //           $('.total_daily').html(currency + formatToCurrency(Number(total_daily ?? 0)));
// //           $recent_txn = data.latest_txn;
// //           $lagest_txn = data.largest_txn;
// //           $transaction_type = data.transaction_type;

// //           $investment = data.display_card.investment;
// //           $product = data.display_card.product;
// //           $dom = data.display_card.dom;

// //           $('.deposit-total').html(currency + formatToCurrency(Number(data.display_card.deposit.total ?? 0)));
// //           $('.withdraw-total').html(currency + formatToCurrency(data.display_card.withdraw.total ?? 0));

// //           $('.transaction_type').empty();
// //           $('#recent_txt').empty();

// //           $today_deposit = $today_withdraw = 0;
// //           $transaction_type.forEach(function (txntype) {
// //             $badge_txn = (txntype.credit_debit_type_id == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
// //             $updown = (txntype.credit_debit_type_id == 1) ? 'rate-inc' : 'rate-dec';
// //             if (txntype.credit_debit_type_id == 1) {
// //               $today_deposit += txntype.total;
// //             } else {
// //               $today_withdraw += txntype.total;
// //             }

// //             $string = txntype.transaction_type;
// //             $badge = $string.slice(0, 2); // "01"
// //             $badge = $badge.toUpperCase();
// //             $('.transaction_type').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + $string.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + formatToCurrency(txntype.total) + '</span> ' + $badge_txn + '</p></div> </div></div>');

// //           });

// //           $recent_txn.forEach(function (txn) {
// //             $badge = (txn.credit_debit_type_id == 1) ? 'outline-badge-success' : 'outline-badge-warning';
// //             $('#recent_txt').append('<tr><td><div class="td-content customer-name">' + txn.first_name + ' ' + txn.last_name + '</div></td><td><div class="td-content product-brand">' + txn.virtual_accnum + '</div></td><td><div class="td-content product-brand">' + txn.transaction_type + '</div></td><td><div class="td-content"><a class="btn btn-flat"  href="/transaction-receipt/' + txn.transaction_reference + '">' + txn.transaction_reference + '</a></div></td><td><div class="td-content pricing"><span class="">' + currency + formatToCurrency(txn.amount) + '</span></div></td><td><div class="td-content"><span class="badge ' + $badge + '">' + txn.type + '</span></div></td></tr>');
// //           });
// //           // let withchrg= Number(data.display_card.withdraw.total_charge);
// //           // $('.sms-charge').html(currency + formatToCurrency(Number(data.display_card.sms)));
// //           // $('.reg-charge').html(currency + formatToCurrency(Number(data.display_card.total_new_entry)));
// //           useNextLoad();


// //         }
// //       }
// //     });

// //   }
//   function company_n_data() {
//     $.ajax({
//       url: '/dashboarddata?page=company_n_data',
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         if (data.permission_to_see) {
//           currency = data.currency;
//           $('.link_cards').html(data.user_has_card);
//           $('.pending_card').html(data.user_penidng_card);
//           // step 1 restriction
//             $balance = data.wallet_data.balance;
//             $sms = data.wallet_data.sms;
//             $ussd = data.wallet_data.ussd;
//             $whatsapp = data.wallet_data.whatsapp;
//             $('.acc-amount').html(currency + formatToCurrency($balance ?? 0));
//             $('.sms').html(currency + formatToCurrency($sms ?? 0));
//             $('.ussd').html(currency + formatToCurrency($ussd ?? 0));
//             $('.whatsapp').html(currency + formatToCurrency($whatsapp ?? 0));
//         }
//       }
//     });

//   }
//   function transaction_hist_txn() {
//     $.ajax({
//       url: '/dashboarddata?page=transaction_hist_txn',
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         $permission_to_see = data.permission_to_see;
//         currency = data.currency;
//         $('.deposit-total').html(currency + formatToCurrency(Number(data.display_card.deposit.total ?? 0)));
//         $('.withdraw-total').html(currency + formatToCurrency(data.display_card.withdraw.total ?? 0));
//         if (data.permission_to_see) {
//           $investment = data.display_card.investment;
//           $product = data.display_card.product;
//         }
//       }
//     });

//   }
//   function monthly_txn() {
//     $.ajax({
//       url: '/dashboarddata?page=monthly_txn',
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         $permission_to_see = data.permission_to_see;

//         if (data.permission_to_see) {
//           //console.log(data.monthly_data.txn.monthly_TXN);
//           monthlydata = data.monthly_data.txn.monthly_TXN;
//           monthlylabel = data.monthly_data.txn.monthly_label;
//           currency = data.currency;
//           total_month = currency + formatToCurrency(data.total_month ?? 0);
//           // step 1 restriction

//         } else {
//           total_month = currency + formatToCurrency(data.total_month ?? 0);
//           $('.total_daily').html(currency + formatToCurrency(Number(total_daily ?? 0)));
//           $transaction_type = data.transaction_type;
//           $today_deposit = $today_withdraw = 0;
//           $transaction_type.forEach(function (txntype) {
//             $badge_txn = (txntype.credit_debit_type_id == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
//             $updown = (txntype.credit_debit_type_id == 1) ? 'rate-inc' : 'rate-dec';
//             if (txntype.credit_debit_type_id == 1) {
//               $today_deposit += txntype.total;
//             } else {
//               $today_withdraw += txntype.total;
//             }

//             $string = txntype.transaction_type;
//             $badge = $string.slice(0, 2); // "01"
//             $badge = $badge.toUpperCase();
//             $('.transaction_type').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + $string.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + formatToCurrency(txntype.total) + '</span> ' + $badge_txn + '</p></div> </div></div>');
//           });
//         }
//         var options1 = {
//             chart: {
//               fontFamily: 'Nunito, sans-serif',
//               height: 365,
//               type: 'area',
//               zoom: {
//                 enabled: false
//               },
//               dropShadow: {
//                 enabled: true,
//                 opacity: 0.3,
//                 blur: 5,
//                 left: -7,
//                 top: 22
//               },
//               toolbar: {
//                 show: false
//               },
//               events: {
//                 mounted: function (ctx, config) {
//                   const highest1 = ctx.getHighestValueInSeries(0);
//                   const highest2 = ctx.getHighestValueInSeries(1);

//                   ctx.addPointAnnotation({
//                     x: new Date(ctx.w.globals.seriesX[0][ctx.w.globals.series[0].indexOf(highest1)]).getTime(),
//                     y: highest1,
//                     label: {
//                       style: {
//                         cssClass: 'd-none'
//                       }
//                     },
//                     customSVG: {
//                       SVG: '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#1b55e2" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-circle"><circle cx="12" cy="12" r="10"></circle></svg>',
//                       cssClass: undefined,
//                       offsetX: -8,
//                       offsetY: 5
//                     }
//                   })

//                   ctx.addPointAnnotation({
//                     x: new Date(ctx.w.globals.seriesX[1][ctx.w.globals.series[1].indexOf(highest2)]).getTime(),
//                     y: highest2,
//                     label: {
//                       style: {
//                         cssClass: 'd-none'
//                       }
//                     },
//                     customSVG: {
//                       SVG: '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#e7515a" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-circle"><circle cx="12" cy="12" r="10"></circle></svg>',
//                       cssClass: undefined,
//                       offsetX: -8,
//                       offsetY: 5
//                     }
//                   })
//                 },
//               }
//             },
//             colors: ['#1b55e2', '#e7515a'],
//             dataLabels: {
//               enabled: false
//             },
//             markers: {
//               discrete: [{
//                 seriesIndex: 0,
//                 dataPointIndex: 7,
//                 fillColor: '#000',
//                 strokeColor: '#000',
//                 size: 5
//               }, {
//                 seriesIndex: 2,
//                 dataPointIndex: 11,
//                 fillColor: '#000',
//                 strokeColor: '#000',
//                 size: 4
//               }]
//             },
//             subtitle: {
//               text: 'Transactions',
//               align: 'left',
//               margin: 0,
//               offsetX: -10,
//               offsetY: 35,
//               floating: false,
//               style: {
//                 fontSize: '14px',
//                 color: '#888ea8'
//               }
//             },
//             title: {
//               text: total_month,
//               align: 'left',
//               margin: 0,
//               offsetX: -10,
//               offsetY: 0,
//               floating: false,
//               style: {
//                 fontSize: '25px',
//                 color: '#0e1726'
//               },
//             },
//             stroke: {
//               show: true,
//               curve: 'smooth',
//               width: 2,
//               lineCap: 'square'
//             },
//             series: [{
//               name: 'Monthly Transactions',
//               data: monthlydata
//             }],
//             labels: monthlylabel,
//             xaxis: {
//               axisBorder: {
//                 show: false
//               },
//               axisTicks: {
//                 show: false
//               },
//               crosshairs: {
//                 show: true
//               },
//               labels: {
//                 offsetX: 0,
//                 offsetY: 5,
//                 style: {
//                   fontSize: '12px',
//                   fontFamily: 'Nunito, sans-serif',
//                   cssClass: 'apexcharts-xaxis-title',
//                 },
//               }
//             },
//             yaxis: {
//               labels: {
//                 formatter: function (value, index) {
//                   return (value / 1000) + 'K'
//                 },
//                 offsetX: -22,
//                 offsetY: 0,
//                 style: {
//                   fontSize: '12px',
//                   fontFamily: 'Nunito, sans-serif',
//                   cssClass: 'apexcharts-yaxis-title',
//                 },
//               }
//             },
//             grid: {
//               borderColor: '#e0e6ed',
//               strokeDashArray: 5,
//               xaxis: {
//                 lines: {
//                   show: true
//                 }
//               },
//               yaxis: {
//                 lines: {
//                   show: false,
//                 }
//               },
//               padding: {
//                 top: 0,
//                 right: 0,
//                 bottom: 0,
//                 left: -10
//               },
//             },
//             legend: {
//               position: 'top',
//               horizontalAlign: 'right',
//               offsetY: -50,
//               fontSize: '16px',
//               fontFamily: 'Nunito, sans-serif',
//               markers: {
//                 width: 10,
//                 height: 10,
//                 strokeWidth: 0,
//                 strokeColor: '#fff',
//                 fillColors: undefined,
//                 radius: 12,
//                 onClick: undefined,
//                 offsetX: 0,
//                 offsetY: 0
//               },
//               itemMargin: {
//                 horizontal: 0,
//                 vertical: 20
//               }
//             },
//             tooltip: {
//               theme: 'dark',
//               marker: {
//                 show: true,
//               },
//               x: {
//                 show: false,
//               }
//             },
//             fill: {
//               type: "gradient",
//               gradient: {
//                 type: "vertical",
//                 shadeIntensity: 1,
//                 inverseColors: !1,
//                 opacityFrom: .28,
//                 opacityTo: .05,
//                 stops: [45, 100]
//               }
//             },
//             responsive: [{
//               breakpoint: 575,
//               options: {
//                 legend: {
//                   offsetY: -30,
//                 },
//               },
//             }]
//           }
//           /*
//             ================================
//                 Revenue Monthly | Render
//             ================================
//         */
//           var chart1 = new ApexCharts(
//             document.querySelector("#revenueMonthly"),
//             options1
//           );

//           chart1.render();


//       }
//     });

//   }
//   function txn_type() {
//     $.ajax({
//       url: '/dashboarddata?page=txn_type',
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         $permission_to_see = data.permission_to_see;

//         if (data.permission_to_see) {
//           //console.log(data.monthly_data.txn.monthly_TXN);

//           dailytxn = data.transaction_type_day;
//           total_daily = data.total_daily;
//           currency = data.currency;
//           $('.total_daily').html(currency + formatToCurrency(Number(total_daily ?? 0)));
//           $transaction_type = data.transaction_type;

//           $('.transaction_type').empty();
//           $today_deposit = $today_withdraw = 0;
//           $transaction_type.forEach(function (txntype) {
//             $badge_txn = (txntype.credit_debit_type_id == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
//             $updown = (txntype.credit_debit_type_id == 1) ? 'rate-inc' : 'rate-dec';
//             if (txntype.credit_debit_type_id == 1) {
//               $today_deposit += txntype.total;
//             } else {
//               $today_withdraw += txntype.total;
//             }
//             $string = txntype.transaction_type;
//             $badge = $string.slice(0, 2); // "01"
//             $badge = $badge.toUpperCase();
//             $('.transaction_type').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + $string.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + formatToCurrency(txntype.total) + '</span> ' + $badge_txn + '</p></div> </div></div>');
//           });
//         } else {
//           dailytxn = data.transaction_type_day;
//           total_daily = data.total_daily;
//           currency = data.currency;
//           $('.total_daily').html(currency + formatToCurrency(Number(total_daily ?? 0)));
//           $transaction_type = data.transaction_type;
//           $('.deposit-total').html(currency + formatToCurrency(Number(data.display_card.deposit.total ?? 0)));
//           $('.withdraw-total').html(currency + formatToCurrency(data.display_card.withdraw.total ?? 0));

//           $('.transaction_type').empty();
//           $('#recent_txt').empty();

//           $today_deposit = $today_withdraw = 0;
//           $transaction_type.forEach(function (txntype) {
//             $badge_txn = (txntype.credit_debit_type_id == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
//             $updown = (txntype.credit_debit_type_id == 1) ? 'rate-inc' : 'rate-dec';
//             if (txntype.credit_debit_type_id == 1) {
//               $today_deposit += txntype.total;
//             } else {
//               $today_withdraw += txntype.total;
//             }
//             $string = txntype.transaction_type;
//             $badge = $string.slice(0, 2); // "01"
//             $badge = $badge.toUpperCase();
//             $('.transaction_type').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + $string.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + formatToCurrency(txntype.total) + '</span> ' + $badge_txn + '</p></div> </div></div>');
//           });
//         }
//         $('.today_deposit').html(currency + formatToCurrency($today_deposit));
//         $('.today_withdraw').html(currency + formatToCurrency($today_withdraw));
//       }
//     });

//   }
//   function BranchData() {
//     $.ajax({
//       url: '/dashboarddata?page=branch_customer_Stat',
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         if (data.permission_to_see) {
//           currency = data.currency;
//         $('.total-branches').html(data.user.data[2]);
//           // step 1 restriction

//             $('.branch_data').empty();
//             label_user = data.user.label_user;
//             label_data = data.user.data;
//             $branch = data.branch;

//             if($branch){
//                 $branch.forEach(function (branch) {
//                 $badge_txn = (1 == 1) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
//                 $updown = '';

//                 $string = branch.name.toUpperCase();
//                 $badge = $string.slice(0, 2); // "01"
//                 $badge = $badge.toUpperCase();
//                 $('.branch_data').append('<div class="transactions-list"><div class="t-item"> <div class="t-company-name">  <div class="t-icon"><div class="avatar avatar-xl"> <span class="avatar-title rounded-circle">' + $badge + '</span></div></div> <div class="t-name"><h4>' + branch.name.toUpperCase() + '</h4> <p class="meta-date">---</p></div></div> <div class="t-rate ' + $updown + '"><p><span>' + currency + (formatToCurrency(branch.balance)) + '</span> ' + $badge_txn + '</p></div> </div></div>');

//                 });
//             }
//                 var options = {
//                   chart: {
//                     type: 'donut',
//                     width: 380
//                   },
//                   colors: ['#5c1ac3', '003314', '#e7515a', '003314'],
//                   dataLabels: {
//                     enabled: false
//                   },
//                   legend: {
//                     position: 'bottom',
//                     horizontalAlign: 'center',
//                     fontSize: '14px',
//                     markers: {
//                       width: 10,
//                       height: 10,
//                     },
//                     itemMargin: {
//                       horizontal: 0,
//                       vertical: 8
//                     }
//                   },
//                   plotOptions: {
//                     pie: {
//                       donut: {
//                         size: '65%',
//                         background: 'transparent',
//                         labels: {
//                           show: true,
//                           name: {
//                             show: true,
//                             fontSize: '29px',
//                             fontFamily: 'Nunito, sans-serif',
//                             color: undefined,
//                             offsetY: -10
//                           },
//                           value: {
//                             show: true,
//                             fontSize: '26px',
//                             fontFamily: 'Nunito, sans-serif',
//                             color: '20',
//                             offsetY: 16,
//                             formatter: function (val) {
//                               return val
//                             }
//                           },
//                           total: {
//                             show: true,
//                             showAlways: true,
//                             label: 'Total',
//                             color: '#888ea8',
//                             formatter: function (w) {
//                               return w.globals.seriesTotals.reduce(function (a, b) {
//                                 return a + b
//                               }, 0)
//                             }
//                           }
//                         }
//                       }
//                     }
//                   },
//                   stroke: {
//                     show: true,
//                     width: 25,
//                   },

//                   series: label_data,
//                   labels: label_user,
//                   responsive: [{
//                     breakpoint: 1599,
//                     options: {
//                       chart: {
//                         width: '350px',
//                         height: '400px'
//                       },
//                       legend: {
//                         position: 'bottom'
//                       }
//                     },

//                     breakpoint: 1439,
//                     options: {
//                       chart: {
//                         width: '250px',
//                         height: '390px'
//                       },
//                       legend: {
//                         position: 'bottom'
//                       },
//                       plotOptions: {
//                         pie: {
//                           donut: {
//                             size: '65%',
//                           }
//                         }
//                       }
//                     },
//                   }]
//                 }
//                 var chart = new ApexCharts(
//                   document.querySelector("#chart-2"),
//                   options
//                 );
//                 chart.render();
//         }
//       }
//     });

//   }

//   function largest_LatestTXN() {
//     $.ajax({
//       url: '/dashboarddata?page=latestTXN',
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         if (data.permission_to_see) {
//           $recent_txn = data.latest_txn;
//           $lagest_txn = data.largest_txn;
//           $('#recent_txt').empty( );
//           $('#largest_txn').empty();
//           // step 1 restriction
//           $recent_txn.forEach(function (txn) {
//             $badge = (txn.credit_debit_type_id == 1) ? 'outline-badge-success' : 'outline-badge-warning';
//             $('#recent_txt').append('<tr><td><div class="td-content customer-name">' + txn.first_name + ' ' + txn.last_name + '</div></td><td><div class="td-content product-brand">' + txn.virtual_accnum + '</div></td><td><div class="td-content product-brand">' + txn.transaction_type + '</div></td><td><div class="td-content"><a class="btn btn-flat"  href="/transaction-receipt/' + txn.transaction_reference + '">' + txn.transaction_reference + '</a></div></td><td><div class="td-content pricing"><span class="">' + currency + formatToCurrency(txn.amount) + '</span></div></td><td><div class="td-content"><span class="badge ' + $badge + '">' + txn.type + '</span></div></td></tr>');
//           });
//           $lagest_txn.forEach(function (largetxn) {
//             $badge = (largetxn.credit_debit_type_id == 1) ? 'outline-badge-success' : 'outline-badge-warning';
//             $('#largest_txn').append('<tr><td><div class="td-content customer-name">' + largetxn.first_name + ' ' + largetxn.last_name + '</div></td><td><div class="td-content product-brand">' + largetxn.virtual_accnum + '</div></td><td><div class="td-content product-brand">' + largetxn.transaction_type + '</div></td><td><div class="td-content"><a class="btn btn-flat" href="/transaction-receipt/' + largetxn.transaction_reference + '">' + largetxn.transaction_reference + '</a></div></td><td><div class="td-content pricing"><span class="">' + currency + formatToCurrency(largetxn.amount) + '</span></div></td><td><div class="td-content"><span class="badge ' + $badge + '">' + largetxn.type + '</span></div></td></tr>');
//           });
//         } else {
//           $recent_txn = data.latest_txn;
//           $lagest_txn = data.largest_txn;
//           $('#recent_txt').empty();
//           $recent_txn.forEach(function (txn) {
//             $badge = (txn.credit_debit_type_id == 1) ? 'outline-badge-success' : 'outline-badge-warning';
//             $('#recent_txt').append('<tr><td><div class="td-content customer-name">' + txn.first_name + ' ' + txn.last_name + '</div></td><td><div class="td-content product-brand">' + txn.virtual_accnum + '</div></td><td><div class="td-content product-brand">' + txn.transaction_type + '</div></td><td><div class="td-content"><a class="btn btn-flat"  href="/transaction-receipt/' + txn.transaction_reference + '">' + txn.transaction_reference + '</a></div></td><td><div class="td-content pricing"><span class="">' + currency + formatToCurrency(txn.amount) + '</span></div></td><td><div class="td-content"><span class="badge ' + $badge + '">' + txn.type + '</span></div></td></tr>');
//           });
//         }
//       }
//     });

//   }
//   //to load

//   largest_LatestTXN();
//   BranchData();
//   txn_type();
//   monthly_txn();
// transaction_hist_txn();//useless i think
//     company_n_data(); //load company wallet rec
//   function load_data_full() {
//     console.log("We want to load full dashboard");
//     setModalButtonLoading(true);
//     $("#fulDashboardDiv").show();
//     $("#loadDashboardButtonDiv").hide();
//     loadallpage('loan');
//     loadallpage('investment');
//     loadallpage('esusu');
//     loadallpage('dom');
//     loadallpage('product');
//     loadallpage('pending');
//     loadallpage('unpaid_sms_Charges');

//   }
//   function loadallpage(page) {
//     $.ajax({
//       url: '/dashboarddatafull?page='+page,
//       type: 'get',
//       dataType: 'json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: function (data) {
//         dailytxn = data.transaction_type_day;
//         total_daily = data.total_daily;
//         currency = data.currency;
//         $permission_to_see = data.permission_to_see;
//         //investment
//         if(page == "investment"){
//             $('.total_investment').html(data.investment.total);
//             $('.active_investment').html(data.investment.active);
//             $('.complete_investment').html(data.investment.complete);
//         // $investment = data.display_card.investment;

//             return false;
//         }
//         //loan
//         if(page == "loan"){
//             $('.loanActiveCount').html(data.loanActiveCount);
//             $('.completeCount').html(data.completeCount);
//             $('.pendingCount').html(data.pendingCount);
//             $('.writeOff').html(data.writeOff);
//             $('.disbursmentCount').html(data.disbursmentCount);
//             $('.Ipaidamount').html(formatToCurrency(data.paymentData[0].Ipaidamount ?? 0));
//             $('.Ppaidamount').html(formatToCurrency(data.paymentData[0].Ppaidamount ?? 0));
//             $('.mgrtFeePaid').html(formatToCurrency(data.paymentData[0].mgrtFeePaid ?? 0));
//             $('.loanFeePaid').html(formatToCurrency(data.paymentData[0].loanFeePaid ?? 0));
//             $('.paidamount').html(formatToCurrency(data.paymentData[0].paidamount ?? 0));
//             $('.InitPamount').html(formatToCurrency(data.activeData[0].InitPamount ?? 0));
//             $('.TloanBalance').html(formatToCurrency(data.activeData[0].TloanBalance ?? 0));
//             $('.TprincipalLeft').html(formatToCurrency(data.activeData[0].TprincipalLeft ?? 0));
//             $('.TInterestLeft').html(formatToCurrency(data.activeData[0].TInterestLeft ?? 0));
//             return false;
//         }
//         //esusu
//         if(page == "esusu"){
//             $('.esusu_count').html(data.esusu.count);
//             $('.esusu_wallet').html(formatToCurrency(data.esusu.record[0].wallet ?? 0));
//             $('.esusu_withdrawable').html(formatToCurrency(data.esusu.record[0].withdrawable ?? 0));
//         }
//         // $auth_wallet = currency + formatToCurrency(Number(data.auth_wallet));
//         // $('.auth_wallet').html($auth_wallet);

//         $transaction_type = data.transaction_type;
//         if(page == "dom"){
//             $dom = data.dom;
//         }
//         if(page == "product"){
//             $product = data.display_card.product;
//             $product.forEach(function (txnsaving) {
//             $('.savingsdata').append(' <div class="col-md-3"><div class="card-counter danger"> <i class="fa fa-dollar"></i> <span class="count-numbers">' + currency + (formatToCurrency(txnsaving.balance)) + '</span><span class="count-name">' + txnsaving.savings_product + '</span></div></div>');
//             });
//         }
//         // step 1 restriction
//         if(page == "pending"){
//             $('.pending_entry').html(currency + formatToCurrency(Number(data.total_new_entry)));
//             $('.reg-charge').html(currency + formatToCurrency(Number(data.total_new_entry)));
//         }
//         // let depchrg = Number(data.display_card.deposit.total_charge);
//         // let withchrg = Number(data.display_card.withdraw.total_charge);
//         // $('.withdraw-charge').html(currency + formatToCurrency(data.display_card.withdraw.total_charge));
//         // $('.deposit-charge').html(currency + formatToCurrency(Number(data.display_card.deposit.total_charge)));
//         // $('.sms-charge').html(currency + formatToCurrency(Number(data.display_card.sms)));
//         // unpaid_sms_Charges
//         if(page == "unpaid_sms_Charges"){
//             $('.unpaid-sms').html(currency + formatToCurrency(Number(data.display_card.unpaidsms)));
//         }
//       }
//     });
//   }

// //    load_data();
//   // alert(monthlylabel)
//   function useNextLoad() {
//     console.log(monthlylabel);



//     var d_2options2 = {
//       chart: {
//         id: 'sparkline1',
//         group: 'sparklines',
//         type: 'area',
//         height: 280,
//         sparkline: {
//           enabled: true
//         },
//       },
//       stroke: {
//         curve: 'smooth',
//         width: 2
//       },
//       fill: {
//         opacity: 1,
//       },
//       series: [{
//         name: 'txn',
//         data: dailytxn
//       }],
//       labels: dailytxn,
//       yaxis: {
//         min: 0
//       },
//       grid: {
//         padding: {
//           top: 125,
//           right: 0,
//           bottom: 36,
//           left: 0
//         },
//       },
//       fill: {
//         type: "gradient",
//         gradient: {
//           type: "vertical",
//           shadeIntensity: 1,
//           inverseColors: !1,
//           opacityFrom: .40,
//           opacityTo: .05,
//           stops: [45, 100]
//         }
//       },
//       tooltip: {
//         x: {
//           show: false,
//         },
//         theme: 'dark'
//       },
//       colors: ['#fff']
//     }

//     /*
//         ============================
//             Total Orders | Render
//         ============================
//     */
//     var d_2C_2 = new ApexCharts(document.querySelector("#total-orders"), d_2options2);
//     d_2C_2.render();



//     /*
//         ==================================
//             Sales By Category | Options
//         ==================================
//     */


//   }
//   // const ps = new PerfectScrollbar(document.querySelector('.mt-container'));
//   //
// function fund_wallet() {
//   $wallet = $('#wallet').val();
//   $amount = $('#amount').val();
//   // ajax_operation('post', '/dashboard/fund-company-wallet', {wallet:$wallet,amount:$amount});
//   $.ajax({
//     url: '/dashboard/fund-company-wallet',
//     type: 'post',
//     data: { wallet: $wallet, amount: $amount },
//     dataType: 'json',
//     headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//     success: function (data) {
//       if (data.status == 1) {
//         $status = 'success';
//         $complete = 'Done';
//         swal(
//           $complete,
//           data.message,
//           $status
//         );
//         load_data();
//       } else {

//         $status = 'error';
//         $complete = 'Error!';
//         swal(
//           $complete,
//           data.message,
//           $status
//         )
//       }

//     }
//   });
// }
// function setModalButtonLoading(isLoading) {

//   $('#loadDashboardBtn').prop("disabled", isLoading);
//   $('#loadDashboardBtn').html(isLoading ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Loading...' :
//     'Load Full Dashboard');
// }
// function generate_ussd_string(amount) {
//   swal({
//     title: 'Are you sure to generate USSD shared string for your customer registration?',
//     text: "Note: you will be charged " + amount + " for this transaction!",
//     type: 'warning',
//     showCancelButton: true,
//     confirmButtonText: 'Proceed',
//     padding: '2em'
//   }).then(function (result) {
//     if (result.value) {
//       $.ajax({
//         url: '/dashboard/generate-ussd-string',
//         type: 'post',

//         dataType: 'json',
//         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//         success: function (data) {
//           $status = '';
//           $complete = '';
//           if (data.status) {
//             $status = 'success';
//             $complete = 'Done!';
//           } else {
//             $status = 'error';
//             $complete = 'Error!';
//             //refresh

//           }
//           swal(
//             $complete,
//             data.message,
//             $status
//           )

//           if (data.refresh) {
//             window.location.replace(window.location.origin);
//           }

//         }
//       });

//     }
//   })
// }
// } catch (e) {
//     console.log(e);
//     alert('Erorr')
//   }
