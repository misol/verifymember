<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.view.php
 * @author	MinSoo Kim (misol.kr@gmail.com)
 * @brief	admin view class of the verifymember module
 */
class verifymemberView extends verifymember
{
	function dispVerifymemberSettings($obj)
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl) return $this->stop('msg_not_logged');

		$oVerifymemberModel = getModel('verifymember');
		$is_ku = $oVerifymemberModel->isKUer($logged_info->member_srl);
		if($is_ku !== FALSE)
		{
			Context::set('verified', TRUE);
			Context::set('ku_student', $is_ku);
		}
		else
		{
			Context::set('verified', FALSE);
		}

		// Specify a template
		$this->setTemplatePath($this->module_path.'tpl');
		if($obj->is_mobile === TRUE)
		{
			$this->setTemplateFile('verify_settings_mobile');
		}
		else
		{
			$this->setTemplateFile('verify_settings');
		}
	}

}
/* End of file verifymember.view.php */
/* Location: ./modules/verifymember/verifymember.view.php */