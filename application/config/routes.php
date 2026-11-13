<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller']                            = 'Auth';
$route['404_override']                                  = '';
$route['translate_uri_dashes']                          = FALSE;

//user
$route['user']                                          = 'user/C_User';
$route['usermanagement']                                = 'user/C_User';
$route['adduser']                                       = 'user/C_User/AddUser';
$route['edituser']                                      = 'user/C_User/EditUser';
$route['deleteuser/(:num)']                             = 'user/C_User/DeleteUser/$1';
$route['usersetting']                                   = 'user/C_User/usersetting';
$route['editPass']                                      = 'user/C_User/editPassword';

//admin data view
$route['admin-data']                                    = 'admin/C_AdminData';
$route['admin-data/table/(:any)']                       = 'admin/C_AdminData/index/$1';
$route['admin-data/update']                             = 'admin/C_AdminData/update';

//auth
$route['login']                                         = 'auth/process';
$route['logout']                                        = 'auth/logout';
$route['dashboard']                                     = 'dashboard';

//suplier 
$route['suplier']                                       = 'Suplier/C_Suplier';
$route['add_suplier']                                   = 'purchaseorder/C_Order/addSuplier';

//PurchaseOrder
$route['purchase']                                      = 'purchaseorder/C_Order';
$route['purchase/sup/(:any)']                           = 'purchaseorder/C_Order/purchaseSuplier/$1';
$route['purchase/listBarang/(:any)']                    = 'purchaseorder/C_Order/listBarang/$1';
$route['addBarangSuplier']                              = 'purchaseorder/C_Order/addBarang';
$route['editbarangsuplier']                             = 'purchaseorder/C_Order/editBarangSuplier';
$route['editSuplier']                                   = 'purchaseorder/C_Order/editSuplier';
$route['editBarang']                                    = 'purchaseorder/C_Order/editBarang';
$route['tambahChart']                                   = 'purchaseorder/C_Order/addChart';
$route['addchart']                                      = 'purchase/C_Order/addChart';
$route['hapusBarang/(:any)/(:any)']                     = 'purchaseorder/C_Order/hapusBarang/$1/$2';
$route['hapusChart/(:any)/(:any)']                      = 'purchaseorder/C_Order/hapusChart/$1/$2';
$route['rekam_po']                                      = 'purchaseorder/C_Order/rekam_po';
$route['purchase/check-nomor-po']                       = 'purchaseorder/C_Order/checkNomorPo';
$route['edit_barang_tmp']                               = 'purchaseorder/C_Order/edit_barang_tmp';
$route['add_tax_tmp']                                   = 'purchaseorder/C_Order/add_tax_tmp';
$route['update_tax_tmp']                                = 'purchaseorder/C_Order/update_tax_tmp';

//MASTER BARANG NON KOMERSIL
$route['masterbarangnk']                                = 'master_barang/C_MasterBarang';
$route['masterbarangnk/check-kode']                     = 'master_barang/C_MasterBarang/cek_kode_barangnk';
$route['add_mbarang']                                   = 'master_barang/C_MasterBarang/add_mbarang';
$route['edit_mbarangnk']                                = 'master_barang/C_MasterBarang/edit_mbarangnk';
$route['delmbarangnk']                                  = 'master_barang/C_MasterBarang/delmbarangnk';
$route['uploadmbarangnk']                               = 'master_barang/C_MasterBarang/uploadgbrbarang';
$route['add_mbarang_tmp']                               = 'master_barang/C_MasterBarang/addrequestmasterbarang';
$route['add_mbarang_tmps']                              = 'master_barang/C_MasterBarang/addrequestmasterbarangs';
$route['genqrcode/(:any)/(:any)/(:any)']                = 'master_barang/C_MasterBarang/inputqrcbrnk/$1/$2/$3';
$route['aprovedmasterbarang']                           = 'master_barang/C_MasterBarang/aprovedmasterbarang';
$route['vrequestmbarang']                               = 'master_barang/C_MasterBarang/vrequestmbarang';
$route['vrequestmbarang/pojasa/decide']                 = 'master_barang/C_MasterBarang/keputusan_usulan_pojasa';

//MASTER BARANG KOMERSIL                                        
$route['masterbarangkomersil']                          = 'master_barang/C_MasterBarang/masterbarangkomersil';
$route['masterbarangkomersil/data']                     = 'master_barang/C_MasterBarang/ajax_masterbarangkomersil';
$route['masterbarangkomersil/detail/(:num)']            = 'master_barang/C_MasterBarang/detailmasterbarangkomersil/$1';
$route['masterbarangkomersil/get/(:num)']                = 'master_barang/C_MasterBarang/ajax_get_masterbarangkomersil/$1';
$route['masterbarangkomersil/save']                     = 'master_barang/C_MasterBarang/ajax_save_masterbarangkomersil';
$route['masterbarangkomersil/delete']                   = 'master_barang/C_MasterBarang/ajax_delete_masterbarangkomersil';

