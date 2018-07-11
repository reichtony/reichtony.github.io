;(function($,_,undefined){"use strict";ips.controller.register('core.admin.stats.filtering',{initialize:function(){this.on('click','[data-role="toggleGroupFilter"]',this.toggleGroupFilter);if($('#elGroupFilter').attr('data-hasGroupFilters')=='true')
{$('#elGroupFilter').show();}},toggleGroupFilter:function(e){e.preventDefault();if($('#elGroupFilter').is(':visible'))
{$('#elGroupFilter').find('input[type="checkbox"]').prop('checked',true);$('#elGroupFilter').slideUp();}
else
{$('#elGroupFilter').slideDown();}}});}(jQuery,_));;