<div class="modal fade " id="customer-modal" tabindex='-1'>
                <?= form_open('#', array('class' => '', 'id' => 'customer-form')); ?>
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header header-custom">
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <label aria-hidden="true">&times;</label></button>
                      <h4 class="modal-title text-center"><?= $this->lang->line('add_customer'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                          <div class="col-md-12">
                            <div class="box-body">
                              <div class="form-group">
                                <label for="customer_name"><?= $this->lang->line('customer_name'); ?>*</label>
                                <label id="customer_name_msg" class="text-danger text-right pull-right"></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="" >
                              </div>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="box-body">
                              <div class="form-group">
                                <label for="mobile"><?= $this->lang->line('mobile'); ?></label>
                                <label id="mobile_msg" class="text-danger text-right pull-right"></label>
                                <input type="tel"  class="form-control no_special_char_no_space " id="mobile" name="mobile" placeholder="8801775457008"  >
                              </div>
                            </div>
                          </div>

						  						                            <div class="col-md-12">
                            <div class="box-body">
                              <div class="form-group">
                                <label for="address"><?= $this->lang->line('address'); ?></label>
                                <label id="address_msg" class="text-danger text-right pull-right"></label>
                                <textarea type="text" class="form-control" id="address" name="address" placeholder="" ></textarea>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="box-body">
                              <div class="form-group">
                                <label for="email"><?= $this->lang->line('email'); ?></label>
                                <label id="email_msg" class="text-danger text-right pull-right"></label>
                                <input type="email" class="form-control " id="email" name="email" placeholder=""  >
                              </div>
                            </div>
                          </div>
						                            <div class="col-md-6">
                            <div class="box-body">
                              <div class="form-group">
                                <label for="credit_limit"><?= $this->lang->line('credit_limit'); ?></label>
                                <label class="text-success text-right pull-right">-1 for No Limit</label>
                                <label id="credit_limit_msg" class="text-danger text-right pull-right"></label>
                                <input type="text"  class="form-control only_currency" id="credit_limit" name="credit_limit" value='-1' placeholder=""  >
                              </div>
                            </div>
                          </div>

                          <div class="col-md-6">
                            <div class="box-body">
                              <div class="form-group">
                                <label for="opening_balance"><?= $this->lang->line('previous_due'); ?></label>
                                <label id="opening_balance_msg" class="text-danger text-right pull-right"></label>
                                <input type="text"  class="form-control only_currency" id="opening_balance" name="opening_balance" placeholder=""  >
                              </div>
                            </div>
                          </div>
                          
                          </div><!-- row/ -->
						  
						  
						  
						  

                        
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-warning" data-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary add_customer">Save</button>
                    </div>
                  </div>
                  <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
               <?= form_close();?>
              </div>
              <!-- /.modal -->