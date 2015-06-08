<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.admin.controller.php
 * @author	MinSoo Kim (misol.kr@gmail.com)
 * @brief	admin controller class of the verifymember module
 */
class verifymemberAdminController extends verifymember
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * 고대생 인증 모듈 기본 설정
	 * @return void
	 */
	public function procVerifymemberAdminInsertConfig()
	{
		$oModuleController = getController('module');
		// Get the configuration information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('verifymember');

		$config->user_config_list = trim(Context::get('user_config_list'));

		if($config->user_config_list != 'Y') $config->user_config_list = 'N';

		$oModuleController->insertModuleConfig('verifymember', $config);
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * 고대생 인증 후 그룹 지정 설정
	 * @return void
	 */
	public function procVerifymemberAdminInsertGroupConfig()
	{
		$oModuleController = getController('module');
		// Get the configuration information
		$oModuleModel = getModel('module');
		$base_config = $oModuleModel->getModuleConfig('verifymember');
		// Arrange variables
		$args = Context::getRequestVars();

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		$ku_group = intval(Context::get('ku_group'));

		// 실제 존재하는 그룹인지 확인(유효성 체크)
		if(!isset($group_list[$ku_group])) unset($ku_group);

		if(isset($ku_group)) $base_config->ku_group = $ku_group;

		$base_config->group_reset = trim(Context::get('group_reset'));
		if($base_config->group_reset !== 'N') $base_config->group_reset = 'Y';

		$oModuleController->insertModuleConfig('verifymember', $base_config);
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * 고대생 인증 해제
	 * @return void
	 */
	public function procVerifymemberAdminDeleteInfo()
	{
		$oDB = &DB::getInstance();
		$oDB->begin();

		// Get the configuration information
		$oModuleModel = getModel('module');
		$member_srl = Context::get('target_srl');
		$config = $oModuleModel->getModuleConfig('verifymember');

		if(!$member_srl || !intval($member_srl)) return FALSE;

		$oVerifymemberModel = getModel('verifymember');
		$is_ku = $oVerifymemberModel->isKUer($member_srl);

		if($is_ku)
		{
			if($config->ku_group)
			{
				// Get the default group
				$oMemberModel = getModel('member');
				$default_group = $oMemberModel->getDefaultGroup();

				$del_group_args = new stdClass;
				$del_group_args->member_srl = $member_srl;
				$del_group_args->group_srl = $config->ku_group;
				$del_group_output = executeQuery('verifymember.deleteMemberGroup', $del_group_args);
				if(!$del_group_output->toBool())
				{
					$oDB->rollback();
					return $output;
				}

				// 남은 그룹 수 확인 후 없으면 기본 그룹 배정
				$check_args = new stdClass();
				$check_args->member_srl = $member_srl;
				$output = executeQueryArray('verifymember.getMemberGroup', $check_args);
				$group_list = $output->data;
				if(!$group_list)
				{
					// 그룹 배정
					$new_group_args = new stdClass;
					$new_group_args->member_srl = $member_srl;
					$new_group_args->group_srl = $default_group->group_srl;
					executeQuery('member.addMemberToGroup', $new_group_args);
				}

				// 캐시 비우기
				$oCacheHandler = CacheHandler::getInstance('object', null, true);
				if($oCacheHandler->isSupport())
				{
					$object_key = 'member_groups:' . getNumberingPath($member_srl) . $member_srl . '_0';
					$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
					$oCacheHandler->delete($cache_key);
				}

				$oCacheHandler = CacheHandler::getInstance('object');
				if($oCacheHandler->isSupport())
				{
					$object_key = 'member_info:' . getNumberingPath($member_srl) . $member_srl;
					$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
					$oCacheHandler->delete($cache_key);
				}
			}

			$args = new stdClass();
			$args->member_srl = $member_srl;
			$output = executeQuery('verifymember.deleteRecordByMemberSrl', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

		}

		$this->setRedirectUrl(Context::get('error_return_url'));
	}
}
/* End of file verifymember.admin.controller.php */
/* Location: ./modules/verifymember/verifymember.admin.controller.php */