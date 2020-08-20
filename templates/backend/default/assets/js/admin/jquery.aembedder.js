$(document).ready(function(){
		
	//Ajax:

    $("body").on('click', "a[id*='run_']", function(event) {
        event.preventDefault();	
		var id = $(this).attr('id');
		var split = id.split('_');
		var cron = split[1];
		var failed = $('#ae_run').text();
		if (cron == 0) {
			$('#ae_run').text('Never');
		} else if (cron == 1){
			$('#ae_run').text('Every Hour'); 			
		} else if (cron == 3){
			$('#ae_run').text('Every 3 Hours'); 
		} else if (cron == 6){
			$('#ae_run').text('Every 6 Hours');
		} else if (cron == 12){
			$('#ae_run').text('Every 12 Hours');
		} else if (cron == 24){
			$('#ae_run').text('Every Day');
		} else if (cron == 168){
			$('#ae_run').text('Every Week');
		}
		$.post(base_url + '/ajax/admin_aembedder_cron', { cron: cron },
			function (response) {
				if (response.status) {
					Messenger().post({
						message: 'AE Cron Successfully Updated',
						type: 'success'
					});
				} else {
					Messenger().post({
						message: 'AE Cron Update Failed!',
						type: 'error'
					});
					$('#ae_run').text(failed);
				}
		}, "json"); 
	});	

    $("body").on('click', "a[id*='delete_source_']", function(event) {
        event.preventDefault();	
		var id = $(this).attr('id');
		var split = id.split('_');
		var source_id = split[2];
		$('#delete__source_' + source_id).html('<i class="small-loader"></i>');
		$('#' + id).html('<i class="small-loader"></i>');
		$.post(base_url + '/ajax/admin_delete_source', { source_id: source_id },
			function (response) {
				if (response.status) {
					Messenger().post({
						message: 'AE Source <b>ID ' + source_id + '</b>: Successfully deleted!',
						type: 'success'
					});
					$('#item-' + source_id).fadeOut();
				} else {
					Messenger().post({
						message: 'AE Source <b>ID ' + source_id + '</b>: Delete failed!',
						type: 'error'
					});					
				}
		}, "json"); 
	});

    $("body").on('click', "a[id*='status_source_']", function(event) {
        event.preventDefault();
		var processing = $(this).attr('data-processing');
		if (processing == 0) {
			$(this).attr('data-processing', 1);	
			var source_status = $(this).attr('data-status');
			var id = $(this).attr('id');
			var split = id.split('_');
			var source_id = split[2];
			$('#' + id).html('<i class="small-loader"></i>');
			$.post(base_url + '/ajax/admin_status_source', { source_id: source_id, source_status: source_status},
				function (response) {
					if (response.status) {
						if (source_status == 0) {
							Messenger().post({
								message: 'source <b>ID ' + source_id + '</b>: Successfully activated!',
								type: 'success'
							});						
							$('#status_source_' + source_id).attr('data-status', 1);							
							$('#status_source_' + source_id).attr('alt', 'Suspend');
							$('#status_source_' + source_id).attr('title', 'Suspend');
							$('#status_source_' + source_id).html('<i class="fa fa-times"></i>');
							$('#status-' + source_id).html('<span class="text-green" alt="Active" title="Active">Active</span>');							
						} else {
							Messenger().post({
								message: 'source <b>ID ' + source_id + '</b>: Successfully suspended!',
								type: 'success'
							});
							$('#status_source_' + source_id).attr('data-status', 0);
							$('#status_source_' + source_id).attr('alt', 'Activate');
							$('#status_source_' + source_id).attr('title', 'Activate');
							$('#status_source_' + source_id).html('<i class="fa fa-check"></i>');
							$('#status-' + source_id).html('<span class="text-red" alt="Inactive" title="Inactive">Inactive</span>');
						}

					} else {
						if (source_status == 0) {
							Messenger().post({
								message: 'source <b>ID ' + source_id + '</b>: Failed activating or already active!',
								type: 'error'
							});
							$('#status_source_' + source_id).html('<i class="fa fa-check"></i>');							
						} else {
							Messenger().post({
								message: 'source <b>ID ' + source_id + '</b>: Failed suspending or already inactive!',
								type: 'error'
							});
							$('#status_source_' + source_id).html('<i class="fa fa-times"></i>');							
						}
					}
					$('#status_source_' + source_id).attr('data-processing', 0);
			}, "json"); 			
		}
	});	
	
});