// Purchase Order Jasa
$route['pojasa']                                        = 'purchaseorder/C_Pojasa';
$route['pononkomersiljasa']                             = 'purchaseorder/C_Pojasa';
$route['pojasa/vendor/save']                            = 'purchaseorder/C_Pojasa/save_vendor';
$route['pojasa/vendor/update']                          = 'purchaseorder/C_Pojasa/update_vendor';
$route['pojasa/vendor/delete']                          = 'purchaseorder/C_Pojasa/delete_vendor';
$route['pojasa/vendor/approval']                        = 'purchaseorder/C_Pojasa/vendor_approval';
$route['pojasa/request/save']                           = 'purchaseorder/C_Pojasa/save_request';
$route['pojasa/request/revise']                         = 'purchaseorder/C_Pojasa/revise_request';
$route['pojasa/scope/save']                             = 'purchaseorder/C_Pojasa/save_scope_review';
$route['pojasa/detail/(:any)']                          = 'purchaseorder/C_Pojasa/detail/$1';
$route['pojasa/approval']['post']                       = 'purchaseorder/C_PojasaAjax/approval_action';
$route['pojasa/generate-spk']['post']                   = 'purchaseorder/C_PojasaAjax/manual_spk_not_allowed';
$route['pojasa/progress/save']['post']                  = 'purchaseorder/C_PojasaPic/save_progress';
$route['pojasa/file/upload']                            = 'purchaseorder/C_Pojasa/upload_file';
$route['pojasa/file/update']                            = 'purchaseorder/C_Pojasa/update_file';
$route['pojasa/file/replace']                           = 'purchaseorder/C_Pojasa/replace_file';
$route['pojasa/file/delete']                            = 'purchaseorder/C_Pojasa/delete_file';
$route['pojasa/file/view/(:num)']                       = 'purchaseorder/C_Pojasa/view_file/$1';
$route['pojasa/file/download/(:num)']                   = 'purchaseorder/C_Pojasa/download_file/$1';
$route['pojasa/biaya/save']['post']                     = 'purchaseorder/C_PojasaAjax/save_cost';
$route['pojasa/settlement/(:any)']['get']                 = 'purchaseorder/C_Pojasa/settlement/$1';
$route['pojasa/bast/complete']                          = 'purchaseorder/C_Pojasa/complete_bast';
$route['pojasa/payment/save']                           = 'purchaseorder/C_Pojasa/save_payment';
$route['pojasa/evaluation/save']                        = 'purchaseorder/C_Pojasa/save_evaluation';
$route['pojasa/report']                                 = 'purchaseorder/C_Pojasa/report';
$route['pojasa/ajax/notifications']['get']              = 'purchaseorder/C_PojasaAjax/notifications';
$route['pojasa/ajax/notifications/read']['post']        = 'purchaseorder/C_PojasaAjax/mark_notification_read';
$route['pojasa/ajax/approval/action']['post']           = 'purchaseorder/C_PojasaAjax/approval_action';
$route['pojasa/ajax/cost/save']['post']                 = 'purchaseorder/C_PojasaAjax/save_cost';
$route['pojasa/ajax/cost/update']['post']               = 'purchaseorder/C_PojasaAjax/update_cost';
$route['pojasa/ajax/cost/verify']['post']               = 'purchaseorder/C_PojasaAjax/verify_cost';
$route['pojasa/ajax/stock/confirm-receipt']['post']     = 'purchaseorder/C_PojasaAjax/confirm_stock_receipt';
$route['pojasa/pic']['get']                            = 'purchaseorder/C_PojasaPic/index';
$route['pojasa/pic/create']['get']                     = 'purchaseorder/C_PojasaPic/create';
$route['pojasa/pic/edit/(:any)']['get']                = 'purchaseorder/C_PojasaPic/edit/$1';
$route['pojasa/pic/detail/(:any)']['get']              = 'purchaseorder/C_PojasaPic/detail/$1';
$route['pojasa/pic/document/(:num)']['get']             = 'purchaseorder/C_PojasaPic/view_document/$1';
$route['pojasa/ajax/pic/requests']['get']               = 'purchaseorder/C_PojasaPic/datatable';
$route['pojasa/ajax/pic/material-catalog']['get']       = 'purchaseorder/C_PojasaPic/material_catalog';
$route['pojasa/ajax/pic/draft/save']['post']            = 'purchaseorder/C_PojasaPic/save_draft';
$route['pojasa/ajax/pic/submit']['post']                = 'purchaseorder/C_PojasaPic/submit';
$route['pojasa/ajax/pic/draft/delete']['post']          = 'purchaseorder/C_PojasaPic/delete_draft';
$route['pojasa/ajax/pic/documents/(:any)']['get']       = 'purchaseorder/C_PojasaPic/documents/$1';
$route['pojasa/ajax/pic/documents/upload']['post']      = 'purchaseorder/C_PojasaPic/upload_documents';
$route['pojasa/ajax/pic/documents/delete']['post']      = 'purchaseorder/C_PojasaPic/delete_document';
$route['pojasa/ajax/pic/execution/(:any)']['get']       = 'purchaseorder/C_PojasaPic/execution_state/$1';
$route['pojasa/ajax/pic/progress/save']['post']         = 'purchaseorder/C_PojasaPic/save_progress';
$route['pojasa/ajax/pic/pickup/request']['post']        = 'purchaseorder/C_PojasaPic/request_pickup';
$route['pojasa/workflow']['get']                        = 'purchaseorder/C_PojasaWorkflow/index';
$route['pojasa/workflow/detail/(:any)']['get']          = 'purchaseorder/C_PojasaWorkflow/detail/$1';
$route['pojasa/workflow/document/(:num)']['get']        = 'purchaseorder/C_PojasaWorkflow/document/$1';
$route['pojasa/workflow/document/(:num)/preview']['get'] = 'purchaseorder/C_PojasaWorkflow/document_preview/$1';
$route['pojasa/workflow/document/(:num)/download']['get'] = 'purchaseorder/C_PojasaWorkflow/document_download/$1';
$route['pojasa/ajax/workflow/planning/(:any)/(:num)']['get'] = 'purchaseorder/C_PojasaWorkflow/planning_detail/$1/$2';
$route['pojasa/ajax/workflow/requests']['get']          = 'purchaseorder/C_PojasaWorkflow/datatable';
$route['pojasa/ajax/workflow/state/(:any)']['get']      = 'purchaseorder/C_PojasaWorkflow/state/$1';
$route['pojasa/ajax/workflow/purchasing/save']['post']  = 'purchaseorder/C_PojasaWorkflow/save_purchasing_review';
$route['pojasa/ajax/workflow/pickup/decision']['post']  = 'purchaseorder/C_PojasaWorkflow/pickup_decision';
$route['pojasa/ajax/workflow/demo/create']['post']      = 'purchaseorder/C_PojasaWorkflow/create_demo';
$route['pojasa/purchasing/detail/(:any)']['get']        = 'purchaseorder/C_PojasaPurchasing/detail/$1';
$route['pojasa/ajax/purchasing/state/(:any)']['get']    = 'purchaseorder/C_PojasaPurchasing/state/$1';
$route['pojasa/ajax/purchasing/vendors']['get']         = 'purchaseorder/C_PojasaPurchasing/vendor_search';
$route['pojasa/ajax/purchasing/comparison/save']['post'] = 'purchaseorder/C_PojasaPurchasing/comparison_save';
$route['pojasa/ajax/purchasing/comparison/delete']['post'] = 'purchaseorder/C_PojasaPurchasing/comparison_delete';
$route['pojasa/ajax/purchasing/comparison/select']['post'] = 'purchaseorder/C_PojasaPurchasing/comparison_select';
$route['pojasa/ajax/purchasing/stocks']['get']          = 'purchaseorder/C_PojasaPurchasing/stock_search';
$route['pojasa/ajax/purchasing/stock/allocate']['post'] = 'purchaseorder/C_PojasaPurchasing/stock_allocate';
$route['pojasa/ajax/purchasing/stock/release']['post']  = 'purchaseorder/C_PojasaPurchasing/stock_release';
$route['pojasa/ajax/purchasing/draft/save']['post']     = 'purchaseorder/C_PojasaPurchasing/draft_save';
$route['pojasa/ajax/purchasing/draft/cancel']['post']   = 'purchaseorder/C_PojasaPurchasing/draft_cancel';
$route['pojasa/ajax/purchasing/purchase/submit']['post'] = 'purchaseorder/C_PojasaPurchasing/purchase_submit';
$route['pojasa/ajax/purchasing/purchase/delete']['post'] = 'purchaseorder/C_PojasaPurchasing/purchase_delete';
$route['pojasa/ajax/purchasing/purchase/dev-submit']['post'] = 'purchaseorder/C_PojasaPurchasing/dev_purchase_submit';
$route['pojasa/ajax/purchasing/purchase/receive']['post'] = 'purchaseorder/C_PojasaPurchasing/purchase_receive';
$route['pojasa/ajax/purchasing/purchase/reverse']['post'] = 'purchaseorder/C_PojasaPurchasing/purchase_reverse';
$route['pojasa/ajax/purchasing/pickup/prepare']['post'] = 'purchaseorder/C_PojasaPurchasing/pickup_prepare';
$route['pojasa/ajax/purchasing/change/submit']['post']  = 'purchaseorder/C_PojasaPurchasing/change_submit';
$route['pojasa/ajax/purchasing/change/decide']['post']  = 'purchaseorder/C_PojasaPurchasing/change_decide';
$route['pojasa/ajax/purchasing/spk-revision/submit']['post'] = 'purchaseorder/C_PojasaPurchasing/spk_revision_submit';
$route['pojasa/ajax/purchasing/spk-revision/decision']['post'] = 'purchaseorder/C_PojasaPurchasing/spk_revision_decide';
$route['pojasa/spk/(:any)/version/(:num)']['get']       = 'purchaseorder/C_PojasaPurchasing/spk_version_view/$1/$2';
$route['pojasa/spk/(:any)/version/(:num)/print']['get'] = 'purchaseorder/C_PojasaPurchasing/spk_version_print/$1/$2';
$route['pojasa/spk/(:any)']['get']                      = 'purchaseorder/C_PojasaPurchasing/spk_view/$1';
$route['pojasa/spk/(:any)/print']['get']                = 'purchaseorder/C_PojasaPurchasing/spk_print/$1';
$route['pojasa/spk/(:any)/download']['get']             = 'purchaseorder/C_PojasaPurchasing/spk_download/$1';

