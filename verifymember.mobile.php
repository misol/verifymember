<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.mobile.php
 * @author	MinSoo Kim (misol.kr@gmail.com)
 * @brief	mobile view class of the verifymember module
 */
require_once(_XE_PATH_.'modules/verifymember/verifymember.view.php');

class verifymemberMobile extends verifymemberView
{
	function dispVerifymemberSettings()
	{
		$obj = new stdClass();
		$obj->is_mobile = TRUE;
		parent::dispVerifymemberSettings($obj);
	}
}
/* End of file verifymember.mobile.php */
/* Location: ./modules/verifymember/verifymember.mobile.php */