/*jslint nomen: true */
/*globals window, document, jQuery, _, Backbone*/

(function messagesApp(window, document, $, _, Backbone) {
    'use strict';

    var Message,
        MessageView,
        MessageList,
        MainReplyView,
        AppView,
        messages,
        messenger,
        $foot,
        $allMsgs,
        $msgInput,
        $1stMsg,
        $acctArea,
        $msgTmpl   = $('#message-template'),
        $inputTmpl = $('#input-template'),
        $win       = $(window);

    Message = Backbone.Model.extend({
        initialize: function message_doInit() {
            this.set('id', this.get('mid'));
            this.set('posted', this.formatDate(this.get('timestamp')));
            this.set('message', this.htmlize(this.get('message')));
            this.set('link_name', this.get('username') ? this.get('username').toLowerCase() : '');
        },
        formatDate: function message_doFormatDate(timestamp) {
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
                    hour:     hour > 12 ? hour - 12 : hour || 12,
                    minute:   minute < 10 ? '0' + minute : minute,
                    meridiem: hour >= 12 ? 'pm' : 'am'
                };

            // Day, Month Date, Hour:Minute meridiem
            // Mon, Oct 12, 2:24pm
            return '{day}., {month}. {date}{ordinal}, {hour}:{minute}{meridiem}'.tmpl(obj);
        },
        htmlize: function message_doHtmlize(input) {
            var output = input,
                base = window.basePath,
                matchArr = [
                    /(\n|\r|\r\n)/g,
                    new RegExp([ '(?:@)?', '(', window.siteUsers.join('|'), ')' ].join(''), 'gi'),
                    /\*([\w\W]+)?\*/g,
                    /\_([\w\W]+)?\_/g
                ],
                replaceArr = [
                    '<br>',
                    '<a href="' + base + '/picks/$1" class="user">@$1</a>',
                    '<strong>$1</strong>',
                    '<em>$1</em>'
                ],
                items = matchArr.length;

            while (items--) {
                output = output.replace(matchArr[items], replaceArr[items]);
            }

            return output;
        }
    });

    MessageView = Backbone.View.extend({
        tagName: 'div',
        className: 'message',
        template: _.template($msgTmpl.html()),
        events: {
            'click > .tools .reply': 'toggleReply',
            'click > .tools .reply-cancel': 'toggleReply',
            'click > .tools .reply-button': 'submitReply',
        },
        render: function messageView_doRender() {
            var user = this.model.get('username');

            this.$el.html(this.template(this.model.toJSON()));

            _.each(this.model.get('replies'), this.addReplyMessage, this);

            this.tools     = this.$('> .tools > span');
            this.inputArea = this.$('> .tools .input-area');
            this.replyText = this.$('> .tools .reply-textarea');
            this.replyBttn = this.$('> .tools .reply-button');

            return this;
        },
        addReplyMessage: function messageView_doAddReplyMessage(message) {
            var view = new MessageView({model: new Message(message)});

            this.$el.find('> .replies').prepend(view.render().el);
        },
        toggleReply: function messageView_doToggleReply(event) {
            this.tools.toggle();
            this.inputArea.toggle();
        },
        submitReply: function messageView_doSubmitReply() {
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
        url: function messageList_doUrl() {
            var loc  = document.location,
                base = window.basePath;

            return loc.origin + base + '/messages';
        }
    });

    MainReplyView = Backbone.View.extend({
        tagName: 'div',
        id: 'messenger-input',
        template: _.template($inputTmpl.html()),
        events: {
            'click input': 'submitMessage'
        },
        render: function mainReplyView_doRender() {
            this.$el.html(this.template({}));

            this.textarea = this.$('textarea');
            this.input    = this.$('input');

            if (messenger.userInfo.active) {
                this.textarea.attr('disabled', false);
                this.input.attr('disabled', false);
            }

            return this;
        },
        submitMessage: function mainReplyView_doSubmitMessage() {
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
        initialize: function appView_doInit() {
            this.userInfo = this.getUserInfo();

            $win.on('orientationchange', _.bind(this.toggleMessages, this));

            messages.on('reset', this.addAll, this);
            messages.on('sync', this.resync, this);

            messages.fetch();
        },
        addOne: function appView_doAddOne(message) {
            var view = new MessageView({model: message});

            $allMsgs.prepend(view.render().el);
        },
        addAll: function appView_doAddAll(models) {
            // Add controls
            var controls = new MainReplyView({model: {}});

            if (models.length > 0) {
                $1stMsg.hide();
            }

            this.$el.append(controls.render().el);

            // Add messages
            messages.each(this.addOne);
        },
        resync: function appView_doResync(model, collection) {
            $allMsgs.empty();

            messages.fetch();
        },
        getUserInfo: function appView_doGetUserInfo() {
            var obj = {
                    name: false,
                    active: false
                },
                text = $.trim($acctArea.text());

            if (text !== 'Login') {
                obj = {
                    name: $.trim(text.split('|')[0]),
                    active: true
                }
            }

            return obj;
        },
        toggleMessages: function appView_doToggleMessages() {
            var diff = 66,
                lght = 553,
                smht = 353;

            switch (window.orientation) {
                case 90:
                case -90:
                    // Shorten
                    this.$el.height(smht);
                    $allMsgs.height(smht - diff);
                break;

                case 0:
                case 180:
                default:
                    // Lengthen
                    this.$el.height(lght);
                    $allMsgs.height(lght - diff);
                break;
            }
        },
        remove: function appView_doRemove() {
            $win.off('resize');
        }
    });

    // Run
    $(function launchApp() {
        $foot      = $('#footer');
        $allMsgs   = $('#all-messages');
        $msgInput  = $('#messenger-input');
        $1stMsg    = $('#message-first');
        $acctArea  = $('.account-area');

        messages   = new MessageList();
        messenger  = new AppView({el: $('#messenger')});

        if (window.orientation !== 0) {
            messenger.toggleMessages();
        }
    });

    String.prototype.tmpl = function string_doTple(obj) {
        return this.replace(/\{(\w+)\}/g, function (full, match) {
            return obj[match] || full;
        });
    };

    Date.prototype.getOrdinal = function date_doGetOridinal() {
        var date = this.getDate(),
            ords = ['th', 'st', 'nd', 'rd'];

        return ords[(date - 20) % 10] || ords[date] || ords[0];
    };

}(window, document, jQuery, _, Backbone));