// Stock PO Non Komersil
$route['stocknonkomersil']                              = 'stock/C_Stocknonkomersil';
$route['stocknonkomersil/data']                         = 'stock/C_Stocknonkomersil/ajax_stocknonkomersil';
$route['stocknonkomersil/update_lokasi']                = 'stock/C_Stocknonkomersil/update_lokasi_barang';
$route['stocknonkomersil/update_minimum_stock']          = 'stock/C_Stocknonkomersil/update_minimum_stock';
$route['stocknonkomersil/save_lifo_price']               = 'stock/C_Stocknonkomersil/save_lifo_price';
$route['pononkomersil/list_stocknkpo']                  = 'stock/C_Stocknonkomersil/list_stock_non_komersil_po';
$route['detailtransaksi/(:any)']                        = 'stock/C_Stocknonkomersil/detailtransaksi/$1';
$route['revisitr/(:any)/(:any)/(:any)']                 = 'stock/C_Stocknonkomersil/revisitr/$1/$2/$3';
$route['adjustmenqty']                                  = 'stock/C_Stocknonkomersil/adjustmenqty';
$route['nkrestok']                                      = 'stock/C_Stocknonkomersil/nkrestok';
$route['indraftrestock']                                = 'stock/C_Stocknonkomersil/indraftrestock';
$route['stockcontrollernk']                             = 'stock/C_Stockcontroller';
$route['stock/filterqtybytgl']                          = 'stock/C_Stocknonkomersil/filterqtybytgl';
$route['stockopnamenk']                                 = 'stock/C_Stocknonkomersil/stock_opname';
$route['stockopnamenk/save']                            = 'stock/C_Stocknonkomersil/save_stock_opname';
$route['stockopnamenk/detail/(:num)']                   = 'stock/C_Stocknonkomersil/detail_stock_opname/$1';
$route['master_lokasi']                                 = 'stock/C_Stocknonkomersil/master_lokasi';
$route['add_master_lokasi']                             = 'stock/C_Stocknonkomersil/add_master_lokasi';
$route['edit_master_lokasi']                            = 'stock/C_Stocknonkomersil/edit_master_lokasi';
$route['hapus_master_lokasi/(:num)']                    = 'stock/C_Stocknonkomersil/hapus_master_lokasi/$1';

