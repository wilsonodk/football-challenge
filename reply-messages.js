/*jslint nomen: true */
/*globals window, document, jQuery, _, Backbone*/
(function (window, document, $, _, Backbone) {
	"use strict";
	//Backbone.emulateJSON = true;
	//Backbone.emulateHTTP = true;
	var Message, MessageView, MessageList, MainReplyView, AppView, messages, messenger;

	Message = Backbone.Model.extend({
		initialize: function () {
			this.set("id", this.get("mid"));
			this.set("posted", this.formatDate(this.get("timestamp")));
			this.on("change", this.update, this);
		},
		formatDate: function (timestamp) {
			var months		= ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
				days		= ['Sun', 'Mon', 'Tue', 'Wed', 'Thr', 'Fri', 'Sat'],
				date		= new Date(timestamp),
				hour		= date.getHours(),
				minute		= date.getMinutes(),
				obj			= {
					day: days[date.getDay()],
					month: months[date.getMonth()],
					date: date.getDate(),
					ordinal: date.getOrdinal(),
					hour: hour > 12 ? hour - 12 : hour,
					minute: minute < 10 ? '0' + minute : minute,
					meridiem: hour >= 12 ? 'pm' : 'am'
				};
			// Day, Month Date, Hour:Minute meridiem
			// Mon, Oct 12, 2:24pm
			return '{day}., {month}. {date}{ordinal}, {hour}:{minute}{meridiem}'.tmpl(obj);
		},
		update: function () {
			console.log(arguments);
		}
	});

	MessageView = Backbone.View.extend({
		tagName: "div",
		className: "message",
		template: _.template($("#message-template").html()),
		events: {
			"click > .tools .reply": "toggleReply",
			"click > .tools .reply-cancel": "toggleReply",
			"click > .tools .reply-button": "submitReply",
			"click > .tools .edit": "toggleEdit",
			"click > .edit-area .edit-cancel": "toggleEdit",
			"click > .edit-area .edit-button": "submitEdit"
		},
		render: function () {
			var user = this.model.get("username");
			this.$el.html(this.template(this.model.toJSON()));
			_.each(this.model.get("replies"), this.addReplyMessage, this);
			this.tools = this.$('> .tools > span');
			this.inputArea = this.$('> .tools .input-area');
			this.replyText = this.$('> .tools .reply-textarea');
			this.replyBttn = this.$('> .tools .reply-button');
			if (user === messenger.userInfo.name) {
				this.editTxtArea = this.$('> .edit-area .edit-textarea');
				this.editText = this.$('> .tools .editable').toggle();
				this.editBttn = this.$('> .edit-area .edit-button');
				this.editArea = this.$('> .edit-area');
				this.message = this.$('> .message-text');
			}
			return this;
		},
		addReplyMessage: function (message) {
			var view = new MessageView({model: new Message(message)});
			this.$el.find('> .replies').prepend(view.render().el);
		},
		toggleReply: function (event) {
			this.tools.toggle();
			this.inputArea.toggle();
		},
		submitReply: function () {
			var payload = {
				pid: this.model.get("mid"),
				message: this.replyText.val()
			};
			if (payload.message) {
				this.replyBttn.attr('disabled', true);
				messages.create(payload);
			}
		},
		toggleEdit: function (event) {
			var ta = this.editTxtArea;
			this.tools.toggle();
			this.editArea.toggle();
			this.message.toggle();
			window.setTimeout(function() {
				if (ta.prop('clientHeight') != ta.prop('scrollHeight')) {
					ta.prop('clientHeight', ta.prop('scrollHeight'));
					ta.height(ta.prop('scrollHeight') + 20);
				}
			}, 0);
		},
		submitEdit: function () {
			var mid = this.model.get("mid"),
				newMessage = this.editTxtArea.val(),
				currentMessage = this.model.get("message");
			if (newMessage != currentMessage) {
				this.editBttn.attr('disabled', true);
				this.model.save({message: newMessage});
			}

		}
	});

	MessageList = Backbone.Collection.extend({
		model: Message,
		url: function () {
			var loc = document.location, base = window.basePath;
			return loc.origin + base + '/messages';
		}
	});

	MainReplyView = Backbone.View.extend({
		tagName: "div",
		id: "messenger-input",
		template: _.template($("#input-template").html()),
		events: {
			"click input": "submitMessage"
		},
		render: function() {
			this.$el.html(this.template({}));
			this.textarea = this.$('textarea');
			this.input = this.$('input');
			if (messenger.userInfo.active) {
				this.textarea.attr('disabled', false);
				this.input.attr('disabled', false);
			}
			return this;
		},
		submitMessage: function() {
			var payload = {
				pid: "main",
				message: this.textarea.val()
			};
			if (payload.message) {
				this.input.attr('disabled', true);
				messages.create(payload);
			}
		}
	});

	AppView = Backbone.View.extend({
		initialize: function () {
			this.userInfo = this.getUserInfo();		
			messages.on("reset", this.addAll, this);
			messages.on("sync", this.resync, this);
			messages.fetch();
		},
		addOne: function (message) {
			var view = new MessageView({model: message});
			$('#all-messages').prepend(view.render().el);
		},
		addAll: function () {
			// Add controls
			var controls = new MainReplyView({model: {}});
			this.$el.append(controls.render().el);
			// Add messages
			messages.each(this.addOne);
		},
		resync: function (model, collection) {
			messages.reset(collection);
		},
		getUserInfo: function () {
			var obj = { name: false, active: false }, 
				text = $.trim($('.account-area').text());
			if (text !== 'Login') {
				obj = {
					name: $.trim(text.split('|')[0]),
					active: true
				}
			}
			return obj;
		}
	});
	// Run
	$(function () {
		messages = new MessageList();
		messenger = new AppView({el: $("#messenger")});
	});

	String.prototype.tmpl = function (obj) {
		return this.replace(/\{(\w+)\}/g, function (full, match) {
			return obj[match] || full;
		});
	};

	Date.prototype.getOrdinal = function () {
		var date = this.getDate(), ords = ["th", "st", "nd", "rd"];
		return ords[(date - 20) % 10] || ords[date] || ords[0];
	};
}(window, document, jQuery, _, Backbone));