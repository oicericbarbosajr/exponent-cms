<?php
/**
 * This file is part of Exponent Content Management System
 *
 * Exponent is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * @category   Exponent CMS
 * @package    Framework
 * @subpackage Subsystems
 * @author     Adam Kessler <adam@oicgroup.net>
 * @copyright  2004-2009 OIC Group, Inc.
 * @license    GPL: http://www.gnu.org/licenses/gpl.txt
 * @version    Release: @package_version@
 * @link       http://www.exponent-docs.org/api/package/PackageName
 */

class expAjaxReply {
	public $packet = array('replyCode'=>'','replyText'=>'','data'=>'');
	public $template;
	public $redirecturl;

	public function __construct($replyCode=200, $replyText='Ok', $data=null, $redirecturl=null) {
		$this->packet = $this->makePacket($replyCode, $replyText, $data);
		$this->redirecturl = $redirecturl;
	}

	public function send() {
		if (!expJavascript::inAjaxAction()) {
			if (isset($this->redirecturl)) {
				redirect_to($this->redirecturl);	
			}
		} else {
			if (expJavascript::requiresJSON()) {
				echo json_encode($this->packet);
			} else {
				global $template;
				echo $template->render();
			}
			exit();
		}
	}

	public static function makePacket($replyCode=200, $replyText='Ok', $data) {
        $ajaxObj['replyCode'] = $replyCode;
    	$ajaxObj['replyText'] = $replyText;
        if (isset($data)) {
	        $ajaxObj['data'] = $data;
        	if (is_array($data)) {
            	$ajaxObj['replyCode'] = 201;
            } elseif (is_string($data)) {
                $ajaxObj['replyCode'] = 202;
        	} elseif (is_bool($data)) {
            	$ajaxObj['replyCode'] = 203;
            } elseif (empty($data)) {
                $ajaxObj['replyCode'] = 204;
        	}
        }
    	return $ajaxObj;
	}
}
?>