$route['tr_trash/(:any)/(:any)']                        = 'stock/C_Stocknonkomersil/trash_transaksi/$1/$2';

// Stock PO Komersil
$route['stockkomersil']                                 = 'stock/C_Stockkomersil/api_stock_komersil';
$route['servergetallkomersil']                          = 'stock/C_Stockkomersil/servergetallkomersil';
$route['stockkomersil/data']                            = 'stock/C_Stockkomersil/api_stock_per_gudang';
$route['brgdetkomersil/(:any)']                         = 'stock/C_Stockkomersil/brgdetkomersil/$1';

//PurchaseOrderNonKomersil
$route['pononkomersil']                                 = 'purchaseorder/C_Order/pononkomersil';
$route['uploadfilegambaredit']                          = 'purchaseorder/C_Order/uploadfilegambaredit';
$route['addtmpbarangnonkomersil']                       = 'purchaseorder/C_Order/tmp_add_barang_komersil';
$route['edittmpbarangnonkomersil']                      = 'purchaseorder/C_Order/tmp_edit_barang_komersil';
$route['hapustmpbarangnonkomersil']                     = 'purchaseorder/C_Order/tmp_hapus_barang_komersil';
$route['addtmpdiskonnk']                                = 'purchaseorder/C_Order/tmp_add_diskon_komersil';
$route['imgedit']                                       = 'purchaseorder/C_Order/imgedit';
$route['add_note_pembelian_tmp']                        = 'purchaseorder/C_Order/add_note_pembelian_tmp';
$route['rekam_po_nk']                                   = 'purchaseorder/C_Order/rekam_po_nk';
$route['edit_note_pembelian_tmp']                       = 'purchaseorder/C_Order/edit_note_pembelian_tmp';
$route['hapus_note_pembelian_tmp/(:any)']               = 'purchaseorder/C_Order/hapus_note_pembelian_tmp/$1';
$route['pononkomersil/list_stocknkpo/addtmpbarangnk']   = 'purchaseorder/C_Order/addtmpponk';



// TESTING
$route['testing']                                       = 'settings/C_Testing/';

