// host.php
$(function() {

	$('#snver_info').click(function(event) {

		event.preventDefault();

		$.get(urlPath+'plugins/snver/snver.php?host_id='+$('#snver_info').data('snver_id'))
			.done(function(data) {
			$('#ping_results').html(data);
			hostInfoHeight = $('.hostInfoHeader').height();
		})
		.fail(function(data) {
			getPresentHTTPError(data);
		});
	})
});


//snver_tab.php
function applyFilter() {
                strURL  = 'snver_tab.php' +
                        '?host_id=' + $('#host_id').val() +
                        '&header=false';
                loadPageNoHeader(strURL);
        }

function clearFilter() {
	strURL = 'snver_tab.php?clear=1&header=false';
	loadPageNoHeader(strURL);
}

$(function() {
	$('#clear').unbind().on('click', function() {
		clearFilter();
	});

	$('#filter').unbind().on('change', function() {
		applyFilter();
	});

	$('#form_snver').unbind().on('submit', function(event) {
		event.preventDefault();
		applyFilter();
	});
});




