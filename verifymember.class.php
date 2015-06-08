<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.class.php
 * @author	MinSoo Kim (misol.kr@gmail.com)
 * @brief	the highest class of the verifymember module
 */
class verifymember extends ModuleObject
{
	var $triggers = array(
		array('member.getMemberMenu', 'verifymember', 'controller', 'triggerBeforeMemberPopupMenu', 'before'),
		array('member.deleteMember', 'verifymember', 'controller', 'triggerAfterDeleteMember', 'after'),
		array('moduleHandler.init', 'verifymember', 'controller', 'triggerAddMemberMenu', 'after')
	);

	public function moduleInstall()
	{
		return new Object();
	}

	public function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		return false;
	}

	public function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}
	}

	function recompileCache()
	{
		return new Object();
	}

	function moduleUninstall()
	{
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}
		return new Object();
	}
}
/* End of file verifymember.class.php */
/* Location: ./modules/verifymember/verifymember.class.php */