// PO REQUEST BY PIC
$route['reqpic']                                        = 'purchaseorder/C_Reqpic';
$route['reqpic/admrestock']                             = 'purchaseorder/C_Reqpic/admrestock';
$route['historireqpic']                                 = 'purchaseorder/C_Reqpic/historireqpic';
$route['reqpicaccreq']                                  = 'purchaseorder/C_Reqpic/index_accreq';
$route['reqpic/ajax/request-list']                       = 'purchaseorder/C_Reqpic/ajax_request_list';
$route['reqpicacckadep']                                = 'purchaseorder/C_Reqpic/index_acckadep';
$route['index_brsedia']                                 = 'purchaseorder/C_Reqpic/index_brsedia';
$route['index_done']                                    = 'purchaseorder/C_Reqpic/index_done';
$route['reqpicpickup']                                  = 'purchaseorder/C_Reqpic/index_pickup';
$route['reqpicpickupapproval']                          = 'purchaseorder/C_Reqpic/index_pickup_approval_kadep';
$route['reqpic/pickup-approval/process-timeout']         = 'purchaseorder/C_Reqpic/process_pickup_after_kadep_timeout';
$route['historipengambilanpic']                         = 'purchaseorder/C_Reqpic/history_pickup_pic';
$route['listbarangready']                               = 'purchaseorder/C_Reqpic/list_barang_ready';
$route['add_mbarang_tmp']                               = 'purchaseorder/C_Reqpic/addrequestmasterbarangready';
$route['addtmpreqbarang']                               = 'purchaseorder/C_Reqpic/addtmpreqbarang';
$route['addnewreq/(:any)']                              = 'purchaseorder/C_Reqpic/addnewreq/$1';
$route['reqpic/document/(:num)']                        = 'purchaseorder/C_Reqpic/supporting_document/$1';

$route['editedreqpic']                                  = 'purchaseorder/C_Reqpic/editedreqpic';
$route['deletedtmpnkreq']                               = 'purchaseorder/C_Reqpic/deletedtmpnkreq';
$route['reqpic/detreqbarangpic/(:any)']                 = 'purchaseorder/C_Reqpic/detreqbarangpic/$1';
$route['confirmreq']                                    = 'purchaseorder/C_Reqpic/confirmreq';
$route['pendingreq']                                    = 'purchaseorder/C_Reqpic/pendingreq';
$route['actpending/(:any)/(:any)']                      = 'purchaseorder/C_Reqpic/actpending/$1/$2';
$route['actconfirm/(:any)']                             = 'purchaseorder/C_Reqpic/actconfirm/$1/';
$route['acc_req_admin']                                 = 'purchaseorder/C_Reqpic/acc_req_admin';
$route['acc_req_admin_plus']                            = 'purchaseorder/C_Reqpic/acc_req_admin_plus';
$route['acc_req_kadep']                                 = 'purchaseorder/C_Reqpic/acc_req_kadep';
$route['reject_req_kadep']                              = 'purchaseorder/C_Reqpic/reject_req_kadep';
$route['process_req_kadep']                             = 'purchaseorder/C_Reqpic/process_req_kadep';
$route['pendingreq1']                                   = 'purchaseorder/C_Reqpic/pendingreq1';
$route['accreqpic']                                     = 'purchaseorder/C_Reqpic/accreqpic';
$route['reqpicconfirmed']                               = 'purchaseorder/C_Reqpic/reqpicconfirmed';
$route['reqpicconfirmed_plus']                          = 'purchaseorder/C_Reqpic/reqpicconfirmed_plus';
$route['reqpicdone']                                    = 'purchaseorder/C_Reqpic/reqpicdone';
$route['reqpic/request-pickup']                         = 'purchaseorder/C_Reqpic/request_pickup_kadep';
$route['reqpic/pickup-approval/request']                = 'purchaseorder/C_Reqpic/request_pickup_approval_pic';
$route['reqpic/pickup-approval/decision']               = 'purchaseorder/C_Reqpic/decide_pickup_approval_kadep';
$route['po_nk_req_revisi_note']                         = 'purchaseorder/C_Reqpic/po_nk_req_revisi_note';
$route['updated_det_req_po_nk']                         = 'purchaseorder/C_Reqpic/updated_det_req_po_nk';
$route['reqpic/requestpending/(:any)']                  = 'purchaseorder/C_Reqpic/requestpending/$1';
$route['requestpendings']                               = 'purchaseorder/C_Reqpic/requestpendings';
$route['updated_po_nk/(:any)']                          = 'purchaseorder/C_Reqpic/updated_po_nk/$1';

$route['promosi_seed']                                  = 'purchaseorder/C_Reqpic/promosiseed';
$route['list_barang_ready_seed']                        = 'purchaseorder/C_Reqpic/list_barang_ready_seed';
$route['addtmpreqbarang_seed']                          = 'purchaseorder/C_Reqpic/addtmpreqbarang_seed';
$route['add_promosi_seed/(:any)']                       = 'purchaseorder/C_Reqpic/add_promosi_seed/$1';


$route['promosi_cp']                                    = 'purchaseorder/C_Reqpic/promosicp';
$route['list_barang_ready_cp']                          = 'purchaseorder/C_Reqpic/list_barang_ready_cp';
$route['addtmpreqbarang_cp']                            = 'purchaseorder/C_Reqpic/addtmpreqbarang_cp';
$route['add_promosi_cp/(:any)']                         = 'purchaseorder/C_Reqpic/add_promosi_cp/$1';

