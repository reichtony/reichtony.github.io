ips.templates.set('follow.frequency'," {{#hasNotifications}}  <i class='fa fa-bell'></i> {{/hasNotifications}} {{^hasNotifications}}  <i class='fa fa-bell-slash-o'></i> {{/hasNotifications}} {{text}}");ips.templates.set('notification.granted'," <div class='ipsAreaBackground_light cNotificationBox'>  <div class='ipsPhotoPanel ipsPhotoPanel_tiny ipsAreaBackground_positive ipsPad'>   <i class='fa fa-check ipsPos_left ipsType_normal'></i>   <div>    <span class='ipsType_large'>{{#lang}}notificationsAccepted{{/lang}}</span>   </div>  </div>  <div class='ipsPad'>   <p class='ipsType_reset ipsType_medium'>    {{#lang}}notificationsAcceptedBlurb{{/lang}}   </p>  </div> </div>");ips.templates.set('notification.denied'," <div class='ipsAreaBackground_light cNotificationBox'>  <div class='ipsPhotoPanel ipsPhotoPanel_tiny ipsAreaBackground_negative ipsPad'>   <i class='fa fa-times ipsPos_left ipsType_normal'></i>   <div>    <span class='ipsType_large'>{{#lang}}notificationsDisabled{{/lang}}</span>   </div>  </div>  <div class='ipsPad'>   <p class='ipsType_reset ipsType_medium ipsSpacer_bottom'>    {{#lang}}notificationsDisabledBlurb{{/lang}}   </p>  </div> </div>");ips.templates.set('notification.default'," <div class='ipsAreaBackground_light cNotificationBox'>  <div class='ipsPhotoPanel ipsPhotoPanel_tiny ipsAreaBackground ipsPad'>   <i class='fa fa-times ipsPos_left ipsType_normal'></i>   <div>    <span class='ipsType_large'>{{#lang}}notificationsNotSure{{/lang}}</span>   </div>  </div>  <div class='ipsPad'>   <p class='ipsType_reset ipsType_medium ipsSpacer_bottom'>    {{#lang}}notificationsDefaultBlurb{{/lang}}   </p>   <button data-action='promptMe' class='ipsButton ipsButton_veryLight ipsButton_fullWidth'>{{#lang}}notificationsAllow{{/lang}}</button>   <p class='ipsType_small ipsSpacer_top ipsSpacer_half ipsHide' data-role='promptMessage'>    {{#lang}}notificationsAllowPrompt{{/lang}}   </p>  </div> </div>");;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.manageFollowed',{initialize:function(){$(document).on('followingItem',_.bind(this.followingItemChange,this));this.setup();},setup:function(){this._followID=this.scope.attr('data-followID');},followingItemChange:function(e,data){if(data.feedID!=this._followID){return;}
if(!_.isUndefined(data.unfollow)){this.scope.find('[data-role="followDate"], [data-role="followFrequency"]').html('');this.scope.find('[data-role="followAnonymous"]').addClass('ipsHide');this.scope.find('[data-role="followButton"]').addClass('ipsButton_disabled');this.scope.addClass('ipsFaded');return;}
this.scope.find('[data-role="followAnonymous"]').toggleClass('ipsHide',!data.anonymous);if(data.notificationType){this.scope.find('[data-role="followFrequency"]').html(ips.templates.render('follow.frequency',{hasNotifications:(data.notificationType!=='none'),text:ips.getString('followFrequency_'+data.notificationType)}));}}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.metaTagEditor',{_changed:false,initialize:function(){this.on('click','[data-action="addMeta"]',this.addMetaBlock);this.on('change','input, select',this.changed);this.on('submit','form',this.formSubmit);this.on(window,'beforeunload',this.beforeUnload);this.on('change','[data-role="metaTagChooser"]',this.toggleNameField);this.setup();},setup:function(){this.scope.css({zIndex:10000});},toggleNameField:function(e){if($(e.currentTarget).val()=='other')
{$(e.currentTarget).closest('ul').find('[data-role="metaTagName"]').show();}
else
{$(e.currentTarget).closest('ul').find('[data-role="metaTagName"]').hide();}},formSubmit:function(e){var form=$(e.currentTarget);if(form.attr('data-noAjax')){return;}
e.preventDefault();var self=this;form.find('.ipsButton').prop('disabled',true).addClass('ipsButton_disabled');ips.getAjax()(form.attr('action'),{data:form.serialize(),type:'post'}).done(function(){ips.ui.flashMsg.show(ips.getString('metaTagsSaved'));form.find('.ipsButton').prop('disabled',false).removeClass('ipsButton_disabled');self._changed=false;}).fail(function(){form.attr('data-noAjax','true');form.submit();});},beforeUnload:function(){if(this._changed){return ips.getString('metaTagsUnsaved');}},addMetaBlock:function(){var copy=this.scope.find('[data-role="metaTemplate"]').clone().removeAttr('data-role').hide();$('#elMetaTagEditor_tags').append(copy);ips.utils.anim.go('fadeIn',copy);$(document).trigger('contentChange',[copy]);},changed:function(e){this._changed=true;}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.notificationSettings',{initialize:function(){this.on('click','[data-action="promptMe"]',this.promptMe);this.on(document,'permissionGranted.notifications',this.permissionChanged);this.on(document,'permissionDenied.notifications',this.permissionChanged);this.setup();},setup:function(){if(ips.utils.notification.supported){this._showNotificationChoice();}},permissionChanged:function(){this._showNotificationChoice();},promptMe:function(e){e.preventDefault();if(ips.utils.notification.hasPermission()){return;}
this.scope.find('[data-role="promptMessage"]').slideDown();setTimeout(function(){ips.utils.notification.requestPermission();},2000);},_showNotificationChoice:function(){var type=ips.utils.notification.permissionLevel();this.scope.find('[data-role="browserNotifyInfo"]').show().html(ips.templates.render('notification.'+type));}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.register',{usernameField:null,timers:{'username':null,'email':null},ajax:{'username':ips.getAjax(),'email':ips.getAjax()},popup:null,passwordBlurred:true,dirty:false,initialize:function(){this.on('keyup','#elInput_username',this.changeUsername);this.on('keyup','#elInput_email_address',this.changeEmail);this.on('blur','#elInput_username',this.changeUsername);this.on('blur','#elInput_email_address',this.changeEmail);this.on('keyup','#elInput_password_confirm',this.confirmPassword);this.on('blur','#elInput_password_confirm',this.confirmPassword);this.setup();},setup:function(){this.usernameField=this.scope.find('#elInput_username');this.emailField=this.scope.find('#elInput_email_address');this.passwordField=this.scope.find('#elInput_password');this.confirmPasswordField=this.scope.find('#elInput_password_confirm');this.usernameField.after($('<span/>').attr('data-role','validationCheck'));this.emailField.after($('<span/>').attr('data-role','validationCheck'));this.confirmPasswordField.after($('<span/>').attr('data-role','validationCheck'));},changeUsername:function(e){if(this.timers['username']){clearTimeout(this.timers['username']);}
if(this.usernameField.val().length>4||e.type!="keyup"){this.timers['username']=setTimeout(_.bind(this._doCheck,this,'username',this.usernameField),700);}else{this._clearResult(this.usernameField);}},changeEmail:function(e){if(this.timers['email']){clearTimeout(this.timers['email']);}
if((this.emailField.val().length>5&&ips.utils.validate.isEmail(this.emailField.val()))||e.type!="keyup"){this.timers['email']=setTimeout(_.bind(this._doCheck,this,'email',this.emailField),700);}else{this._clearResult(this.emailField);}},changePassword:function(e){if(this.timers['password']){clearTimeout(this.timers['password']);}
if(this.passwordField.val().length>2||e.type!="keyup"){this.timers['password']=setTimeout(_.bind(this._doPasswordCheck,this,this.passwordField),200);}else{this._clearResult(this.passwordField);}
this.confirmPassword();},confirmPassword:function(e){var resultElem=this.confirmPasswordField.next('[data-role="validationCheck"]');if(this.passwordField.val()&&this.passwordField.val()===this.confirmPasswordField.val()){resultElem.hide().html('');this.confirmPasswordField.removeClass('ipsField_error').addClass('ipsField_success');}else{this._clearResult(this.confirmPasswordField);}},_clearResult:function(field){field.removeClass('ipsField_error').removeClass('ipsField_success').next('[data-role="validationCheck"]').html('');},_doCheck:function(type,field){var value=field.val();var resultElem=field.next('[data-role="validationCheck"]');var self=this;if(this.ajax[type]&&this.ajax[type].abort){this.ajax[type].abort();}
field.addClass('ipsField_loading');this.ajax[type](ips.getSetting('baseURL')+'?app=core&module=system&controller=ajax&do='+type+'Exists',{dataType:'json',data:{input:encodeURIComponent(value)}}).done(function(response){if(response.result=='ok'){resultElem.hide().html('');field.removeClass('ipsField_error').addClass('ipsField_success');}else{resultElem.show().html(ips.templates.render('core.forms.validateFailText',{message:response.message}));field.removeClass('ipsField_success').addClass('ipsField_error');}}).fail(function(){}).always(function(){field.removeClass('ipsField_loading');});}});}(jQuery,_));;