//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class hook21 extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'userPhoto' => 
  array (
    0 => 
    array (
      'selector' => 'a',
      'type' => 'add_inside_start',
      'content' => '{{if settings.onlineIndicator_inAvatar == 1 AND settings.onlineIndicator_roundAvatars == 0}}
{{if $size == large}}
{{if $member->isOnline()}}<span data-ipsTooltip title="{lang="online_now" sprintf="$member->name"}" class="avatar_indicator avatar_indicator_online avatar_indicator_{setting="onlineIndicator_inAvatar_position"} avatar_indicator_online_color_{setting=\'onlineIndicator_inAvatar_position\'}"></span>{{else}}<span data-ipsTooltip title="{lang="offline" sprintf="$member->name"}" class="avatar_indicator avatar_indicator_offline avatar_indicator_{setting="onlineIndicator_inAvatar_position"} avatar_indicator_offline_color_{setting=\'onlineIndicator_inAvatar_position\'}"></span>{{endif}}
{{endif}}
{{endif}}',
    ),
    1 => 
    array (
      'selector' => 'a > img',
      'type' => 'replace',
      'content' => '{{if settings.onlineIndicator_inAvatar == 1 AND settings.onlineIndicator_roundAvatars == 1}}
{{if $size == large}}
{{if $member->isOnline()}}
<img {{if settings.onlineIndicator_roundAvatars == 1}}class="avatar_round_edge avatar_round_edge_online avatar_round_indicator_online_color {{if settings.onlineIndicator_roundAvatars_glow == 1}}online_glow{{endif}}"{{endif}} src="{$member->photo}" alt="{$member->name}" itemprop="image"> </img>
{{else}}
<img {{if settings.onlineIndicator_roundAvatars == 1}}class="avatar_round_edge avatar_round_edge_offline avatar_round_indicator_offline_color {{if settings.onlineIndicator_roundAvatars_glow == 1}}offline_glow{{endif}}"{{endif}} src="{$member->photo}" alt="{$member->name}" itemprop="image"> </img>
{{endif}}
{{else}}
<img src="{$member->photo}" alt="{$member->name}" itemprop="image"> </img>
{{endif}}
{{else}}
<img src="{$member->photo}" alt="{$member->name}" itemprop="image"> </img>
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}
