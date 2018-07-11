<?php

return <<<'VALUE'
"namespace IPS\\Theme;\nclass class_core_admin_stats extends \\IPS\\Theme\\Template\n{\n\tpublic $cache_key = '365f3b06537aa514c04f6131a3ae9b26';\n\tfunction activitymessage(  ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<p class=\"ipsType_normal\">\n\t\nCONTENT;\n\n$return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_activity_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );\n$return .= <<<CONTENT\n\n<\/p>\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction filtersFormTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar, $form=NULL ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<form accept-charset='utf-8' class=\"ipsForm \nCONTENT;\n$return .= htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\" action=\"\nCONTENT;\n$return .= htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\" method=\"post\" \nCONTENT;\n\nif ( $uploadField ):\n$return .= <<<CONTENT\nenctype=\"multipart\/form-data\"\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n \nCONTENT;\n\nforeach ( $attributes as $k => $v ):\n$return .= <<<CONTENT\n\nCONTENT;\n$return .= htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n=\"\nCONTENT;\n$return .= htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\"\nCONTENT;\n\nendforeach;\n$return .= <<<CONTENT\n data-ipsForm>\n\t<input type=\"hidden\" name=\"\nCONTENT;\n$return .= htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n_submitted\" value=\"1\">\n\t\nCONTENT;\n\nforeach ( $hiddenValues as $k => $v ):\n$return .= <<<CONTENT\n\n\t\t<input type=\"hidden\" name=\"\nCONTENT;\n$return .= htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\" value=\"\nCONTENT;\n$return .= htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\">\n\t\nCONTENT;\n\nendforeach;\n$return .= <<<CONTENT\n\n\t\nCONTENT;\n\nif ( $uploadField ):\n$return .= <<<CONTENT\n\n\t\t<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"\nCONTENT;\n$return .= htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\">\n\t\t<input type=\"hidden\" name=\"plupload\" value=\"\nCONTENT;\n\n$return .= htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\">\n\t\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\n\t<div class=\"ipsPad\" data-controller='core.admin.stats.filtering'>\n\t\t<div class=\"ipsGrid\">\n\t\t\t<div class=\"ipsGrid_span10\">\n\t\t\t\t{$elements['']['date']->html()}\n\n\t\t\t\t<span class='ipsType_small'><a href='#' data-role='toggleGroupFilter'>\nCONTENT;\n\n$return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_stats_by_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );\n$return .= <<<CONTENT\n<\/a><\/span>\n\n\t\t\t\t<div id='elGroupFilter' class='ipsHide' data-hasGroupFilters=\"\nCONTENT;\n\nif ( count( $elements['']['groups']->value ) != count( \\IPS\\Member\\Group::groups( TRUE, FALSE ) ) ):\n$return .= <<<CONTENT\ntrue\nCONTENT;\n\nelse:\n$return .= <<<CONTENT\nfalse\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\">{$elements['']['groups']->html()}<\/div>\n\t\t\t<\/div>\n\t\t\t<div class=\"ipsGrid_span2\">\n\t\t\t\t<button type=\"submit\" class=\"ipsButton ipsButton_primary ipsButton_veryLarge ipsButton_fullWidth\">\nCONTENT;\n\n$return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );\n$return .= <<<CONTENT\n<\/button>\n\t\t\t<\/div>\n\t\t<\/div>\n\t<\/div>\n<\/form>\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction memberactivity( $form, $count, $members ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<div class=''>\n\t<div class='ipsBox'>\n\t\t<h1 class='ipsBox_titleBar'>\nCONTENT;\n\n$return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'activity_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );\n$return .= <<<CONTENT\n<\/h1>\n\t\t<div>\n\t\t\t{$form}\n\t\t<\/div>\n\t<\/div>\n<\/div>\n\n\nCONTENT;\n\nif ( $count !== NULL ):\n$return .= <<<CONTENT\n\n\t<div class='ipsSpacer_top ipsSpacer_double'>\n\t\t{$members}\n\t<\/div>\n\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction membervisits( $form, $count, $members ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<div class=''>\n\t<div class='ipsBox'>\n\t\t<h1 class='ipsBox_titleBar'>\nCONTENT;\n\n$return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'visit_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );\n$return .= <<<CONTENT\n<\/h1>\n\t\t<div>\n\t\t\t{$form}\n\t\t<\/div>\n\t<\/div>\n<\/div>\n\n\nCONTENT;\n\nif ( $count !== NULL ):\n$return .= <<<CONTENT\n\n\t<div class='ipsSpacer_top ipsSpacer_double'>\n\t\t{$members}\n\t<\/div>\n\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\nCONTENT;\n\n\t\treturn $return;\n}\n\n\tfunction tableheader( $start, $end, $count, $string ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n<h1 class='ipsBox_titleBar'>\nCONTENT;\n\n$val = \"{$string}\"; $htmlsprintf = array($start, $end); $pluralize = array( $count ); $return .= \\IPS\\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf, 'pluralize' => $pluralize ) );\n$return .= <<<CONTENT\n<\/h1>\nCONTENT;\n\n\t\treturn $return;\n}}"
VALUE;
