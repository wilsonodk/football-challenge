$(document).ready(setNav);
$(document).ready(setWings);
$(window).resize(setWings);

function loadCreateChallenge() {
	// Form validation

}

function loadWeeklyChallenge() {
	// Add event to toggle checked highlight
	$(':radio').change(function(evt) {
		var target  = $(evt.target).parent();
		var type	= '.'+ target.attr('class').split(' ')[0];
		target.parent().children('td').removeClass('user-pick');
		$(type).addClass('user-pick');
	});
}

function setWings() {
	var wings = 1800;
	var width = $(window).width();
	var diff  = Math.round((width - wings)/2);
	$('#wings').css('left', diff +'px');
}

function setNav() {
	var root = "/" + (location.pathname.split('/')[1]) + "/",
		path = location.pathname;

	if (typeof useTab !== 'undefined') {
		$('li#week-' + useTab).addClass('selected');
	}
	else if (path.length === root.length) {
		$('li#home').addClass('selected');
	}
}

function onLastWeekResults(data, status, xhr) {
    var output = '<table class="results"><tr><td class="wins">{wins}</td><td class="losses">{losses}</td></tr></table>',
        users = data.users,
        id;

    for (var i = 0; i < users.length; i++) {
        id = '#last-week-' + users[i].username;
        $(id).html(output.replace('{wins}', users[i].wins).replace('{losses}', users[i].losses));
    }
}

function getLastWeeksResults() {
    $.getJSON('./last/week', onLastWeekResults);
}

function inspectObj(obj) {
	var out = '';
	for (var e in obj) {
		if (typeof obj[e] == 'function') {
			out += e +': function\n';
		}
		else {
			out += e +': '+ obj[e] +'\n';
		}
	}
	$('body').prepend('<div>' + out + '</div>');
}

function checkTime() {
	var diff = close - getNow();
	if (diff <= 0) {
		var date = new Date(close * 1000);
		var	time = months[date.getMonth()] +' '+ date.getDate();
		var text = 'This challenge is closed. <span class="time">Closed on {time}.</span>'.replace('{time}', time);
		$('#form-action').html(text);
	}
	else {
		$('#end-time').html(getEndTime(diff));
	}
}

function getEndTime(diff) {
	var min	= 60;
	var hrs	= min * 60;
	var day = hrs * 24;
	var str = '';
	var time = 0;
	var diffstr = " <span>({diff} seconds)</span>";
	if (diff >= day) {
		// More than a day
		time = Math.floor(diff / day);
		str  = "You've got some time, over {time} left.".replace('{time}', time +' day'+(time == 1 ? '':'s'));
		str += diffstr;
	}
	else if (diff >= hrs) {
		// More than 1 hour
		time = Math.floor(diff / hrs);
		str  = "Starting to run out of time, over {time} left.".replace('{time}', time +' hour'+(time == 1 ? '':'s'));
		str += diffstr;
	}
	else if (diff >= min) {
		// Less than an hour
		time = Math.round(diff / min);
		str  = "Hurry! About {time} left.".replace('{time}', time +' minute'+(time == 1 ? '':'s'));
		str += diffstr;
	}
	else {
		// Less than a minute
		time = diff;
		str  = "HURRY! ONLY {time} LEFT!".replace('{time}', time +' SECOND'+(time == 1 ? '':'S'));
	}
	// Format number of seconds
	diff = Number(diff);

	return str.replace("{diff}", diff.format());
}

function getNow() {
	return Math.floor((new Date()).getTime()/1000);
}

var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];


Number.prototype.format = function() {
	var me = this, temp, before, after, regex = /(\d+)(\d{3})/;
	// Cast to string, then split on dot
	temp	= String(me).split('.');
	before	= temp[0];
	after	= temp.length > 1 ? '.' + temp[1] : '';
	while (regex.test(before)) {
		before = before.replace(regex, "$1,$2");
	}
	return before + after;
};
