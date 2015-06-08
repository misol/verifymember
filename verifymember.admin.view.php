<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.admin.view.php
 * @author	MinSoo Kim (misol.kr@gmail.com)
 * @brief	admin view class of the verifymember module
 */
class verifymemberAdminView extends verifymember
{
	/**
	 * Initialization
	 * @return void
	 */
	public function init()
	{
	}

	public function dispVerifymemberAdminConfig()
	{
		$oVerifymemberModel = getModel('verifymember');
		$config = $oVerifymemberModel->getVerifymemberConfig();

		// Get the list of groups
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();
		$selected_group_list = array();
		if(count($group_list))
		{
			foreach($group_list as $key => $val)
			{
				$selected_group_list[$key] = $val;
			}
		}
		Context::set('group_list', $selected_group_list);
		//Security
		$security = new Security();
		$security->encodeHTML('group_list..title','group_list..description');

		Context::set('verifymember_config', $config);


		// Specify a template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('ku_config');
	}


	/**
	 * display verified member list
	 *
	 * @return void
	 */
	function dispVerifymemberAdminList()
	{
		
	}

	/**
	 * display verified member info
	 *
	 * @return void
	 */
	function dispVerifymemberAdminDetail()
	{
		$member_srl = Context::get('view_member_srl');

		$oMemberModel = getModel('member');
		$view_member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		Context::set('view_member_info',$view_member_info);

		$oVerifymemberModel = getModel('verifymember');
		$is_ku = $oVerifymemberModel->isKUer($member_srl);
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
		$this->setTemplateFile('verify_admin_settings');
	}
}
/* End of file verifymember.admin.view.php */
/* Location: ./modules/verifymember/verifymember.admin.view.php */