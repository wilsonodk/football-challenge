/*jslint nomen: true */
/*globals window, document, jQuery, _, Backbone*/
(function (window, document, $, _, Backbone) {
    'use strict';
    var Message, MessageView, MessageList, MainReplyView, AppView, messages, messenger;

    Message = Backbone.Model.extend({
        initialize: function () {
            this.set('id', this.get('mid'));
            this.set('posted', this.formatDate(this.get('timestamp')));
        },
        formatDate: function (timestamp) {
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                days   = ['Sun', 'Mon', 'Tue', 'Wed', 'Thr', 'Fri', 'Sat'],
                date   = new Date(timestamp),
                hour   = date.getHours(),
                minute = date.getMinutes(),
                obj    = {
                    day:      days[date.getDay()],
                    month:    months[date.getMonth()],
                    date:     date.getDate(),
                    ordinal:  date.getOrdinal(),
                    hour:     hour > 12 ? hour - 12 : hour,
                    minute:   minute < 10 ? '0' + minute : minute,
                    meridiem: hour >= 12 ? 'pm' : 'am'
                };

            // Day, Month Date, Hour:Minute meridiem
            // Mon, Oct 12, 2:24pm
            return '{day}., {month}. {date}{ordinal}, {hour}:{minute}{meridiem}'.tmpl(obj);
        }
    });

    MessageView = Backbone.View.extend({
        tagName: 'div',
        className: 'message',
        template: _.template($('#message-template').html()),
        events: {
            'click > .tools .reply': 'toggleReply',
            'click > .tools .reply-cancel': 'toggleReply',
            'click > .tools .reply-button': 'submitReply',
        },
        render: function () {
            var user = this.model.get('username');

            this.$el.html(this.template(this.model.toJSON()));

            _.each(this.model.get('replies'), this.addReplyMessage, this);

            this.tools     = this.$('> .tools > span');
            this.inputArea = this.$('> .tools .input-area');
            this.replyText = this.$('> .tools .reply-textarea');
            this.replyBttn = this.$('> .tools .reply-button');

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
                pid: this.model.get('mid'),
                message: this.replyText.val()
            };

            if (payload.message) {
                this.replyBttn.attr('disabled', true);
                messages.create(payload);
            }
        }
    });

    MessageList = Backbone.Collection.extend({
        model: Message,
        url: function () {
            var loc  = document.location,
                base = window.basePath;

            return loc.origin + base + '/messages';
        }
    });

    MainReplyView = Backbone.View.extend({
        tagName: 'div',
        id: 'messenger-input',
        template: _.template($('#input-template').html()),
        events: {
            'click input': 'submitMessage'
        },
        render: function() {
            this.$el.html(this.template({}));

            this.textarea = this.$('textarea');
            this.input    = this.$('input');

            if (messenger.userInfo.active) {
                this.textarea.attr('disabled', false);
                this.input.attr('disabled', false);
            }

            return this;
        },
        submitMessage: function() {
            var payload = {
                pid: 'main',
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

            messages.on('reset', this.addAll, this);
            messages.on('sync', this.resync, this);

            messages.fetch();
        },
        addOne: function (message) {
            var view = new MessageView({model: message});

            $('#all-messages').prepend(view.render().el);
        },
        addAll: function () {
            // Add controls
            var controls = new MainReplyView({model: {}});

            $('#all-messages').empty();
            this.$el.append(controls.render().el);

            // Add messages
            messages.each(this.addOne);
        },
        resync: function (model, collection) {
            messages.fetch();
        },
        getUserInfo: function () {
            var obj  = {
                    name: false,
                    active: false
                },
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
        messages  = new MessageList();
        messenger = new AppView({el: $('#messenger')});
    });

    String.prototype.tmpl = function (obj) {
        return this.replace(/\{(\w+)\}/g, function (full, match) {
            return obj[match] || full;
        });
    };

    Date.prototype.getOrdinal = function () {
        var date = this.getDate(), ords = ['th', 'st', 'nd', 'rd'];

        return ords[(date - 20) % 10] || ords[date] || ords[0];
    };

}(window, document, jQuery, _, Backbone));
