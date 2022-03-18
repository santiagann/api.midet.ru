$('#datafrom').datepicker({
    language: 'ru'
});
$('#datato').datepicker({
    language: 'ru'
});
let startOfMonth = moment().startOf('month').format('DD.MM.YYYY');
let endOfMonth = moment().endOf('month').format('DD.MM.YYYY');
setFrom(startOfMonth);
setTo(endOfMonth);


$('#datafrom').on('changeDate', function () {
    if ($('#datafrom').datepicker('getFormattedDate') != $('#datafrom_input').val()) {
        $('#datato').datepicker('setStartDate', $('#datafrom').datepicker('getFormattedDate'));
        $('#datato').datepicker('setDate', $('#datato_input').val());
    }
    $('#datafrom_input').val(
        $('#datafrom').datepicker('getFormattedDate')
    );
    $('#txt_from').html($('#datafrom').datepicker('getFormattedDate'));
});
$('#datato').on('changeDate', function () {
    if ($('#datato').datepicker('getFormattedDate') != $('#datato_input').val()) {
        $('#datafrom').datepicker('setEndDate', $('#datato').datepicker('getFormattedDate'));
        $('#datafrom').datepicker('setDate', $('#datafrom_input').val());
    }
    $('#datato_input').val(
        $('#datato').datepicker('getFormattedDate')
    );
    $('#txt_to').html($('#datato').datepicker('getFormattedDate'));
});

function setFrom(dates) {
    $('#datafrom').datepicker('setDate', dates);
    $('#datafrom_input').val(dates);
    $('#txt_from').html(dates);
}

function setTo(dates) {
    $('#datato').datepicker('setDate', dates);
    $('#datato_input').val(dates);
    $('#txt_to').html(dates);
}

$("#requestStat").click(function () {
    if (!$("#requestStat").hasClass('disabled')) {
        $("#requestStat").addClass('disabled');
        buttonWidth=$('#requestStat').width();
        buttonHeight=$('#requestStat').height();
        buttonText=$('#requestStat').html();
        $('#requestStat').html('<i class="fa fa-spinner fa-spin"></i>');
        $('#requestStat').width(buttonWidth);
        $('#requestStat').height(buttonHeight);
    const data = {
        from: $('#datafrom_input').val(),
        to: $('#datato_input').val()
    };
    $.ajax({
        type: "POST",
        url: '/statistics/getdata',
        data: JSON.stringify(data),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (data) {
            if (data.status === 200) {
                $('#getfile').attr('href', '/' + data.data.file.fullfile);
                var link = $('#getfile')[0];
                var linkEvent = null;
                if (document.createEvent) {
                    linkEvent = document.createEvent('MouseEvents');
                    linkEvent.initEvent('click', true, true);
                    link.dispatchEvent(linkEvent);
                } else if (document.createEventObject) {
                    linkEvent = document.createEventObject();
                    link.fireEvent('onclick', linkEvent);
                }
                $('#requestStat').removeClass('disabled');
                $('#requestStat').html(buttonText);
                $('#requestStat').height('');
                $('#requestStat').width('');
            } else if (data.Status === 500) {

            }
        }
    });
}
})
