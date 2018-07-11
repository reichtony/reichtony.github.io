//<?php

class hook19 extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'postContainer' => 
  array (
    1 => 
    array (
      'selector' => 'article > aside.ipsComment_author.cAuthorPane.ipsColumn.ipsColumn_medium.ipsResponsive_hidePhone > h3.ipsType_sectionHead.cAuthorPane_author.ipsType_blendLinks.ipsType_break',
      'type' => 'add_inside_end',
      'content' => '{{if settings.onlineIndicator_inName_before == 0}}
{{if settings.onlineIndicator_inName == 1}}
{{if $comment->author()->isOnline()}}{{if settings.onlineIndicator_Advanced == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="state-indicator ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"} {{if $comment->author()->isOnlineFromPC()== 1}}{lang="onlineIndicator_fromPC"}{{elseif $comment->author()->isOnlineFromPhone()== 1}}{lang="onlineIndicator_fromPhone"}{{else}}{lang="onlineIndicator_fromTablet"}{{endif}}" >{$comment->author()->_isOnlineFromSymbol()}</i>{{else}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOnline"} ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"}"></i>{{endif}}{{elseif settings.onlineIndicator_inName_offline == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOffline"} ipsOnlineStatus_offline" data-ipsTooltip title="{lang="offline" sprintf="$comment->author()->name"}"></i>{{endif}} 
{{endif}}
{{endif}}',
    ),
    2 => 
    array (
      'selector' => 'article > div.cAuthorPane.cAuthorPane_mobile.ipsResponsive_showPhone.ipsResponsive_block > span.ipsType_sectionHead.cAuthorPane_author.ipsResponsive_showPhone.ipsResponsive_inlineBlock.ipsType_break.ipsType_blendLinks.ipsTruncate.ipsTruncate_line',
      'type' => 'add_inside_start',
      'content' => '{{if settings.onlineIndicator_inName_before == 1}}
{{if settings.onlineIndicator_inName == 1}}
{{if $comment->author()->isOnline()}}{{if settings.onlineIndicator_Advanced == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="state-indicator ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"} {{if $comment->author()->isOnlineFromPC()== 1}}{lang="onlineIndicator_fromPC"}{{elseif $comment->author()->isOnlineFromPhone()== 1}}{lang="onlineIndicator_fromPhone"}{{else}}{lang="onlineIndicator_fromTablet"}{{endif}}" >{$comment->author()->_isOnlineFromSymbol()}</i>{{else}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOnline"} ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"}"></i>{{endif}}{{elseif settings.onlineIndicator_inName_offline == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOffline"} ipsOnlineStatus_offline" data-ipsTooltip title="{lang="offline" sprintf="$comment->author()->name"}"></i>{{endif}} 
{{endif}}
{{endif}}',
    ),
    3 => 
    array (
      'selector' => 'article > aside.ipsComment_author.cAuthorPane.ipsColumn.ipsColumn_medium.ipsResponsive_hidePhone > h3.ipsType_sectionHead.cAuthorPane_author.ipsType_blendLinks.ipsType_break',
      'type' => 'add_inside_start',
      'content' => '{{if settings.onlineIndicator_inName_before == 1}}
{{if settings.onlineIndicator_inName == 1}}
{{if $comment->author()->isOnline()}}{{if settings.onlineIndicator_Advanced == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="state-indicator ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"} {{if $comment->author()->isOnlineFromPC()== 1}}{lang="onlineIndicator_fromPC"}{{elseif $comment->author()->isOnlineFromPhone()== 1}}{lang="onlineIndicator_fromPhone"}{{else}}{lang="onlineIndicator_fromTablet"}{{endif}}" >{$comment->author()->_isOnlineFromSymbol()}</i>{{else}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOnline"} ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"}"></i>{{endif}}{{elseif settings.onlineIndicator_inName_offline == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOffline"} ipsOnlineStatus_offline" data-ipsTooltip title="{lang="offline" sprintf="$comment->author()->name"}"></i>{{endif}} 
{{endif}}
{{endif}}',
    ),
    4 => 
    array (
      'selector' => 'article > div.cAuthorPane.cAuthorPane_mobile.ipsResponsive_showPhone.ipsResponsive_block > span.ipsType_sectionHead.cAuthorPane_author.ipsResponsive_showPhone.ipsResponsive_inlineBlock.ipsType_break.ipsType_blendLinks.ipsTruncate.ipsTruncate_line',
      'type' => 'add_inside_end',
      'content' => '{{if settings.onlineIndicator_inName_before == 0}}
{{if settings.onlineIndicator_inName == 1}}
{{if $comment->author()->isOnline()}}{{if settings.onlineIndicator_Advanced == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="state-indicator ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"} {{if $comment->author()->isOnlineFromPC()== 1}}{lang="onlineIndicator_fromPC"}{{elseif $comment->author()->isOnlineFromPhone()== 1}}{lang="onlineIndicator_fromPhone"}{{else}}{lang="onlineIndicator_fromTablet"}{{endif}}" >{$comment->author()->_isOnlineFromSymbol()}</i>{{else}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOnline"} ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"}"></i>{{endif}}{{elseif settings.onlineIndicator_inName_offline == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOffline"} ipsOnlineStatus_offline" data-ipsTooltip title="{lang="offline" sprintf="$comment->author()->name"}"></i>{{endif}} 
{{endif}}
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */

}