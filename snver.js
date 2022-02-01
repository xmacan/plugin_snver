
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
