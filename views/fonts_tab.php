<div id="fonts-tab"></div>

<div id="lister" style="font-size: large; float: right;">
    <a href="/show/listing/fonts/fonts" title="List">
        <i class="btn btn-default tab-btn fa fa-list-alt"></i>
    </a>
</div>
<div id="report_btn" style="font-size: large; float: right;">
    <a href="/show/report/fonts/fonts" title="Report">
        <i class="btn btn-default tab-btn fa fa-bar-chart-o"></i>
    </a>
</div>
<h2><i class="fa fa-font" style="font-size:14px; border:0.1px solid #ccc; border-radius:4px; padding:5px 5px; vertical-align:text-top;"></i> <span data-i18n="fonts.clienttab"></span></h2>

<div id="fonts-msg" data-i18n="listing.loading" class="col-lg-12 text-center"></div>

<script>
$(document).on('appReady', function(){
   $.getJSON(appUrl + '/module/fonts/get_data/' + serialNumber, function(data){

        // Check if we have data
        if( data.length == 0 ){
            $('#fonts-msg').text(i18n.t('no_data'));
            $('#fonts-cnt').text('')
        } else {
            // Hide loading message
            $('#fonts-msg').text('');
            // Set count of fonts
            $('#fonts-cnt').text(data.length);
            var skipThese = ['id','serial_number','type_name'];
            $.each(data, function(i,d){
                var boolFields = ['enabled', 'copy_protected', 'duplicate', 'embeddable', 'type_enabled', 'outline', 'valid'];
                var $rows = $('<tbody>');
                var riskFields = ['duplicate', 'copy_protected'];

                // Generate rows from data
                for (var prop in d){
                    // Skip skipThese
                    if(skipThese.indexOf(prop) == -1){
                        if(boolFields.indexOf(prop) !== -1 && d[prop] == 1){
                           var yesClass = riskFields.indexOf(prop) !== -1 ? 'label-danger' : 'label-success';
                           var $yesValue = $('<span>').addClass('label ' + yesClass).text(i18n.t('yes'));
                           $rows.append(
                               $('<tr>')
                                   .append($('<th>').text(i18n.t('fonts.' + prop)))
                                   .append($('<td>').append($yesValue))
                           );
                        }
                        else if(boolFields.indexOf(prop) !== -1 && d[prop] == 0){
                           var noClass = riskFields.indexOf(prop) !== -1 ? 'label-success' : 'label-danger';
                           var $noValue = $('<span>').addClass('label ' + noClass).text(i18n.t('no'));
                           $rows.append(
                               $('<tr>')
                                   .append($('<th>').text(i18n.t('fonts.' + prop)))
                                   .append($('<td>').append($noValue))
                           );
                        }
                        else if(d[prop] == ""){
                           // Blank out empty rows
                        }
                        else {
                            $rows.append(
                                $('<tr>')
                                    .append($('<th>').text(i18n.t('fonts.' + prop)))
                                    .append($('<td>').text(d[prop]))
                            );
                        }
                    }
                }
                $('#fonts-tab')
                    .append($('<h4>')
                        .append($('<i>')
                            .addClass('fa fa-font'))
                        .append(document.createTextNode(' ' + (d.type_name || ''))))
                    .append($('<div style="max-width:650px;">')
                        .addClass('table-responsive')
                        .append($('<table>')
                            .addClass('table table-striped table-condensed')
                            .append($rows)))
            })
        }
   });
});
</script>