//PurchaseOrderNonKomersil - STATUS
$route['postatusnk']                                    = 'postatus/C_PoStatus/postatusnk';
$route['arsip-evident-ponk']                            = 'postatus/C_PoStatus/arsip_evident_ponk';
$route['srcponkbytgl']                                  = 'postatus/C_PoStatus/srcponkbytgl';
$route['postatusallnk']                                 = 'postatus/C_PoStatus/postatusallnk';
$route['detailponk/(:any)']                             = 'postatus/C_PoStatus/detailponk/$1';
$route['postatusnk/supporting-file/(:num)']              = 'postatus/C_PoStatus/supporting_file/$1';
$route['postatusnk/purchase-proof/(:num)']               = 'postatus/C_PoStatus/purchase_proof/$1';
$route['postatusnk/master-barang-manual']               = 'postatus/C_PoStatus/simpan_master_barang_manual_pojasa';
$route['cancel_pengajuan_ponk']                         = 'postatus/C_PoStatus/cancel_pengajuan_ponk';
$route['postatusnk/update-tujuan-pembelian']            = 'postatus/C_PoStatus/update_tujuan_pembelian_ponk';
$route['update_tujuan_pembelian_ponk']                  = 'postatus/C_PoStatus/update_tujuan_pembelian_ponk';
$route['add_faktur_item_nk']                            = 'postatus/C_PoStatus/add_item_faktur_nk';
$route['listbarangnk/1/2/3/(:any)']                     = 'stock/C_Stocknonkomersil/list_stock_non_komersil_po';
$route['noteupdatenk']                                  = 'postatus/C_PoStatus/addnotenk';
$route['noteupdatenk_pembelian']                        = 'postatus/C_PoStatus/notepembelian';
$route['konfirm_penerimaan']                            = 'postatus/C_PoStatus/konfirm_penerimaan';
$route['edit_harganyata']                               = 'postatus/C_PoStatus/edit_harganyata';
$route['approve_harganyata/(:num)']                     = 'postatus/C_PoStatus/approve_harganyata/$1';
$route['reject_harganyata/(:num)']                      = 'postatus/C_PoStatus/reject_harganyata/$1';
$route['hrgnyataoff/(:any)']                            = 'postatus/C_PoStatus/hrgnyataoff/$1';
$route['hrgnyataon/(:any)']                             = 'postatus/C_PoStatus/hrgnyataon/$1';
$route['add_tax_nk']                                    = 'postatus/C_PoStatus/add_tax_fk_nk';
$route['add_diskon_nk']                                 = 'postatus/C_PoStatus/add_diskon_nk';
$route['editedponk']                                    = 'postatus/C_PoStatus/edited_fk_nk';
$route['add_note_pembelian_nk']                         = 'postatus/C_PoStatus/add_note_pembelian_nk';
$route['edit_note_pembelian_nk']                        = 'postatus/C_PoStatus/edit_note_pembelian_nk';
$route['hapus_note_pembelian_nk']                       = 'postatus/C_PoStatus/hapus_note_pembelian_nk';
$route['gbruploadpic']                                  = 'postatus/C_PoStatus/gbruploadpic';
$route['reuploadgbrflpndukung']                         = 'postatus/C_PoStatus/reuploadgbrflpndukung';
$route['edit_gbr_pndukung']                             = 'postatus/C_PoStatus/edit_gbr_pndukung';
$route['delete_gbr_pendukung']                          = 'postatus/C_PoStatus/delete_gbr_pendukung';
$route['upbuktipembelian']                              = 'postatus/C_PoStatus/upbuktipembelian';
$route['addnopo']                                       = 'postatus/C_PoStatus/addnopo';
$route['srcponkbytgl']                                  = 'postatus/C_PoStatus/srcponkbytgl';
$route['historidone/(:any)/(:any)']                     = 'postatus/C_PoStatus/historidone/$1/$2';
$route['stsviewpo/(:any)']                              = 'postatus/C_PoStatus/stsviewpo/$1';
$route['srcexpdone']                                    = 'postatus/C_PoStatus/srcexpdone';
$route['downloadfile/(:any)']                           = 'postatus/C_PoStatus/downloadfile/$1';


$route['edit_faktur_item_nk']                           = 'postatus/C_PoStatus/edit_faktur_item_nk';
$route['hapus_faktur_item_nk']                          = 'postatus/C_PoStatus/hapus_faktur_item_nk';
$route['addnotebarangsupliertmp']                       = 'purchaseorder/C_Order/addnotebarangsupliertmp';
$route['edit_note_tmp_barang']                          = 'purchaseorder/C_Order/edit_note_tmp_barang';
$route['hapus_note_tmp_barang']                         = 'purchaseorder/C_Order/hapus_note_tmp_barang';
$route['add_diskon_po']                                 = 'purchaseorder/C_Order/add_diskon_po';
$route['edit_diskon_po']                                = 'purchaseorder/C_Order/edit_diskon_po';
$route['hapus_diskon_po']                               = 'purchaseorder/C_Order/hapus_diskon_po';
$route['add_diskon_merk_tmp']                           = 'purchaseorder/C_Order/add_diskon_merk_tmp';
$route['add_diskon_barang_tmp']                         = 'purchaseorder/C_Order/add_diskon_barang_tmp';
$route['add_diskon_barangs_tmp']                        = 'purchaseorder/C_Order/add_diskon_barangs_tmp';
$route['unpostponk/(:any)']                             = 'postatus/C_PoStatus/unpostponk/$1';
$route['hapusponk/(:any)']                              = 'postatus/C_PoStatus/hapusponk/$1';

