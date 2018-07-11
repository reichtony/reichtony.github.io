<?php

return <<<'VALUE'
"namespace IPS\\Theme;\nclass class_core_admin_livesearch extends \\IPS\\Theme\\Template\n{\n\tpublic $cache_key = '365f3b06537aa514c04f6131a3ae9b26';\n\tfunction club( $club ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<li class='ipsPad_half ipsClearfix' data-role='result'>\n\t<a href='\nCONTENT;\n\n$return .= str_replace( '&', '&amp;', \\IPS\\Http\\Url::internal( \"app=core&module=clubs&controller=clubs&do=edit&id=\", null, \"\", array(), 0 ) );\n$return .= <<<CONTENT\n\nCONTENT;\n$return .= htmlspecialchars( $club->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' class='ipsPos_left'>\nCONTENT;\n$return .= htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/a>\n<\/li>\n\n\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction generic( $url, $lang, $appName ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<li class='ipsPad_half ipsClearfix' data-role='result'>\n\t<a href='\nCONTENT;\n$return .= htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' class='ipsPos_left'>[\nCONTENT;\n$return .= htmlspecialchars( $appName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n] \nCONTENT;\n\n$val = \"{$lang}\"; $return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );\n$return .= <<<CONTENT\n<\/a>\n<\/li>\n\n\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction group( $group ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<li class='ipsPad_half ipsClearfix' data-role='result'>\n\t<a href='\nCONTENT;\n\n$return .= str_replace( '&', '&amp;', \\IPS\\Http\\Url::internal( \"app=core&module=members&controller=groups&do=form&id=\", null, \"\", array(), 0 ) );\n$return .= <<<CONTENT\n\nCONTENT;\n$return .= htmlspecialchars( $group->g_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' class='ipsPos_left'>\nCONTENT;\n$return .= htmlspecialchars( $group->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/a>\n<\/li>\n\n\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction member( $member ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<li class='ipsPhotoPanel ipsPhotoPanel_mini ipsPad_half ipsClearfix' data-role='result'>\n\t<a href='\nCONTENT;\n\n$return .= str_replace( '&', '&amp;', \\IPS\\Http\\Url::internal( \"app=core&module=members&controller=members&do=view&id=\", null, \"\", array(), 0 ) );\n$return .= <<<CONTENT\n\nCONTENT;\n$return .= htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' class='ipsPos_left'>\nCONTENT;\n\n$return .= \\IPS\\Theme::i()->getTemplate( \"global\", \"core\" )->userPhoto( $member, 'mini' );\n$return .= <<<CONTENT\n<\/a>\n\t<div>\n\t\t<h2 class='ipsType_sectionHead'><strong><a href='\nCONTENT;\n\n$return .= str_replace( '&', '&amp;', \\IPS\\Http\\Url::internal( \"app=core&module=members&controller=members&do=view&id=\", null, \"\", array(), 0 ) );\n$return .= <<<CONTENT\n\nCONTENT;\n$return .= htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n'>\nCONTENT;\n\nif ( $member->name ):\n$return .= <<<CONTENT\n\nCONTENT;\n$return .= htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\nCONTENT;\n\nelse:\n$return .= <<<CONTENT\n\nCONTENT;\n\n$return .= \\IPS\\Theme::i()->getTemplate( \"members\", \"core\" )->memberReserved(  );\n$return .= <<<CONTENT\n\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n<\/a><\/strong><\/h2><br>\n\t\t\nCONTENT;\n\n$return .= $member->groupName;\n$return .= <<<CONTENT\n &middot; <span class='ipsType_light'>\nCONTENT;\n$return .= htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/span>\n\t<\/div>\n<\/li>\n\n\nCONTENT;\n\n\t\treturn $return;\n}}"
VALUE;
