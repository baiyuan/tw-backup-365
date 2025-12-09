jQuery(document).ready(function($) {
	// 詳情展開
	$('.toggle-details').on('click', function() {
		var target = '#' + $(this).data('target');
		$(target).toggle();
	});

	// 複製路徑
	$('.copy-btn').on('click', function() {
		var $temp = $("<input>");
		$("body").append($temp);
		$temp.val($('#storage-path').text()).select();
		document.execCommand("copy");
		$temp.remove();
		var originalText = $(this).html();
		var $btn = $(this);
		$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
		setTimeout(function() { $btn.html(originalText); }, 2000);
	});

	// 自動倒數與解鎖
	var $cooldownBtn = $('#tw-cooldown-btn');
	if ($cooldownBtn.length) {
		var remaining = parseInt($cooldownBtn.data('remaining'));
		var waitText = $cooldownBtn.data('wait-text');
		var readyText = $cooldownBtn.data('ready-text');

		var interval = setInterval(function() {
			remaining--;
			
			if (remaining <= 0) {
				clearInterval(interval);
				$cooldownBtn.prop('disabled', false)
							.removeClass('button-secondary')
							.addClass('button-primary')
							.val(readyText);
			} else {
				var minutes = Math.floor(remaining / 60);
				var seconds = remaining % 60;
				var formatted = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
				$cooldownBtn.val(waitText + ' ' + formatted);
			}
		}, 1000);
	}
});