//postatus
$route['postatus']                                      = 'postatus/C_PoStatus/postatus';

$route['postatus/today']                                = 'postatus/C_PoStatus';
$route['postatusall']                                   = 'postatus/C_PoStatus/postatus';
$route['postatus/postatusall/done']                     = 'postatus/C_PoStatus/getDone';
$route['postatus/postatusall/onprogress']               = 'postatus/C_PoStatus/getOnProgress';
$route['postatus/postatusall/reject']                   = 'postatus/C_PoStatus/getReject';
$route['postatus/onprogress']                           = 'postatus/C_PoStatus/getOnProgres';
$route['postatus/done']                                 = 'postatus/C_PoStatus/getDone';
$route['postatus/reject']                               = 'postatus/C_PoStatus/getReject';
$route['postatus/today/done']                           = 'postatus/C_PoStatus/getDoneToday';
$route['postatus/today/onprogress']                     = 'postatus/C_PoStatus/getOnProgresToday';
$route['postatus/today/reject']                         = 'postatus/C_PoStatus/getRejectToday';
$route['konfirmasiOrderNK/(:any)/(:any)']               = 'postatus/C_PoStatus/konfirmasiOrderNK/$1/$2';
$route['konfirmasiOrderdirNK/(:any)/(:any)']            = 'postatus/C_PoStatus/konfirmasiOrderdirNK/$1/$2';

$route['tolakordernk/(:any)/(:any)']                    = 'postatus/C_PoStatus/tolakordernk/$1/$2';
$route['pendingordernk']                                = 'postatus/C_PoStatus/pendingordernk';
$route['porevisi']                                      = 'postatus/C_PoStatus/porevisi';

$route['uploadfileponk']                                = 'postatus/C_PoStatus/uploadfileponk';
$route['repostponk/(:any)']                             = 'postatus/C_PoStatus/repostponk/$1';

$route['detailPO/(:any)']                               = 'postatus/C_PoStatus/detailPO/$1';
$route['update_printout_po']                            = 'postatus/C_PoStatus/update_printout_po';

$route['onhandpo/(:any)']                               = 'postatus/C_PoStatus/onhandpo/$1';
$route['onhandpo_ajax']                                 = 'postatus/C_PoStatus/onhandpo_ajax';
$route['print_po/(:any)/(:any)']                        = 'postatus/C_PoStatus/print_po/$1/$2';
$route['print_po/(:any)']                               = 'postatus/C_PoStatus/print_po/$1';
$route['print_po_supplier/(:any)/(:any)']               = 'postatus/C_PoStatus/print_po_supplier/$1/$2';
$route['print_po_supplier/(:any)']                      = 'postatus/C_PoStatus/print_po_supplier/$1';
$route['printOrder/(:any)']                             = 'postatus/C_PoStatus/printOrder/$1';
$route['printOrdernk/(:any)']                           = 'postatus/C_PoStatus/printOrdernk/$1';
$route['konfirmasiOrder/(:any)/(:any)']                 = 'postatus/C_PoStatus/konfirmasiOrder/$1';
$route['konfirmasiOrder/(:any)']                        = 'postatus/C_PoStatus/konfirmasiOrder/$1';
$route['poconfirmacc/(:any)']                           = 'postatus/C_PoStatus/poconfirmacc/$1';
$route['tolakOrder/(:any)/(:any)']                      = 'postatus/C_PoStatus/tolakOrder/$1';
$route['tolakOrder/(:any)']                             = 'postatus/C_PoStatus/tolakOrder/$1';
$route['cancel_po']                                     = 'postatus/C_PoStatus/cancel_po';
$route['NoteDirektur']                                  = 'postatus/C_PoStatus/addNote';
$route['addBarangRevisi/(:any)/(:any)']                 = 'postatus/C_PoStatus/listBarangRevisi/$1/$2';
$route['tambahBarangRevisi']                            = 'postatus/C_PoStatus/tambahBarangRevisi';
$route['revisiPO']                                      = 'postatus/C_PoStatus/revisiPO';
$route['hapusBarangPO/(:any)/(:any)']                   = 'postatus/C_PoStatus/hapusBarangPO/$1/$2';
$route['note_barang_suplier']                           = 'postatus/C_PoStatus/note_barang_suplier';
$route['note_barang_suplier_edit']                      = 'postatus/C_PoStatus/note_barang_suplier_edit';
$route['note_barang_suplier_hapus']                     = 'postatus/C_PoStatus/note_barang_suplier_hapus';
$route['searchPOdate']                                  = 'postatus/C_PoStatus/searchdatepo';
$route['repost_po']                                     = 'postatus/C_PoStatus/repostpo';
$route['edit_no_po']                                    = 'postatus/C_PoStatus/edit_no_po';
$route['unpostpo/(:any)']                               = 'postatus/C_PoStatus/unpostpo/$1';
$route['hapuspo/(:any)']                                = 'postatus/C_PoStatus/hapuspo/$1';
$route['insert_note_setting']                           = 'postatus/C_PoStatus/insert_note_setting';
$route['NoteUpdateKeuangan']                            = 'postatus/C_PoStatus/addNote';
$route['ntupdateporevisi']                              = 'postatus/C_PoStatus/porepost';
$route['tambahTax']                                     = 'postatus/C_PoStatus/tambahTax';
$route['tempoPembayaran']                               = 'postatus/C_PoStatus/tempoPembayaran';
$route['frankoPengiriman']                              = 'postatus/C_PoStatus/frankoPengiriman';
$route['addDiskon']                                     = 'postatus/C_PoStatus/tambahDiskon';
$route['diskonEdit']                                    = 'postatus/C_PoStatus/editDiskon';
$route['hapusDiskon/(:any)/(:any)']                     = 'postatus/C_PoStatus/hapusDiskon/$1/$2';
$route['hapusDiskonNk/(:any)/(:any)']                   = 'postatus/C_PoStatus/hapusDiskonNK/$1/$2';
$route['add_diskon_merk']                               = 'postatus/C_PoStatus/add_diskon_merk';
$route['add_diskon_barang']                             = 'postatus/C_PoStatus/add_diskon_barang';
$route['add_diskon_barangs']                            = 'postatus/C_PoStatus/add_diskon_barangs';
$route['shipment_to']                                   = 'postatus/C_PoStatus/shipment_to';

