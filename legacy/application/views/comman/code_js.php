<script>
  var base_url = '<?=base_url()?>';
</script>
<!-- Bootstrap 3.3.6 -->
<script src="<?php echo $theme_link; ?>bootstrap/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/js/jquery.dataTables.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/FixedHeader-3.1.4/js/dataTables.fixedHeader.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Responsive-2.2.2/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Responsive-2.2.2/js/responsive.bootstrap.min.js"></script>
<!-- end -->
<!--  FOR EXPORT BUTTONS START -->
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/JSZip-2.5.0/jszip.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/pdfmake-0.1.36/pdfmake.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/pdfmake-0.1.36/vfs_fonts.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Buttons-1.5.4/js/dataTables.buttons.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Buttons-1.5.4/js/buttons.flash.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Buttons-1.5.4/js/buttons.html5.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Buttons-1.5.4/js/buttons.print.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Buttons-1.5.4/js/buttons.colVis.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/DataTables-1.10.18/extensions/Buttons-1.5.4/js/buttons.bootstrap.min.js"></script>
<!--  FOR EXPORT BUTTONS END -->

<!-- SlimScroll -->
<script src="<?php echo $theme_link; ?>plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="<?php echo $theme_link; ?>plugins/fastclick/fastclick.js"></script>
<!-- Shortcut Keys -->
<script src="<?php echo $theme_link; ?>plugins/shortcuts/shortcuts.js"></script>
<!-- Select2 -->
<script src="<?php echo $theme_link; ?>plugins/select2/select2.full.min.js"></script>
<!-- AdminLTE App -->
<script>
  var AdminLTEOptions = {
    /*https://adminlte.io/themes/AdminLTE/documentation/index.html*/
    sidebarExpandOnHover: true,
    navbarMenuHeight: "200px", //The height of the inner menu
    animationSpeed: 250,
  };
</script>
<script src="<?php echo $theme_link; ?>dist/js/app.js"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="<?php echo $theme_link; ?>dist/js/demo.js"></script> -->
<!-- page script -->
<!--Toastr notification -->
<script src="<?php echo $theme_link; ?>toastr/toastr.js"></script>
<script src="<?php echo $theme_link; ?>toastr/toastr_custom.js"></script>
<!--Toastr notification end-->
<!-- bootstrap datepicker -->
<script src="<?php echo $theme_link; ?>plugins/daterangepicker/moment.min.js"></script>
<script src="<?php echo $theme_link; ?>plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap datepicker -->
<script src="<?php echo $theme_link; ?>plugins/datepicker/bootstrap-datepicker.js"></script>
<!-- Autocomplete -->      
<script src="<?php echo $theme_link; ?>plugins/autocomplete/autocomplete.js"></script>
<!-- Custom JS -->
<script src="<?php echo $theme_link; ?>js/special_char_check.js"></script>
<script src="<?php echo $theme_link; ?>js/custom.js"></script>

<!-- Keep table action menus visible outside responsive/scroll containers -->
<script type="text/javascript">
(function ($) {
  'use strict';

  var activeTableActionMenu = null;

  function positionTableActionMenu() {
    if (!activeTableActionMenu || !activeTableActionMenu.toggle.length) {
      return;
    }

    var toggleElement = activeTableActionMenu.toggle[0];
    if (!document.documentElement.contains(toggleElement)) {
      restoreTableActionMenu();
      return;
    }

    var toggleRect = toggleElement.getBoundingClientRect();
    var menu = activeTableActionMenu.menu;
    var viewportPadding = 8;
    var menuWidth = Math.max(menu.outerWidth(), 160);
    var menuHeight = menu.outerHeight();
    var left = toggleRect.right - menuWidth;
    var top = toggleRect.bottom + 6;

    left = Math.max(viewportPadding, Math.min(left, window.innerWidth - menuWidth - viewportPadding));

    if (top + menuHeight > window.innerHeight - viewportPadding &&
        toggleRect.top - menuHeight - 6 >= viewportPadding) {
      top = toggleRect.top - menuHeight - 6;
    }

    menu.css({
      bottom: 'auto',
      display: 'block',
      left: left + 'px',
      position: 'fixed',
      right: 'auto',
      top: top + 'px',
      zIndex: 2050
    });
  }

  function restoreTableActionMenu() {
    if (!activeTableActionMenu) {
      return;
    }

    var menuState = activeTableActionMenu;
    activeTableActionMenu = null;

    menuState.menu
      .removeClass('table-action-menu-portal')
      .appendTo(menuState.group);

    if (typeof menuState.originalStyle === 'undefined') {
      menuState.menu.removeAttr('style');
    } else {
      menuState.menu.attr('style', menuState.originalStyle);
    }

    menuState.group.removeData('picoposTableActionMenu');
  }

  $(document).on(
    'show.bs.dropdown',
    '.dataTables_wrapper .btn-group, .table-responsive .btn-group',
    function () {
      var group = $(this);
      var menu = group.children('.dropdown-menu').first();
      var toggle = group.children('[data-toggle="dropdown"]').first();

      if (!menu.length || !toggle.length) {
        return;
      }

      restoreTableActionMenu();

      activeTableActionMenu = {
        group: group,
        menu: menu,
        originalStyle: menu.attr('style'),
        toggle: toggle
      };

      group.data('picoposTableActionMenu', menu);
      menu.detach().appendTo(document.body).addClass('table-action-menu-portal');
      positionTableActionMenu();
    }
  );

  $(document).on(
    'hide.bs.dropdown',
    '.dataTables_wrapper .btn-group, .table-responsive .btn-group',
    function () {
      if (activeTableActionMenu && activeTableActionMenu.group[0] === this) {
        restoreTableActionMenu();
      }
    }
  );

  window.addEventListener('resize', positionTableActionMenu);
  window.addEventListener('scroll', positionTableActionMenu, true);
})(jQuery);
</script>

