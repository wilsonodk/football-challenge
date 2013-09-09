
(function(window, $){
	if (window.messenger) {
		alert("Issue with Messenger. It's already defined.");
	}
	
	var messenger = window.messenger = new Messenger();	
	
	function Messenger() {
		this.timer = 0;
		this.timeOut = 1000 * 60; // One minute
		this.msgrInputSize;
		
		this.messages = [];
	}
	Messenger.state = 0;
	Messenger.prototype.init = function() {
		var self = this;
		$('#content').append('\
			<div id="messenger">\
				<div id="messenger-input">\
						<textarea rows="2" name="message" disabled="disabled" id="messenger-textarea-main"></textarea><br />\
						<input type="button" value="Reply" id="messenger-input-main-reply" disabled="disabled" />\
				</div>\
				<div id="all-messages">\
					<div class="message" id="message-first">&nbsp;<br />No messages, yet. Why don\'t you add one?<br />&nbsp;</div>\
				</div>\
			</div>\
		');
		
		// Validate
		$('#messenger-input-main-reply').click(self.onClick);
		
		if ($.browser.mozilla) {
			$('#all-messages').css('height', 471);
		}
		
		this.msgrInputSize = parseInt($("#messenger-input").css("height")) - 7;
		this.getMessages();
		this.checkSize();
		
		this.timer = setInterval(function() {
			self.getMessages();
			self.checkSize();
		}, this.timeOut);
	};
	Messenger.prototype.checkSize = function() {
		var msgr = $('#messenger'),
			body = $('body');
			
		console.log(body);
	};
	Messenger.prototype.getMessages = function() {
		Messenger.state = 1;
		var root = location.pathname.split('/')[1];
		var path = '/' + root +'/messages';
		$.ajax({
			url: path,
			dataType: 'json',
			success: Messenger.onSuccess,
			error: Messenger.onError
		});
	};
	Messenger.onSuccess = function(data, textStatus, xhr) {
		var i, len = data.messages.length;
		if (len > 0) {
			// Hide first message if we have one
			$('#message-first').hide();
			
			if (Messenger.state) {
				$('#all-messages').html('');
			}
			
			for (i = 0; i < len; i++) {
				messenger.messages.push((new Message(data.messages[i])).render());
			}
			
			// Is user logged in?
			if ($('.account-area').text() !== 'Login') {
				$('#messenger .message .tools span').show();
			}
		}
		if ($('.account-area').text() !== 'Login') {
			$('#messenger-input-main-reply').attr('disabled', false);
			$('#messenger-textarea-main').attr('disabled', false);
		}
	};
	Messenger.onError = function(xhr, textStatus, errorThrown) {
		alert('There was an error!\n\n' + errorThrown);
	};
	Messenger.onNewMessageSuccess = function(data, textStatus, xhr) {
		if (data.success) {
			messenger.getMessages(); 
		}
		else {
			Messenger.onError({}, 'error', "Couldn't save message. " + data.pid);
		}
	};
	Messenger.prototype.onClick = function(evt) {
		var input	 = $(evt.target),
			id 		 = input.attr('id').split('-')[2],
			textarea = $('#messenger-textarea-' + id),
			message  = textarea.val(),
			payload	 = {},
			root = location.pathname.split('/')[1],
			path = '/' + root +'/message/new';

		if (message.length > 0) {			
			textarea.removeClass('form-error');
			
			payload.message = message;
			payload.id = id;
			
			// do it
			$.ajax({
				url: path,
				type: 'post',
				data: payload,
				dataType: 'json',
				success: Messenger.onNewMessageSuccess,
				error: Messenger.onError
			});
		}
		else {
			textarea.addClass('form-error').focus();
		}
	};

	/**
	 *
	 */
	function Message(json) {
		this.template = '\
			<div class="message" id="messenger-message-{msg-id}">{body}\
				<div class="tools">\
					By <strong>{user}</strong> on <em id="posted-{timestamp}">{posted}</em> <span id="messenger-reply-toggle-{msg-id}">&bull; <a class="reply" id="messenger-reply-{msg-id}" href="javascript:;">Reply</a></span>\
					<div class="input-area" id="messenger-reply-input-{msg-id}">\
						<textarea rows="2" id="messenger-textarea-{msg-id}" name="message"></textarea><br />\
						<input type="button" id="messenger-input-{msg-id}-reply" value="Reply" /> or <a class="cancel" id="messenger-reply-cancel-{msg-id}" href="javascript:;">Cancel</a>\
					</div>\
				</div>\
				<div class="replies"></div>\
			</div>';
	
		this.ele		= null;
		this.id			= json.mid;
		this.pid		= json.pid !== "0" && json.pid ? json.pid : 0;
		this.body		= Message.htmlize(json.message);
		this.user		= json.username;
		this.uid		= json.uid;
		this.posted		= this.getDate(json.posted);
		this.timestamp	= json.posted;
		this.replies	= [];
	}
	Message.htmlize = function(input) {
		var output = input, 
			matchArr = [
				/(\n|\r)/g,
				/(big blue|bulldog|comish|commish|m'neer|mouth|nd-man|p-dawg|title ix|wiseass)/gi
			],
			replaceArr = [
				"<br>",
				"<a href=\"./picks/$1\">$1</a>"
				
			], 
			items = matchArr.length;
			
		while (items--) {
			output = output.replace(matchArr[items], replaceArr[items]);
		}

		return output;
	};
	Message.prototype.render = function() {
		var msg = this.template
			.replace(/{msg-id}/g,	this.id)
			.replace(/{body}/,		this.body)
			.replace(/{user}/,		this.user)
			.replace(/{posted}/,	this.posted)
			.replace(/{timestamp}/,	this.timestamp)
		;

		// Append this to the DOM
		if (this.pid) {
			// This is a response
			$('#messenger-message-'+ this.pid +' > .replies').append(msg);
		}
		else {
			// This is message
			$('#all-messages').append(msg);
		}
		
		this.ele = $('#messenger-message-'+ this.id);

		// Now add events
		this.ele.children('.tools').find('a').click(this, this.toggleReply);
		$('#messenger-input-' + this.id + '-reply').click(messenger.onClick);
		
		return this;
	};
	Message.prototype.toggleReply = function(event) {
		var id = event.data.id;
		$('#messenger-reply-' + id).toggle();
		$('#messenger-reply-input-' + id).toggle();
	};
	Message.prototype.getDate = function(timestamp) {
		var months		= ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			days		= ['Sun', 'Mon', 'Tue', 'Wed', 'Thr', 'Fri', 'Sat'],
			date		= new Date(timestamp * 1000),
			hour		= date.getHours(),
			meridiem	= date.getHours() > 12 ? 'pm' : 'am',
			minute		= date.getMinutes();
		
		// update hour and minute for better readability
		hour	= hour > 12 ? hour - 12 : hour;
		minute	= minute < 10 ? '0' + minute : minute;
		
		// Day, Month Date, Hour:Minute meridiem
		// Mon, Oct 12, 2:24pm
		return '{day}, {month} {date}, {hour}:{minute}{meridiem}'
			.replace(/{day}/, 		days[date.getDay()])
			.replace(/{month}/, 	months[date.getMonth()])
			.replace(/{date}/, 		date.getDate())
			.replace(/{hour}/, 		hour)
			.replace(/{minute}/, 	minute)
			.replace(/{meridiem}/,	meridiem)
			;
	};

	// Let's go!
	$(document).ready(function() {
		messenger.init();
	});

})(window, jQuery);