//settings-tax
$route['taxseting']                                     = 'settings/C_TaxSetting';
$route['addSatuanPajak']                                = 'settings/C_TaxSetting/addPajak';
$route['editSatuanPajak']                               = 'settings/C_TaxSetting/editTax';
$route['hapusPajak/(:num)']                             = 'settings/C_TaxSetting/hapusPajak/$1';

//setting-satuan
$route['satuansetting']                                 = 'settings/C_SatuanSetting';
$route['addSatuanBarang']                               = 'settings/C_SatuanSetting/addSatuanBarang';
$route['editSatuanBarang']                              = 'settings/C_SatuanSetting/editSatuan';
$route['hapusSatuan/(:num)']                            = 'settings/C_SatuanSetting/hapusSatuan/$1';

//setting-template
$route['notetemplate']                                  = 'settings/C_NoteSetting';
$route['addnotetemplate']                               = 'settings/C_NoteSetting/add_note_template';
$route['notetemplate/(:any)']                           = 'settings/C_NoteSetting/detail_note_template/$1';
$route['updateisinote']                                 = 'settings/C_NoteSetting/update_note_template';

//setting-formula-po
$route['formulapo']                                     = 'settings/C_Formulapo';

//LAPORAN PEMBELIAN NON KOMERSIL
$route['lap_nonkomersil']                               = 'laporan/C_Laporan';
$route['srclapbeli']                                    = 'laporan/C_Laporan/srclapbeli';
$route['export_laporan_pembelian_nk']                   = 'laporan/C_Laporan/export_laporan_pembelian_nk';
$route['exported_allstock']                             = 'laporan/C_Laporan/exported_allstock';

//LAPORAN TRANSAKSI NON KOMERSIL
$route['tr_allstock']                                   = 'laporan/C_Laporan/tr_allstock';
$route['get_allstock_ajax']                             = 'laporan/C_Laporan/get_allstock_ajax';
$route['exported_tr_allnk']                             = 'laporan/C_Laporan/exported_tr_allnk';

//reviewapps
$route['reviewapps']                                    = 'settings/C_Appsrated';
$route['addnewmodule']                                  = 'settings/C_Appsrated/addnewmodule';
$route['detailreview/(:any)']                           = 'settings/C_Appsrated/modulereview/$1';
$route['addqbaru']                                      = 'settings/C_Appsrated/addqbaru';
$route['reviewanswer']                                  = 'settings/C_Appsrated/reviewanswer';
$route['questionreviewpic/(:any)']                      = 'settings/C_Appsrated/questionreviewpic/$1';
$route['addconfirmsos']                                 = 'settings/C_Appsrated/addconfirmsos';

//API
$route['get_po']                                        = 'api/C_Api/get_po';
$route['get_data_pre_po_erp']                           = 'api/C_Api/get_data_pre_po_erp';
$route['get_barang']                                    = 'api/C_Api/get_barang';
$route['get_data_barang']                               = 'api/C_Api/get_data_barang';
$route['api/barang']                                    = 'api/C_Api/get_data_barang';


$route['stockkomersil']                                 = 'stock/C_Stockkomersil/api_stock_komersil';
$route['api/stockkomersil']                             = 'stock/C_Stockkomersil/api_stock_komersil';

// Master vendor jasa: Purchasing dan Admin.
$route['pojasa/ajax/purchasing/vendor/list']['get'] = 'purchaseorder/C_PojasaPurchasing/vendor_list';
$route['pojasa/ajax/purchasing/vendor/save']['post'] = 'purchaseorder/C_PojasaPurchasing/vendor_save';
$route['pojasa/ajax/purchasing/vendor/delete']['post'] = 'purchaseorder/C_PojasaPurchasing/vendor_delete';
