//<?php

class hook20 extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'comment' => 
  array (
    0 => 
    array (
      'selector' => 'div[data-controller=\'core.front.core.comment\'].ipsComment_content.ipsType_medium > div.ipsComment_header.ipsPhotoPanel.ipsPhotoPanel_mini > div > h3.ipsComment_author.ipsType_blendLinks',
      'type' => 'add_inside_start',
      'content' => '{{if settings.onlineIndicator_inConversations == 1}}
{{if $comment->author()->isOnline()}}{{if settings.onlineIndicator_Advanced == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="state-indicator ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"} {{if $comment->author()->isOnlineFromPC()== 1}}{lang="onlineIndicator_fromPC"}{{elseif $comment->author()->isOnlineFromPhone()== 1}}{lang="onlineIndicator_fromPhone"}{{else}}{lang="onlineIndicator_fromTablet"}{{endif}}" >{$comment->author()->_isOnlineFromSymbol()}</i>{{else}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOnline"} ipsOnlineStatus_online" data-ipsTooltip title="{lang="online_now" sprintf="$comment->author()->name"}"></i>{{endif}}{{elseif settings.onlineIndicator_inName_offline == 1}}<i style="font-size: {setting=\'onlineIndicator_inName_size\'}px" class="fa {setting="onlineIndicator_iconOffline"} ipsOnlineStatus_offline" data-ipsTooltip title="{lang="offline" sprintf="$comment->author()->name"}"></i>{{endif}} 
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */

}