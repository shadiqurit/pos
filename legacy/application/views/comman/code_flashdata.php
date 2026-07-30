<div class="col-md-12">
      <!-- ********** ALERT MESSAGE START******* -->
          <?php if(demo_app()){ ?>
            <div class="alert alert-info text-left">
                 <a href="javascript:void()" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>
<p>PicoPos inventory software now includes many more advanced integrated features than before.</p><p>For details, call: 01728838644 &nbsp;|&nbsp; Website: <a href="https://shadiqur.com" target="_blank" rel="noopener">shadiqur.com</a></p><p></p>
                </strong>
              </div>
          <?php } ?>

          <?php if(!get_current_subcription_id() && !is_admin()){ ?>
            <div class="alert alert-success  text-left">
                 <a href="javascript:void()" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>
                  <?= $this->lang->line('subscription_msg_1'); ?> Please click <a href='<?=base_url('subscription')?>'>here</a> to Activate!
                </strong>
              </div>
          <?php } ?>
          <?php if(!is_admin() && store_module() && !empty(get_current_subcription_id())){ 
            //validate subscription
            $message = '';
            $subscription_id = get_current_subcription_id();
            if(empty($subscription_id)){
              $message = "This store don't have any subscrtions!!";
            }

            $expire_date = get_subscription_rec($subscription_id)->expire_date;
            if($expire_date<date('Y-m-d')){
              $message = "Store Subscription expired!!";
            }

            if(!empty($message)){ ?>
              <div class="alert alert-success  text-left">
                 <a href="javascript:void()" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>
                  <?=$message?>, Click <a href='<?=base_url('subscription')?>'>here</a> to Activate!
                </strong>
              </div>
            <?php }
           } ?>

          <?php
            if($this->session->flashdata('success')!=''):
              ?>
                <div class="alert alert-success alert-dismissable text-center">
                 <a href="javascript:void()" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong><?= $this->session->flashdata('success') ?></strong>
              </div> 
               <?php 
            endif;
            if($this->session->flashdata('error')!=''):
              ?>
                <div class="alert alert-danger alert-dismissable text-center">
                 <a href="javascript:void()" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong><?= $this->session->flashdata('error') ?></strong>
              </div> 
               <?php
            endif;
            if($this->session->flashdata('warning')!=''):
              ?>
                <div class="alert alert-warning alert-dismissable text-center">
                 <a href="javascript:void()" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong><?= $this->session->flashdata('warning') ?></strong>
              </div> 
               <?php
            endif;
            ?>
            <!-- ********** ALERT MESSAGE END******* -->
     </div>