<!-- Pace Loader -->
<script src="<?php echo $theme_link; ?>plugins/pace/pace.min.js"></script>
<script type="text/javascript">
$(document).ajaxStart(function() { Pace.restart(); }); 
</script>  
<!-- Sweet alert -->
<script src="<?php echo $theme_link; ?>js/sweetalert.min.js"></script>


<!-- iCheck -->
<script src="<?php echo $theme_link; ?>plugins/iCheck/icheck.min.js"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-orange',
      /*uncheckedClass: 'bg-white',*/
      radioClass: 'iradio_square-orange',
      increaseArea: '10%' // optional
    });
  });
</script>
<!-- Initialize Select2 Elements -->
<script type="text/javascript"> $(".select2").select2(); </script>
<!-- Initialize toggler -->
<script type="text/javascript">
  $(document).ready(function(){
      $('[data-toggle="popover"]').popover();   
  });
</script>
<!-- Initialize date with its Format -->
<script type="text/javascript">
  //Date picker
    $('.datepicker').datepicker({
      autoclose: true,
    format: '<?php echo $VIEW_DATE;?>',
     todayHighlight: true
    });
</script>
<script>
  $(function () {
    //Date range as a button
    $('#daterange-btn').daterangepicker(
      {
        ranges   : {
          'Today'       : [moment(), moment()],
          'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month'  : [moment().startOf('month'), moment().endOf('month')],
          'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate  : moment()
      },
      function (start, end) {
        $('#daterange-btn span').html(start.format('<?php echo strtoupper($VIEW_DATE) ;?>') + ' - ' + end.format('<?php echo strtoupper($VIEW_DATE);?>'))
      }
    );


  });

    function get_start_date(){
        return $('#daterange-btn').data('daterangepicker').startDate.format('<?php echo strtoupper($VIEW_DATE) ;?>');
    }
    function get_end_date(){
        return $('#daterange-btn').data('daterangepicker').endDate.format('<?php echo strtoupper($VIEW_DATE) ;?>');
    }
</script>
<script type="text/javascript" >
$(function($) { // this script needs to be loaded on every page where an ajax POST may happen
  //var csrf = $('input[name="csrf_token"]').val();  // <- get token value from hidden form input
    $.ajaxSetup({ data: {'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>' }  }); });
</script>
<script type="text/javascript">
	function show_delete_btn() {
  var group_check_count = $(".group_check").prop("checked") ? 1: 0;
  var check_count = $('#example2').find('input[type=checkbox]:checked').length-parseInt(group_check_count);

  //console.log($('#example2 > tbody').find('.checkbox').length);
  if(parseInt(check_count)>0){
    $(".delete_btn").removeClass('hidden').show();
  }    
  else{
    $(".delete_btn").addClass('hidden').hide();
  }

  if($('#example2 > tbody').find('.checkbox').length == check_count){
    $(".group_check").prop("checked",true).iCheck('update');
  }
  else{
    $(".group_check").prop("checked",false).iCheck('update');
  }

}
$('.group_check').on('ifChanged', function(event) {
    if(event.target.checked){
      $(".column_checkbox").prop("checked",true).iCheck('update');
    }
    else{
      $(".column_checkbox").prop("checked",false).iCheck('update');
    }
    //$(".undelete").prop("checked",false).iCheck('update');
    show_delete_btn();
});


function call_code(){
  $('.column_checkbox').on('ifChanged', function(event) {
      show_delete_btn();
  });
}
</script>
<script type="text/javascript">
$(document).ready(function () { setTimeout(function() {$( ".alert-dismissable" ).fadeOut( 1000, function() {});}, 10000); });
</script>
<script type="text/javascript">
  function round_off(input=0){
    <?php if(is_enabled_round_off()){ ?>
      return to_Fixed(Math.round(input));
    <?php }else{?>
      return to_Fixed(input);
    <?php }?>
  }
</script>

<script type="text/javascript">
  function to_Fixed(res=0){
      var decimals = <?=decimals()?>;
        return (isNaN(parseFloat(res))) ? parseFloat(0).toFixed(decimals) : parseFloat(res).toFixed(decimals); 
      }
</script>
<script type="text/javascript">
  function format_qty(res=0){
      var decimals = <?=qty_decimal()?>;
        return (isNaN(parseFloat(res))) ? parseFloat(0).toFixed(decimals) : parseFloat(res).toFixed(decimals); 
      }
</script>
<script type="text/javascript">
  $("#item_search").on("focusout", function(){
  $("#item_search").val('').removeClass('ui-autocomplete-loading');
});
</script>
