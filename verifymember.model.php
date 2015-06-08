<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.model.php
 * @class	verifymemberModel
 * @author MinSoo Kim (misol.kr@gmail.com)
 * @brief model class of the verifymember module
 */
class verifymemberModel extends verifymember
{
	/**
	 * @brief Return verifymember module setting
	 */
	public function getVerifymemberConfig()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('verifymember');
		if(!is_object($config))
		{
			$config = new stdClass();
		}

		//default settings
		if(!$config->user_config_list)
		{
			$config->user_config_list = 'N';
		}

		return $config;
	}

	/**
	 * @brief Return is KU student or not
	 */
	public function isKUer($member_srl)
	{
		if(!$member_srl || !intval($member_srl)) return FALSE;

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('verifymember.getRecordByMemberSrl', $args);

		// 학번이 존재할 경우 인증
		if($output->data->student_id)
		{
			return $output->data;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * @brief Return is KU student or not
	 */
	public function getRecordByStudentId($student_id)
	{
		if(!$student_id || !intval($student_id)) return FALSE;

		$args = new stdClass();
		$args->student_id = $student_id;
		$output = executeQuery('verifymember.getRecordByStudentId', $args);

		// 레코드가 존재할 경우 값 반환
		if($output->data->student_id)
		{
			return $output->data;
		}
		else
		{
			return FALSE;
		}
	}
}
/* End of file verifymember.model.php */
/* Location: ./modules/verifymember/verifymember.model.php */