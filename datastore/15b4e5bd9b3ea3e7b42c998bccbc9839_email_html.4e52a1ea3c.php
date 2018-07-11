<?php

return <<<'VALUE'
"namespace IPS\\Theme;\n\tfunction email_html_core_notification_new_private_message( $message, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"messenger_notify_title\", FALSE, array( 'sprintf' => array( $message->author()->name ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n<br \/><br \/>\n\n<a href='\nCONTENT;\n$return .= htmlspecialchars( $message->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' style=\"color: #ffffff; font-family: 'Helvetica Neue', helvetica, sans-serif; text-decoration: none; font-size: 12px; background: \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->email_color;\n$return .= <<<CONTENT\n; line-height: 32px; padding: 0 10px; display: inline-block; border-radius: 3px;\">\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"messenger_inline_button\", FALSE), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/a>\n\n<br \/>\n<br \/>\n<em style='color: #8c8c8c'>&mdash; \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->board_name;\n$return .= <<<CONTENT\n<\/em>\n\n<br \/>\n<br \/>\n<hr style='height: 0px; border-top: 1px solid #f0f0f0;' \/>\n\n<table width='100%' cellpadding='10' cellspacing='0' border='0'>\n\t<tr>\n\t\t<td dir='{dir}'>\n\t\t\t<strong>\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"members_in_convo\", FALSE, array( 'pluralize' => array( $message->item()->activeParticipants ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/strong>\n\t\t<\/td>\n\t<\/tr>\n\t<tr>\n\t\t<td dir='{dir}'>\n\t\t\t<table width='100%' cellpadding='0' cellspacing='0' border='0' class='responsive_table'>\n\t\t\t\t\nCONTENT;\n\n$i = 0;\n$return .= <<<CONTENT\n\n\t\t\t\t\nCONTENT;\n\nforeach ( $message->item()->maps() as $map ):\n$return .= <<<CONTENT\n\n\t\t\t\t\t\nCONTENT;\n\nif ( $map['map_user_active'] ):\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t\nCONTENT;\n\nif ( ( $i % 4 ) == 0 ):\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t<td dir='{dir}' width='200' class='responsive_row'>\n\t\t\t\t\t\t\t<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td dir='{dir}' width='50'>\n\t\t\t\t\t\t\t\t\t\t<img src='\nCONTENT;\n\n$return .= htmlspecialchars( \\IPS\\Member::load( $map['map_user_id'] )->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' width='40' height='40' style='border: 1px solid #777777; vertical-align: middle;'>\n\t\t\t\t\t\t\t\t\t<\/td>\n\t\t\t\t\t\t\t\t\t<td dir='{dir}'>\n\t\t\t\t\t\t\t\t\t\t<strong>\nCONTENT;\n\n$return .= htmlspecialchars( \\IPS\\Member::load( $map['map_user_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/strong><br \/>\n\t\t\t\t\t\t\t\t\t\t\nCONTENT;\n\n$mapGroup = \\IPS\\Member\\Group::constructFromData( \\IPS\\Member::load( $map['map_user_id'] )->group );\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t\t\t\t\t<span style='font-size: 14px'>\nCONTENT;\n\n$return .= $mapGroup->formatName( $email->language->addToStack( 'core_group_' . $mapGroup->g_id ) );\n$return .= <<<CONTENT\n<\/span>\n\t\t\t\t\t\t\t\t\t<\/td>\n\t\t\t\t\t\t\t\t<\/tr>\n\t\t\t\t\t\t\t<\/table>\n\t\t\t\t\t\t<\/td>\n\t\t\t\t\t\t\nCONTENT;\n\nif ( ( ( $i + 1 ) % 4 ) == 0 ):\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t\t<\/tr>\n\t\t\t\t\t\t\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t\nCONTENT;\n\n$i++;\n$return .= <<<CONTENT\n\n\t\t\t\t\t\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\n\t\t\t\t\nCONTENT;\n\nendforeach;\n$return .= <<<CONTENT\n\n\t\t\t\t\nCONTENT;\n\nwhile ( ( ( $i ) % 4 ) != 0 ):\n$return .= <<<CONTENT\n\n\t\t\t\t\t<td dir='{dir}'>&nbsp;<\/td>\n\t\t\t\t\t\nCONTENT;\n\nif ( ( ( $i + 1 ) % 4 ) == 0 ):\n$return .= <<<CONTENT\n\n\t\t\t\t\t\t<\/tr>\n\t\t\t\t\t\nCONTENT;\n\nendif;\n$return .= <<<CONTENT\n\n\t\t\t\t\t\nCONTENT;\n\n$i++;\n$return .= <<<CONTENT\n\n\t\t\t\t\nCONTENT;\n\nendwhile;;\n$return .= <<<CONTENT\n\n\t\t\t<\/table>\n\t\t<\/td>\n\t<\/tr>\n<\/table>\n\n<br \/>\n<br \/>\n\n<table width='100%' cellpadding='15' cellspacing='0' border='0' style='background: #f5f5f5'>\n\t<tr>\n\t\t<td dir='{dir}'>\n\t\t\t<h2 style='margin: 0; font-size: 19px; font-weight: 500'>\nCONTENT;\n$return .= htmlspecialchars( $message->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/h2>\n\t\t<\/td>\n\t<\/tr>\n<\/table>\n<br \/>\n\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n\t<tr>\n\t\t<td dir='{dir}' width='20' class='hidePhone' style='width: 0; max-height: 0; overflow: hidden; float: left;'>&nbsp;<\/td>\n\t\t<td dir='{dir}' width='100' valign='top' align='center' class='hidePhone' style='width: 0; max-height: 0; overflow: hidden; float: left;'>\n\t\t\t<img src='\nCONTENT;\n$return .= htmlspecialchars( $message->author()->get_photo( true, true ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' width='100' height='100' style='border: 1px solid #777777; vertical-align: middle;'>\n\t\t<\/td>\n\t\t<td dir='{dir}' width='20' class='hidePhone' style='width: 0; max-height: 0; overflow: hidden; float: left;'>&nbsp;<\/td>\n\t\t<td dir='{dir}' valign='top'>\n\t\t\t<div style='line-height: 1.5'>\n\t\t\t\t{$email->parseTextForEmail( $message->post, $email->language )}\n\t\t\t<\/div>\n\t\t\t<br \/>\n\t\t\t<hr style='height: 0px; border-top: 1px solid #f0f0f0;' \/>\n\t\t\t<br \/>\n\n\t\t\t<a href='\nCONTENT;\n$return .= htmlspecialchars( $message->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n' style=\"color: #ffffff; font-family: 'Helvetica Neue', helvetica, sans-serif; text-decoration: none; font-size: 12px; background: \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->email_color;\n$return .= <<<CONTENT\n; line-height: 32px; padding: 0 10px; display: inline-block; border-radius: 3px;\">\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"messenger_inline_button\", FALSE), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n<\/a>\n\t\t<\/td>\n\t\t<td dir='{dir}' width='20' class='hidePhone' style='width: 0; max-height: 0; overflow: hidden; float: left;'>&nbsp;<\/td>\n\t<\/tr>\n<\/table>\nCONTENT;\n\n\t\treturn $return;\n}\n\tfunction email_plaintext_core_notification_new_private_message( $message, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"messenger_notify_title\", FALSE, array( 'sprintf' => array( $message->author()->name ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"email_url_to_message\", FALSE), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n: \nCONTENT;\n$return .= htmlspecialchars( $message->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n\n-- \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->board_name;\n$return .= <<<CONTENT\n\nCONTENT;\n\n\t\treturn $return;\n}"
VALUE;
